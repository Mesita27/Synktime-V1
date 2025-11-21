"""
RFID Service for USB serial readers
Handles RFID card/tag enrollment and verification using pyserial and pyusb
"""

import asyncio
import logging
from typing import Optional, List, Dict, Any
from datetime import datetime
import re

# Try to import serial/USB libraries, with fallback for development
try:
    import serial
    import serial.tools.list_ports
    SERIAL_AVAILABLE = True
except ImportError:
    SERIAL_AVAILABLE = False
    logging.warning("PySerial not available. Using mock implementation for development.")

try:
    import usb.core
    import usb.util
    USB_AVAILABLE = True
except ImportError:
    USB_AVAILABLE = False
    logging.warning("PyUSB not available. USB device detection disabled.")

from config.settings import settings
from models.biometric_models import BiometricResponse

logger = logging.getLogger(__name__)

class RFIDService:
    """RFID Service for USB serial readers"""
    
    def __init__(self):
        self.serial_connections = {}  # device_id -> serial connection
        self.device_info = {}  # device_id -> device info
        self.initialized = False
        
    async def initialize(self):
        """Initialize RFID service and scan for devices"""
        try:
            logger.info("Initializing RFID service...")
            
            # Scan for available RFID readers
            await self._scan_devices()
            
            self.initialized = True
            logger.info(f"RFID service initialized. Found {len(self.device_info)} devices.")
            
        except Exception as e:
            logger.error(f"Failed to initialize RFID service: {e}")
            # Don't raise - allow service to start without RFID support
            self.initialized = True
    
    async def cleanup(self):
        """Cleanup resources"""
        # Close all serial connections
        for device_id, conn in self.serial_connections.items():
            try:
                if conn and conn.is_open:
                    conn.close()
                    logger.info(f"Closed RFID device {device_id}")
            except Exception as e:
                logger.error(f"Error closing RFID device {device_id}: {e}")
        
        self.serial_connections.clear()
        self.device_info.clear()
        self.initialized = False
        logger.info("RFID service cleaned up")
    
    async def _scan_devices(self):
        """Scan for RFID devices on USB/serial ports"""
        try:
            if not SERIAL_AVAILABLE:
                return
                
            # Common RFID reader vendor/product IDs and names
            rfid_patterns = [
                # Common RFID reader patterns
                r'.*rfid.*',
                r'.*nfc.*', 
                r'.*mifare.*',
                r'.*acr.*',
                r'.*omni.*key.*',
                r'.*proxmark.*',
                r'.*chameleon.*',
                # USB-to-Serial converter patterns (often used with RFID)
                r'.*cp210.*',
                r'.*ft232.*',
                r'.*ch340.*',
                r'.*pl2303.*'
            ]
            
            # Scan serial ports
            ports = serial.tools.list_ports.comports()
            
            for port in ports:
                device_name = (port.description or "").lower()
                manufacturer = (port.manufacturer or "").lower()
                
                # Check if this looks like an RFID device
                is_rfid = any(
                    re.search(pattern, device_name, re.IGNORECASE) or 
                    re.search(pattern, manufacturer, re.IGNORECASE)
                    for pattern in rfid_patterns
                )
                
                if is_rfid or port.device.startswith('/dev/ttyUSB') or port.device.startswith('COM'):
                    device_id = f"rfid_{port.device.replace('/', '_').replace(':', '')}"
                    
                    self.device_info[device_id] = {
                        'device_id': device_id,
                        'port': port.device,
                        'description': port.description,
                        'manufacturer': port.manufacturer,
                        'vid': port.vid,
                        'pid': port.pid,
                        'serial_number': port.serial_number
                    }
                    
                    logger.info(f"Found potential RFID device: {port.device} - {port.description}")
                    
        except Exception as e:
            logger.error(f"Error scanning for RFID devices: {e}")
    
    async def _connect_device(self, device_id: str) -> bool:
        """Connect to RFID device"""
        try:
            if device_id in self.serial_connections and self.serial_connections[device_id].is_open:
                return True
                
            if device_id not in self.device_info:
                logger.error(f"Unknown RFID device: {device_id}")
                return False
            
            device = self.device_info[device_id]
            port = device['port']
            
            # Try to open serial connection
            connection = serial.Serial(
                port=port,
                baudrate=settings.RFID_BAUDRATE,
                timeout=1,
                write_timeout=1
            )
            
            if connection.is_open:
                self.serial_connections[device_id] = connection
                logger.info(f"Connected to RFID device {device_id} on {port}")
                return True
            else:
                logger.error(f"Failed to open connection to {port}")
                return False
                
        except Exception as e:
            logger.error(f"Failed to connect to RFID device {device_id}: {e}")
            return False
    
    async def _disconnect_device(self, device_id: str):
        """Disconnect from RFID device"""
        try:
            if device_id in self.serial_connections:
                conn = self.serial_connections[device_id]
                if conn and conn.is_open:
                    conn.close()
                del self.serial_connections[device_id]
                logger.info(f"Disconnected from RFID device {device_id}")
        except Exception as e:
            logger.error(f"Error disconnecting from RFID device {device_id}: {e}")
    
    async def _read_card(self, device_id: str, timeout: int = 10) -> Optional[str]:
        """Read RFID card/tag from device"""
        try:
            if not await self._connect_device(device_id):
                return None
                
            connection = self.serial_connections[device_id]
            
            # Wait for card/tag data
            start_time = datetime.now()
            buffer = ""
            
            while (datetime.now() - start_time).seconds < timeout:
                if connection.in_waiting > 0:
                    data = connection.read(connection.in_waiting).decode('utf-8', errors='ignore')
                    buffer += data
                    
                    # Look for common RFID data patterns
                    # Many readers send UID in hex format
                    uid_match = re.search(r'([0-9A-Fa-f]{8,})', buffer)
                    if uid_match:
                        uid = uid_match.group(1).upper()
                        logger.info(f"RFID card detected: {uid}")
                        return uid
                
                await asyncio.sleep(0.1)
            
            logger.warning(f"No RFID card detected within {timeout} seconds")
            return None
            
        except Exception as e:
            logger.error(f"Error reading RFID card from {device_id}: {e}")
            return None
    
    async def enroll(self, employee_id: int, device_id: Optional[str] = None, timeout: int = 10) -> BiometricResponse:
        """Enroll RFID card/tag for employee"""
        start_time = datetime.now()
        
        try:
            if not self.initialized:
                raise RuntimeError("RFID service not initialized")
            
            # Use first available device if not specified
            if not device_id:
                if not self.device_info:
                    return BiometricResponse(
                        success=False,
                        message="No RFID devices available",
                        employee_id=employee_id,
                        biometric_type="rfid"
                    )
                device_id = list(self.device_info.keys())[0]
            
            if SERIAL_AVAILABLE and device_id in self.device_info:
                # Real RFID enrollment
                logger.info(f"Reading RFID card for employee {employee_id} enrollment...")
                
                uid = await self._read_card(device_id, timeout)
                
                if uid:
                    processing_time = int((datetime.now() - start_time).total_seconds() * 1000)
                    
                    return BiometricResponse(
                        success=True,
                        message=f"RFID card enrolled successfully (UID: {uid})",
                        employee_id=employee_id,
                        biometric_type="rfid",
                        template_id=uid,
                        device_id=device_id,
                        processing_time_ms=processing_time
                    )
                else:
                    return BiometricResponse(
                        success=False,
                        message="No RFID card detected within timeout period",
                        employee_id=employee_id,
                        biometric_type="rfid",
                        device_id=device_id
                    )
            else:
                # Mock implementation
                logger.info(f"Mock RFID enrollment for employee {employee_id}")
                
                # Simulate card reading time
                await asyncio.sleep(2)
                
                processing_time = int((datetime.now() - start_time).total_seconds() * 1000)
                mock_uid = f"MOCK{employee_id:04d}{int(datetime.now().timestamp()) % 10000:04d}"
                
                return BiometricResponse(
                    success=True,
                    message=f"Mock RFID card enrolled successfully (UID: {mock_uid})",
                    employee_id=employee_id,
                    biometric_type="rfid",
                    template_id=mock_uid,
                    device_id=device_id or "mock_rfid_device",
                    processing_time_ms=processing_time
                )
                
        except Exception as e:
            logger.error(f"RFID enrollment failed for employee {employee_id}: {e}")
            return BiometricResponse(
                success=False,
                message=f"Enrollment failed: {str(e)}",
                employee_id=employee_id,
                biometric_type="rfid"
            )
    
    async def verify(self, uid: str, device_id: Optional[str] = None) -> BiometricResponse:
        """Verify RFID card/tag"""
        start_time = datetime.now()
        
        try:
            if not self.initialized:
                raise RuntimeError("RFID service not initialized")
            
            # Use first available device if not specified
            if not device_id:
                if not self.device_info:
                    return BiometricResponse(
                        success=False,
                        message="No RFID devices available",
                        biometric_type="rfid"
                    )
                device_id = list(self.device_info.keys())[0]
            
            if SERIAL_AVAILABLE and device_id in self.device_info:
                # Real RFID verification
                logger.info(f"Reading RFID card for verification (expected UID: {uid})...")
                
                detected_uid = await self._read_card(device_id, settings.RFID_TIMEOUT)
                
                if detected_uid:
                    # Compare UIDs
                    match = detected_uid.upper() == uid.upper()
                    confidence = 1.0 if match else 0.0
                    
                    processing_time = int((datetime.now() - start_time).total_seconds() * 1000)
                    
                    return BiometricResponse(
                        success=match,
                        message=f"RFID {'verified' if match else 'verification failed'} (UID: {detected_uid})",
                        biometric_type="rfid",
                        confidence=confidence,
                        device_id=device_id,
                        processing_time_ms=processing_time
                    )
                else:
                    return BiometricResponse(
                        success=False,
                        message="No RFID card detected within timeout period",
                        biometric_type="rfid",
                        device_id=device_id
                    )
            else:
                # Mock implementation
                logger.info(f"Mock RFID verification for UID {uid}")
                
                # Simulate card reading time
                await asyncio.sleep(1)
                
                processing_time = int((datetime.now() - start_time).total_seconds() * 1000)
                
                # Mock verification (90% success rate for testing)
                import random
                success = random.random() > 0.1
                confidence = 0.95 if success else 0.05
                
                return BiometricResponse(
                    success=success,
                    message=f"Mock RFID {'verified' if success else 'verification failed'} (UID: {uid})",
                    biometric_type="rfid",
                    confidence=confidence,
                    device_id=device_id or "mock_rfid_device",
                    processing_time_ms=processing_time
                )
                
        except Exception as e:
            logger.error(f"RFID verification failed for UID {uid}: {e}")
            return BiometricResponse(
                success=False,
                message=f"Verification failed: {str(e)}",
                biometric_type="rfid"
            )
    
    async def get_available_devices(self) -> List[Dict[str, Any]]:
        """Get list of available RFID devices"""
        try:
            await self._scan_devices()  # Refresh device list
            
            devices = []
            for device_id, info in self.device_info.items():
                devices.append({
                    'device_id': device_id,
                    'port': info['port'],
                    'description': info['description'],
                    'manufacturer': info['manufacturer'],
                    'connected': device_id in self.serial_connections and self.serial_connections[device_id].is_open
                })
            
            return devices
            
        except Exception as e:
            logger.error(f"Error getting available RFID devices: {e}")
            return []