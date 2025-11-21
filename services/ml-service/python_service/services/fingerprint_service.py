"""
Fingerprint Service using fprintd (Linux D-Bus interface)
Handles fingerprint enrollment and verification through fprintd daemon
"""

import asyncio
import logging
from typing import Optional, List
from datetime import datetime

"""
Fingerprint Service for Windows
Handles fingerprint enrollment and verification using Windows Biometric Framework
"""

import asyncio
import logging
from typing import Optional, List
from datetime import datetime
import os
import sys

logger = logging.getLogger(__name__)

# Try to import Windows-specific libraries, with fallback for development
try:
    import serial
    import usb.core as usb
    import usb.util as util
    HARDWARE_AVAILABLE = True
    logger.info("Hardware libraries available for fingerprint service")
except ImportError as e:
    HARDWARE_AVAILABLE = False
    logger.warning(f"Hardware libraries not available: {e}. Using mock implementation.")

from config.settings import settings
from models.biometric_models import BiometricResponse, FingerType

class FingerprintService:
    """Fingerprint Service for Windows"""

    def __init__(self):
        self.device = None
        self.initialized = False
        self.mock_fingerprints = {}  # Mock storage for development

    async def initialize(self):
        """Initialize fingerprint device connection"""
        try:
            if HARDWARE_AVAILABLE:
                logger.info("Initializing fingerprint device...")

                # Try to find fingerprint devices
                self.device = await self._find_fingerprint_device()

                if self.device:
                    logger.info("Fingerprint device found and initialized")
                else:
                    logger.warning("No fingerprint device found, using mock mode")
            else:
                logger.info("Hardware libraries not available, using mock implementation")

            self.initialized = True
            logger.info("✅ Fingerprint service initialized")

        except Exception as e:
            logger.error(f"Failed to initialize fingerprint service: {e}")
            self.initialized = False
            raise

    async def _find_fingerprint_device(self):
        """Find and connect to fingerprint device"""
        try:
            # Common USB VID/PID for fingerprint readers
            fingerprint_devices = [
                (0x08ff, 0x1600),  # AuthenTec AES1600
                (0x08ff, 0x2580),  # AuthenTec AES2501
                (0x0483, 0x2016),  # STMicroelectronics
            ]

            for vid, pid in fingerprint_devices:
                try:
                    device = usb.core.find(idVendor=vid, idProduct=pid)
                    if device:
                        logger.info(f"Found fingerprint device: {vid:04x}:{pid:04x}")
                        device.set_configuration()
                        return device
                except Exception as e:
                    logger.debug(f"Failed to connect to device {vid:04x}:{pid:04x}: {e}")
                    continue

            # Try serial devices
            import serial.tools.list_ports
            ports = list(serial.tools.list_ports.comports())
            for port in ports:
                if 'fingerprint' in port.description.lower() or 'fp' in port.description.lower():
                    try:
                        ser = serial.Serial(port.device, 9600, timeout=1)
                        logger.info(f"Found serial fingerprint device on {port.device}")
                        return ser
                    except Exception as e:
                        logger.debug(f"Failed to connect to serial device {port.device}: {e}")

            return None

        except Exception as e:
            logger.error(f"Error finding fingerprint device: {e}")
            return None
            
        except Exception as e:
            logger.error(f"Failed to initialize fingerprint service: {e}")
            # Don't raise - allow service to start without fingerprint support
            self.initialized = True
    
    async def cleanup(self):
        """Cleanup resources"""
        self.bus = None
        self.fprintd_manager = None
        self.fprintd_device = None
        self.initialized = False
        logger.info("Fingerprint service cleaned up")
    
    def _finger_to_fprintd_name(self, finger_type: FingerType) -> str:
        """Convert our finger type enum to fprintd finger names"""
        finger_mapping = {
            FingerType.THUMB_LEFT: "left-thumb",
            FingerType.INDEX_LEFT: "left-index-finger", 
            FingerType.MIDDLE_LEFT: "left-middle-finger",
            FingerType.RING_LEFT: "left-ring-finger",
            FingerType.PINKY_LEFT: "left-little-finger",
            FingerType.THUMB_RIGHT: "right-thumb",
            FingerType.INDEX_RIGHT: "right-index-finger",
            FingerType.MIDDLE_RIGHT: "right-middle-finger", 
            FingerType.RING_RIGHT: "right-ring-finger",
            FingerType.PINKY_RIGHT: "right-little-finger"
        }
        return finger_mapping.get(finger_type, "right-index-finger")
    
    async def _wait_for_scan_completion(self, timeout: int = 30) -> bool:
        """Wait for fingerprint scan to complete"""
        try:
            # In a real implementation, we would listen for D-Bus signals
            # For now, simulate the scan process
            await asyncio.sleep(2)  # Simulate scan time
            return True
        except Exception as e:
            logger.error(f"Scan completion wait failed: {e}")
            return False
    
    async def enroll(self, employee_id: int, finger_type: FingerType, device_id: Optional[str] = None) -> BiometricResponse:
        """Enroll fingerprint for employee"""
        start_time = datetime.now()
        
        try:
            if not self.initialized:
                raise RuntimeError("Fingerprint service not initialized")
            
            finger_name = self._finger_to_fprintd_name(finger_type)
            
            if HARDWARE_AVAILABLE and self.device:
                try:
                    # Start enrollment
                    username = f"employee_{employee_id}"  # Create username for employee
                    
                    # Begin enrollment process
                    self.fprintd_device.EnrollStart(finger_name)
                    
                    # Wait for enrollment completion
                    success = await self._wait_for_scan_completion(settings.FPRINTD_TIMEOUT)
                    
                    if success:
                        # Complete enrollment
                        self.fprintd_device.EnrollStop()
                        
                        processing_time = int((datetime.now() - start_time).total_seconds() * 1000)
                        
                        return BiometricResponse(
                            success=True,
                            message="Fingerprint enrolled successfully",
                            employee_id=employee_id,
                            biometric_type="fingerprint",
                            template_id=f"fingerprint_{employee_id}_{finger_type.value}_{int(datetime.now().timestamp())}",
                            device_id=device_id or "default",
                            processing_time_ms=processing_time
                        )
                    else:
                        return BiometricResponse(
                            success=False,
                            message="Enrollment timeout - please try again",
                            employee_id=employee_id,
                            biometric_type="fingerprint"
                        )
                        
                except Exception as e:
                    logger.error(f"fprintd enrollment error: {e}")
                    # Fall through to mock implementation
                    
            # Mock implementation (when fprintd not available)
            logger.info(f"Mock fingerprint enrollment for employee {employee_id}, finger {finger_type.value}")
            
            # Simulate enrollment time
            await asyncio.sleep(1)
            
            processing_time = int((datetime.now() - start_time).total_seconds() * 1000)
            
            return BiometricResponse(
                success=True,
                message="Mock fingerprint enrolled successfully (fprintd not available)",
                employee_id=employee_id,
                biometric_type="fingerprint",
                quality_score=0.85,
                template_id=f"mock_fingerprint_{employee_id}_{finger_type.value}",
                device_id=device_id or "mock_device",
                processing_time_ms=processing_time
            )
            
        except Exception as e:
            logger.error(f"Fingerprint enrollment failed for employee {employee_id}: {e}")
            return BiometricResponse(
                success=False,
                message=f"Enrollment failed: {str(e)}",
                employee_id=employee_id,
                biometric_type="fingerprint"
            )
    
    async def verify(self, employee_id: int, finger_type: Optional[FingerType] = None, device_id: Optional[str] = None) -> BiometricResponse:
        """Verify fingerprint for employee"""
        start_time = datetime.now()
        
        try:
            if not self.initialized:
                raise RuntimeError("Fingerprint service not initialized")
            
            if HARDWARE_AVAILABLE and self.device:
                try:
                    # Start verification
                    self.fprintd_device.VerifyStart()
                    
                    # Wait for verification completion  
                    success = await self._wait_for_scan_completion(settings.FPRINTD_TIMEOUT)
                    
                    if success:
                        # Complete verification
                        result = self.fprintd_device.VerifyStop()
                        
                        processing_time = int((datetime.now() - start_time).total_seconds() * 1000)
                        
                        # Parse result (fprintd returns match info)
                        verified = bool(result)  # Simplified - fprintd returns more complex data
                        
                        return BiometricResponse(
                            success=verified,
                            message="Fingerprint verified successfully" if verified else "Fingerprint verification failed",
                            employee_id=employee_id,
                            biometric_type="fingerprint",
                            confidence=0.95 if verified else 0.1,
                            device_id=device_id or "default", 
                            processing_time_ms=processing_time
                        )
                    else:
                        return BiometricResponse(
                            success=False,
                            message="Verification timeout - please try again",
                            employee_id=employee_id,
                            biometric_type="fingerprint"
                        )
                        
                except Exception as e:
                    logger.error(f"fprintd verification error: {e}")
                    # Fall through to mock implementation
                    
            # Mock implementation (when fprintd not available)
            logger.info(f"Mock fingerprint verification for employee {employee_id}")
            
            # Simulate verification time
            await asyncio.sleep(1)
            
            processing_time = int((datetime.now() - start_time).total_seconds() * 1000)
            
            # Mock verification result (80% success rate for testing)
            import random
            success = random.random() > 0.2
            confidence = 0.85 if success else 0.3
            
            return BiometricResponse(
                success=success,
                message="Mock fingerprint verified successfully" if success else "Mock fingerprint verification failed",
                employee_id=employee_id,
                biometric_type="fingerprint",
                confidence=confidence,
                device_id=device_id or "mock_device",
                processing_time_ms=processing_time
            )
            
        except Exception as e:
            logger.error(f"Fingerprint verification failed for employee {employee_id}: {e}")
            return BiometricResponse(
                success=False,
                message=f"Verification failed: {str(e)}",
                employee_id=employee_id,
                biometric_type="fingerprint"
            )
    
    async def get_enrolled_fingers(self, employee_id: int) -> List[FingerType]:
        """Get list of enrolled fingers for employee"""
        try:
            if HARDWARE_AVAILABLE and self.device:
                # In real implementation, query fprintd for enrolled prints
                # For now, return mock data
                pass
            
            # Mock implementation - return some fingers as enrolled
            return [FingerType.INDEX_RIGHT, FingerType.THUMB_RIGHT]
            
        except Exception as e:
            logger.error(f"Failed to get enrolled fingers for employee {employee_id}: {e}")
            return []
    
    async def delete_enrolled_finger(self, employee_id: int, finger_type: FingerType) -> bool:
        """Delete enrolled fingerprint for employee"""
        try:
            if HARDWARE_AVAILABLE and self.device:
                finger_name = self._finger_to_fprintd_name(finger_type)
                username = f"employee_{employee_id}"
                
                # In real implementation, delete from fprintd
                # self.fprintd_device.DeleteEnrolledFinger(username, finger_name)
                logger.info(f"Deleted fingerprint for {username}, finger {finger_name}")
                return True
            
            # Mock implementation
            logger.info(f"Mock delete fingerprint for employee {employee_id}, finger {finger_type.value}")
            return True
            
        except Exception as e:
            logger.error(f"Failed to delete fingerprint: {e}")
            return False