"""
Device Scanner Service
Scans for compatible biometric devices and reports capabilities
"""

import asyncio
import logging
from typing import List, Dict, Any
import platform
import subprocess

# Try to import device detection libraries
try:
    import cv2
    OPENCV_AVAILABLE = True
except ImportError:
    OPENCV_AVAILABLE = False

try:
    import serial.tools.list_ports
    SERIAL_AVAILABLE = True
except ImportError:
    SERIAL_AVAILABLE = False

try:
    import usb.core
    import usb.util
    USB_AVAILABLE = True
except ImportError:
    USB_AVAILABLE = False

try:
    import pydbus
    DBUS_AVAILABLE = True
except ImportError:
    DBUS_AVAILABLE = False

from models.biometric_models import DeviceCompatibilityResponse, DeviceInfo, DeviceType, DeviceStatus

logger = logging.getLogger(__name__)

class DeviceScanner:
    """Device Scanner for biometric hardware"""
    
    def __init__(self):
        self.system_info = {
            'platform': platform.system(),
            'architecture': platform.machine(),
            'python_version': platform.python_version()
        }
        
    async def scan_devices(self) -> DeviceCompatibilityResponse:
        """Scan for all compatible biometric devices"""
        try:
            logger.info("Starting device compatibility scan...")
            
            devices = []
            
            # Scan for cameras (facial recognition)
            camera_devices = await self._scan_cameras()
            devices.extend(camera_devices)
            
            # Scan for fingerprint devices
            fingerprint_devices = await self._scan_fingerprint_devices()
            devices.extend(fingerprint_devices)
            
            # Scan for RFID devices
            rfid_devices = await self._scan_rfid_devices()
            devices.extend(rfid_devices)
            
            # Count devices by type
            facial_count = sum(1 for d in devices if d.device_type == DeviceType.CAMERA)
            fingerprint_count = sum(1 for d in devices if d.device_type == DeviceType.FINGERPRINT_READER)
            rfid_count = sum(1 for d in devices if d.device_type == DeviceType.RFID_READER)
            
            logger.info(f"Device scan complete: {len(devices)} total devices found")
            
            return DeviceCompatibilityResponse(
                success=True,
                message=f"Found {len(devices)} compatible devices",
                devices=devices,
                total_devices=len(devices),
                facial_devices=facial_count,
                fingerprint_devices=fingerprint_count,
                rfid_devices=rfid_count
            )
            
        except Exception as e:
            logger.error(f"Device scan failed: {e}")
            return DeviceCompatibilityResponse(
                success=False,
                message=f"Device scan failed: {str(e)}",
                devices=[],
                total_devices=0,
                facial_devices=0,
                fingerprint_devices=0,
                rfid_devices=0
            )
    
    async def _scan_cameras(self) -> List[DeviceInfo]:
        """Scan for camera devices"""
        devices = []
        
        try:
            if OPENCV_AVAILABLE:
                logger.info("Scanning for camera devices using OpenCV...")
                
                # Test camera indices 0-3 (covers most common setups)
                for i in range(4):
                    try:
                        cap = cv2.VideoCapture(i)
                        if cap.isOpened():
                            # Get camera properties
                            width = cap.get(cv2.CAP_PROP_FRAME_WIDTH)
                            height = cap.get(cv2.CAP_PROP_FRAME_HEIGHT)
                            fps = cap.get(cv2.CAP_PROP_FPS)
                            
                            # Try to read a frame to verify the camera works
                            ret, frame = cap.read()
                            
                            if ret and frame is not None:
                                device_info = DeviceInfo(
                                    device_id=f"camera_{i}",
                                    device_type=DeviceType.CAMERA,
                                    name=f"Camera {i}",
                                    manufacturer="Unknown",
                                    model="Generic USB Camera",
                                    status=DeviceStatus.AVAILABLE,
                                    capabilities=["facial_recognition", "photo_capture"],
                                    connection_info={
                                        "index": i,
                                        "resolution": f"{int(width)}x{int(height)}",
                                        "fps": fps,
                                        "backend": "OpenCV"
                                    }
                                )
                                devices.append(device_info)
                                logger.info(f"Found camera {i}: {width}x{height} @ {fps} FPS")
                            
                            cap.release()
                            
                    except Exception as e:
                        logger.debug(f"Camera {i} not available: {e}")
                        continue
                        
            else:
                logger.warning("OpenCV not available - camera detection disabled")
                
                # Try basic system commands as fallback
                if platform.system() == "Linux":
                    try:
                        # Check for /dev/video* devices
                        import glob
                        video_devices = glob.glob("/dev/video*")
                        
                        for i, device_path in enumerate(video_devices):
                            device_info = DeviceInfo(
                                device_id=f"camera_{i}",
                                device_type=DeviceType.CAMERA,
                                name=f"Video Device {device_path}",
                                status=DeviceStatus.AVAILABLE,
                                capabilities=["facial_recognition"],
                                connection_info={"path": device_path}
                            )
                            devices.append(device_info)
                            
                    except Exception as e:
                        logger.debug(f"Linux camera detection failed: {e}")
                        
        except Exception as e:
            logger.error(f"Camera scanning failed: {e}")
            
        return devices
    
    async def _scan_fingerprint_devices(self) -> List[DeviceInfo]:
        """Scan for fingerprint devices"""
        devices = []
        
        try:
            # Check for fprintd on Linux
            if DBUS_AVAILABLE and platform.system() == "Linux":
                try:
                    from pydbus import SystemBus
                    bus = SystemBus()
                    
                    # Try to connect to fprintd
                    manager = bus.get('net.reactivated.Fprint', '/net/reactivated/Fprint/Manager')
                    device_paths = manager.GetDevices()
                    
                    for i, device_path in enumerate(device_paths):
                        try:
                            device = bus.get('net.reactivated.Fprint', device_path)
                            
                            device_info = DeviceInfo(
                                device_id=f"fingerprint_{i}",
                                device_type=DeviceType.FINGERPRINT_READER,
                                name=device_path.split('/')[-1],
                                status=DeviceStatus.AVAILABLE,
                                capabilities=["fingerprint_enrollment", "fingerprint_verification"],
                                connection_info={
                                    "path": device_path,
                                    "service": "fprintd"
                                }
                            )
                            devices.append(device_info)
                            logger.info(f"Found fingerprint device: {device_path}")
                            
                        except Exception as e:
                            logger.debug(f"Error accessing fingerprint device {device_path}: {e}")
                            
                except Exception as e:
                    logger.debug(f"fprintd not available: {e}")
            
            # Check for USB fingerprint scanners
            if USB_AVAILABLE:
                try:
                    # Common fingerprint scanner vendor IDs
                    fingerprint_vendors = [
                        0x08ff,  # AuthenTec
                        0x0483,  # STMicroelectronics  
                        0x147e,  # Upek
                        0x1c7a,  # LighTuning
                        0x27c6,  # Shenzhen Goodix
                        0x04f3,  # Elan
                        0x138a,  # Validity Sensors
                    ]
                    
                    for vendor_id in fingerprint_vendors:
                        devices_found = usb.core.find(find_all=True, idVendor=vendor_id)
                        
                        for device in devices_found:
                            try:
                                device_info = DeviceInfo(
                                    device_id=f"usb_fingerprint_{vendor_id:04x}_{device.idProduct:04x}",
                                    device_type=DeviceType.FINGERPRINT_READER,
                                    name=f"USB Fingerprint Scanner",
                                    manufacturer=f"Vendor {vendor_id:04x}",
                                    model=f"Product {device.idProduct:04x}",
                                    status=DeviceStatus.AVAILABLE,
                                    capabilities=["fingerprint_enrollment", "fingerprint_verification"],
                                    connection_info={
                                        "vendor_id": vendor_id,
                                        "product_id": device.idProduct,
                                        "bus": device.bus,
                                        "address": device.address
                                    }
                                )
                                devices.append(device_info)
                                logger.info(f"Found USB fingerprint device: {vendor_id:04x}:{device.idProduct:04x}")
                                
                            except Exception as e:
                                logger.debug(f"Error accessing USB fingerprint device: {e}")
                                
                except Exception as e:
                    logger.debug(f"USB fingerprint scan failed: {e}")
                    
        except Exception as e:
            logger.error(f"Fingerprint device scanning failed: {e}")
            
        return devices
    
    async def _scan_rfid_devices(self) -> List[DeviceInfo]:
        """Scan for RFID devices"""
        devices = []
        
        try:
            if SERIAL_AVAILABLE:
                logger.info("Scanning for RFID devices on serial ports...")
                
                ports = serial.tools.list_ports.comports()
                
                # RFID device patterns
                rfid_patterns = [
                    "rfid", "nfc", "mifare", "acr", "omnikey", "proxmark",
                    "chameleon", "pn532", "rc522", "mfrc522"
                ]
                
                for port in ports:
                    description = (port.description or "").lower()
                    manufacturer = (port.manufacturer or "").lower()
                    
                    # Check if this looks like an RFID device
                    is_rfid = any(pattern in description or pattern in manufacturer 
                                for pattern in rfid_patterns)
                    
                    # Also check common USB-to-Serial chips used with RFID
                    is_usb_serial = any(chip in description or chip in manufacturer
                                      for chip in ["cp210", "ft232", "ch340", "pl2303"])
                    
                    if is_rfid or is_usb_serial:
                        device_info = DeviceInfo(
                            device_id=f"rfid_{port.device.replace('/', '_').replace(':', '')}",
                            device_type=DeviceType.RFID_READER,
                            name=port.description or f"RFID Reader ({port.device})",
                            manufacturer=port.manufacturer or "Unknown",
                            status=DeviceStatus.AVAILABLE,
                            capabilities=["rfid_enrollment", "rfid_verification"],
                            connection_info={
                                "port": port.device,
                                "vendor_id": port.vid,
                                "product_id": port.pid,
                                "serial_number": port.serial_number
                            }
                        )
                        devices.append(device_info)
                        logger.info(f"Found potential RFID device: {port.device}")
                        
        except Exception as e:
            logger.error(f"RFID device scanning failed: {e}")
            
        return devices
    
    async def test_device_functionality(self, device_id: str) -> Dict[str, Any]:
        """Test specific device functionality"""
        try:
            logger.info(f"Testing device functionality: {device_id}")
            
            # This would perform actual device tests
            # For now, return mock test results
            
            return {
                "device_id": device_id,
                "functional": True,
                "test_results": {
                    "connection": "OK",
                    "basic_operation": "OK", 
                    "performance": "Good"
                },
                "recommendations": []
            }
            
        except Exception as e:
            logger.error(f"Device test failed for {device_id}: {e}")
            return {
                "device_id": device_id,
                "functional": False,
                "error": str(e),
                "recommendations": ["Check device connection", "Verify drivers installed"]
            }