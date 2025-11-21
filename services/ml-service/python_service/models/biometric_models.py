"""
Pydantic models for biometric service API
"""

from pydantic import BaseModel, Field
from typing import Optional, List, Dict, Any
from enum import Enum

class BiometricType(str, Enum):
    """Biometric type enumeration"""
    FACIAL = "facial"
    FINGERPRINT = "fingerprint"
    RFID = "rfid"

class FingerType(str, Enum):
    """Finger type enumeration"""
    THUMB_RIGHT = "thumb_right"
    INDEX_RIGHT = "index_right"
    MIDDLE_RIGHT = "middle_right"
    RING_RIGHT = "ring_right"
    PINKY_RIGHT = "pinky_right"
    THUMB_LEFT = "thumb_left"
    INDEX_LEFT = "index_left"
    MIDDLE_LEFT = "middle_left"
    RING_LEFT = "ring_left"
    PINKY_LEFT = "pinky_left"

class DeviceType(str, Enum):
    """Device type enumeration"""
    CAMERA = "camera"
    FINGERPRINT_READER = "fingerprint_reader"
    RFID_READER = "rfid_reader"

class DeviceStatus(str, Enum):
    """Device status enumeration"""
    AVAILABLE = "available"
    BUSY = "busy"
    ERROR = "error"
    NOT_FOUND = "not_found"

# Base request/response models
class BaseResponse(BaseModel):
    """Base response model"""
    success: bool
    message: str
    timestamp: str = Field(default_factory=lambda: __import__('datetime').datetime.now().isoformat())

class BiometricResponse(BaseResponse):
    """Biometric operation response"""
    employee_id: Optional[int] = None
    biometric_type: Optional[BiometricType] = None
    confidence: Optional[float] = None
    quality_score: Optional[float] = None
    template_id: Optional[str] = None
    device_id: Optional[str] = None
    processing_time_ms: Optional[int] = None

# Device models
class DeviceInfo(BaseModel):
    """Device information model"""
    device_id: str
    device_type: DeviceType
    name: str
    manufacturer: Optional[str] = None
    model: Optional[str] = None
    status: DeviceStatus
    capabilities: List[str] = []
    connection_info: Dict[str, Any] = {}

class DeviceCompatibilityResponse(BaseResponse):
    """Device compatibility scan response"""
    devices: List[DeviceInfo] = []
    total_devices: int = 0
    facial_devices: int = 0
    fingerprint_devices: int = 0
    rfid_devices: int = 0

# Facial recognition models
class FacialEnrollRequest(BaseModel):
    """Facial enrollment request"""
    employee_id: int
    image_data: str = Field(..., description="Base64 encoded image data")
    quality_threshold: float = Field(0.5, ge=0.0, le=1.0)

class FacialEnrollMultipleRequest(BaseModel):
    """Facial enrollment request with multiple images"""
    employee_id: int
    images: List[str] = Field(..., description="List of base64 encoded image data")
    quality_threshold: float = Field(0.5, ge=0.0, le=1.0)
    session_id: Optional[str] = Field(None, description="Session identifier for tracking")

class FacialVerifyRequest(BaseModel):
    """Facial verification request"""
    employee_id: int
    image_data: str = Field(..., description="Base64 encoded image data")
    confidence_threshold: float = Field(0.85, ge=0.0, le=1.0, description="Confidence threshold for face verification (higher = more strict)")

class FacialIdentifyRequest(BaseModel):
    """Facial identification request for automatic employee recognition"""
    image_data: str = Field(..., description="Base64 encoded image data")
    company_id: int = Field(..., description="Company ID to filter employees")
    confidence_threshold: float = Field(0.85, ge=0.0, le=1.0, description="Confidence threshold for identification (higher = more strict)")

class FacialExtractRequest(BaseModel):
    """Facial embedding extraction request"""
    image_data: str = Field(..., description="Base64 encoded image data")

class FacialEmbeddingResponse(BaseResponse):
    """Facial embedding response"""
    embedding: List[float] = []
    quality_score: float = 0.0
    face_detected: bool = False
    face_count: int = 0

class EmployeeCandidate(BaseModel):
    """Employee candidate for identification"""
    employee_id: int
    full_name: str
    dni: str
    establishment_name: str
    establishment_id: int
    confidence: float

class EmployeeIdentificationResponse(BaseResponse):
    """Employee identification response"""
    employee_id: Optional[int] = None
    employee_name: Optional[str] = None
    dni: Optional[str] = None
    establishment_name: Optional[str] = None
    establishment_id: Optional[int] = None
    confidence: float = 0.0
    quality_score: Optional[float] = None
    processing_time_ms: Optional[int] = None
    candidates: List[EmployeeCandidate] = []

# Fingerprint models
class FingerprintEnrollRequest(BaseModel):
    """Fingerprint enrollment request"""
    employee_id: int
    finger_type: FingerType
    device_id: Optional[str] = None
    quality_threshold: float = Field(0.6, ge=0.0, le=1.0)

class FingerprintVerifyRequest(BaseModel):
    """Fingerprint verification request"""
    employee_id: int
    finger_type: Optional[FingerType] = None
    device_id: Optional[str] = None
    confidence_threshold: float = Field(0.7, ge=0.0, le=1.0)

# RFID models
class RFIDEnrollRequest(BaseModel):
    """RFID enrollment request"""
    employee_id: int
    device_id: Optional[str] = None
    timeout: int = Field(10, ge=1, le=60, description="Timeout in seconds")

class RFIDVerifyRequest(BaseModel):
    """RFID verification request"""
    uid: str = Field(..., description="RFID card/tag UID")
    device_id: Optional[str] = None

# Error models
class ErrorResponse(BaseResponse):
    """Error response model"""
    error_code: str
    error_details: Dict[str, Any] = {}

# Batch operation models
class BatchEnrollRequest(BaseModel):
    """Batch enrollment request"""
    employee_id: int
    operations: List[Dict[str, Any]]

class BatchEnrollResponse(BaseResponse):
    """Batch enrollment response"""
    results: List[BiometricResponse] = []
    successful_operations: int = 0
    failed_operations: int = 0

# Statistics models
class BiometricStats(BaseModel):
    """Biometric statistics model"""
    total_enrollments: int = 0
    total_verifications: int = 0
    success_rate: float = 0.0
    average_confidence: float = 0.0
    device_usage: Dict[str, int] = {}
    daily_stats: List[Dict[str, Any]] = []

class PhotoCaptureResponse(BaseResponse):
    """Photo capture response model"""
    filename: Optional[str] = None
    file_path: Optional[str] = None
    file_size: Optional[int] = None
    image_width: Optional[int] = None
    image_height: Optional[int] = None
    capture_timestamp: Optional[str] = None