#!/usr/bin/env python3
"""
SNKTIME Biometric Microservice
FastAPI application for biometric enrollment and verification
Supports: InsightFace facial recognition, fprintd fingerprints, RFID readers

Para diagnosticar problemas, ejecuta: python test_service.py
"""

from fastapi import FastAPI, HTTPException, UploadFile, File
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Optional, List, Dict, Any
import logging
import os
import sys
from contextlib import asynccontextmanager
from datetime import datetime

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(name)s - %(levelname)s - %(message)s"
)
logger = logging.getLogger(__name__)

# Import service modules with error handling
try:
    from services.facial_service import FacialRecognitionService
    FACIAL_SERVICE_AVAILABLE = True
except ImportError as e:
    logger.warning(f"Facial service not available: {e}")
    FacialRecognitionService = None
    FACIAL_SERVICE_AVAILABLE = False

try:
    from services.fingerprint_service import FingerprintService
    FINGERPRINT_SERVICE_AVAILABLE = True
except ImportError as e:
    logger.warning(f"Fingerprint service not available: {e}")
    FingerprintService = None
    FINGERPRINT_SERVICE_AVAILABLE = False

try:
    from services.rfid_service import RFIDService
    RFID_SERVICE_AVAILABLE = True
except ImportError as e:
    logger.warning(f"RFID service not available: {e}")
    RFIDService = None
    RFID_SERVICE_AVAILABLE = False

try:
    from services.device_scanner import DeviceScanner
    DEVICE_SCANNER_AVAILABLE = True
except ImportError as e:
    logger.warning(f"Device scanner not available: {e}")
    DeviceScanner = None
    DEVICE_SCANNER_AVAILABLE = False

try:
    from models.biometric_models import *
    MODELS_AVAILABLE = True
except ImportError as e:
    logger.warning(f"Biometric models not available: {e}")
    MODELS_AVAILABLE = False

    # Fallback models if not available
    class BiometricResponse(BaseModel):
        success: bool
        message: str
        confidence: Optional[float] = None
        employee_id: Optional[int] = None
        biometric_type: Optional[str] = None
        timestamp: Optional[str] = None

    class FacialVerificationRequest(BaseModel):  # Deprecated - use FacialVerifyRequest instead
        employee_id: int
        image: str  # Base64 encoded image

    class FingerprintVerificationRequest(BaseModel):  # Deprecated - use FingerprintVerifyRequest instead
        employee_id: int
        fingerprint_data: str
        device_id: Optional[str] = "python_service"

    class RFIDVerificationRequest(BaseModel):  # Deprecated - use RFIDVerifyRequest instead
        employee_id: int
        uid: str
        device_id: Optional[str] = "python_service"

try:
    from config.settings import settings
    SETTINGS_AVAILABLE = True
except ImportError as e:
    logger.warning(f"Settings not available: {e}")
    SETTINGS_AVAILABLE = False
    # Fallback settings
    class FallbackSettings:
        HOST = "127.0.0.1"
        PORT = 8000
        DEBUG = True
        CORS_ORIGINS = ["*"]
    settings = FallbackSettings()

# Global services
facial_service = None
fingerprint_service = None
rfid_service = None
device_scanner = None

@asynccontextmanager
async def lifespan(app: FastAPI):
    """Initialize and cleanup services"""
    global facial_service, fingerprint_service, rfid_service, device_scanner

    logger.info("Initializing SNKTIME Biometric Microservice...")

    try:
        # Initialize facial service if available
        if FACIAL_SERVICE_AVAILABLE and FacialRecognitionService:
            try:
                logger.info("Initializing facial recognition service...")
                facial_service = FacialRecognitionService()
                await facial_service.initialize()
                logger.info("✅ Facial recognition service initialized")
            except Exception as e:
                logger.error(f"❌ Failed to initialize facial service: {e}")
                facial_service = None
        else:
            logger.warning("⚠️ Facial recognition service not available")

        # Initialize fingerprint service if available
        if FINGERPRINT_SERVICE_AVAILABLE and FingerprintService:
            try:
                logger.info("Initializing fingerprint service...")
                fingerprint_service = FingerprintService()
                logger.info("✅ Fingerprint service initialized")
            except Exception as e:
                logger.error(f"❌ Failed to initialize fingerprint service: {e}")
                fingerprint_service = None
        else:
            logger.warning("⚠️ Fingerprint service not available")

        # Initialize RFID service if available
        if RFID_SERVICE_AVAILABLE and RFIDService:
            try:
                logger.info("Initializing RFID service...")
                rfid_service = RFIDService()
                logger.info("✅ RFID service initialized")
            except Exception as e:
                logger.error(f"❌ Failed to initialize RFID service: {e}")
                rfid_service = None
        else:
            logger.warning("⚠️ RFID service not available")

        # Initialize device scanner if available
        if DEVICE_SCANNER_AVAILABLE and DeviceScanner:
            try:
                logger.info("Initializing device scanner...")
                device_scanner = DeviceScanner()
                logger.info("✅ Device scanner initialized")
            except Exception as e:
                logger.error(f"❌ Failed to initialize device scanner: {e}")
                device_scanner = None
        else:
            logger.warning("⚠️ Device scanner not available")

        logger.info("🎉 Service initialization completed")

    except Exception as e:
        logger.error(f"❌ Error during service initialization: {e}")

    yield

    # Cleanup
    logger.info("Shutting down services...")
    # Add cleanup logic here if needed

# Create FastAPI app
app = FastAPI(
    title="SNKTIME Biometric Service",
    description="Microservice for biometric enrollment and verification",
    version="1.0.0",
    lifespan=lifespan
)

# Configure CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=settings.CORS_ORIGINS,
    allow_credentials=True,
    allow_methods=["GET", "POST", "PUT", "DELETE", "OPTIONS"],
    allow_headers=["*"],
    expose_headers=["*"],
    max_age=86400,  # Cache preflight for 24 hours
)

# Health check endpoint
@app.get("/health")
async def health_check():
    """Health check endpoint"""
    return {
        "status": "healthy",
        "service": "SNKTIME Biometric Service",
        "version": "1.0.0"
    }


@app.get("/healthz")
async def health_check_alias():
    """Kubernetes-style health endpoint alias"""
    return await health_check()


# CORS preflight handler for all routes
@app.options("/{path:path}")
async def handle_options(path: str):
    """Handle CORS preflight requests"""
    return {"message": "OK"}

# Device compatibility endpoint
@app.get("/devices/scan", response_model=DeviceCompatibilityResponse)
async def scan_devices():
    """Scan for compatible biometric devices"""
    try:
        result = await device_scanner.scan_devices()
        return result
    except Exception as e:
        logger.error(f"Device scan failed: {e}")
        raise HTTPException(status_code=500, detail=f"Device scan failed: {str(e)}")

# Facial recognition endpoints
@app.post("/facial/enroll", response_model=BiometricResponse)
async def enroll_facial(request: FacialEnrollRequest):
    """Enroll facial biometric data"""
    try:
        result = await facial_service.enroll(
            employee_id=request.employee_id,
            image_data=request.image_data,
            quality_threshold=request.quality_threshold
        )
        return result
    except Exception as e:
        logger.error(f"Facial enrollment failed: {e}")
        raise HTTPException(status_code=400, detail=f"Facial enrollment failed: {str(e)}")

@app.post("/facial/enroll-multiple", response_model=BiometricResponse)
async def enroll_facial_multiple(request: FacialEnrollMultipleRequest):
    """Enroll facial biometric data using multiple images"""
    try:
        logger.info(f"Enrolling employee {request.employee_id} with {len(request.images)} images")
        result = await facial_service.enroll_multiple(
            employee_id=request.employee_id,
            images=request.images,
            quality_threshold=request.quality_threshold
        )
        return result
    except Exception as e:
        logger.error(f"Facial multiple enrollment failed: {e}")
        raise HTTPException(status_code=400, detail=f"Facial multiple enrollment failed: {str(e)}")

@app.post("/facial/verify", response_model=BiometricResponse)
async def verify_facial(request: FacialVerifyRequest):
    """Verify facial biometric data"""
    try:
        result = await facial_service.verify(
            employee_id=request.employee_id,
            image_data=request.image_data,
            confidence_threshold=request.confidence_threshold
        )
        return result
    except Exception as e:
        logger.error(f"Facial verification failed: {e}")
        raise HTTPException(status_code=400, detail=f"Facial verification failed: {str(e)}")

@app.post("/facial/extract", response_model=FacialEmbeddingResponse)
async def extract_facial_embedding(request: FacialExtractRequest):
    """Extract facial embedding from image"""
    try:
        result = await facial_service.extract_embedding(request.image_data)
        return result
    except Exception as e:
        logger.error(f"Facial embedding extraction failed: {e}")
        raise HTTPException(status_code=400, detail=f"Embedding extraction failed: {str(e)}")

@app.post("/facial/identify", response_model=EmployeeIdentificationResponse)
async def identify_employee_facial(request: FacialIdentifyRequest):
    """Identify employee automatically using facial recognition"""
    try:
        if not facial_service:
            raise HTTPException(status_code=503, detail="Facial recognition service not available")
        
        logger.info(f"Identifying employee for company {request.company_id} with confidence threshold {request.confidence_threshold}")
        
        result = await facial_service.identify_employee(
            image_data=request.image_data,
            company_id=request.company_id,
            confidence_threshold=request.confidence_threshold
        )
        
        return result
    except Exception as e:
        logger.error(f"Employee identification failed: {e}")
        raise HTTPException(status_code=400, detail=f"Employee identification failed: {str(e)}")

# Fingerprint endpoints
@app.post("/fingerprint/enroll", response_model=BiometricResponse)
async def enroll_fingerprint(request: FingerprintEnrollRequest):
    """Enroll fingerprint biometric data"""
    try:
        result = await fingerprint_service.enroll(
            employee_id=request.employee_id,
            finger_type=request.finger_type,
            device_id=request.device_id
        )
        return result
    except Exception as e:
        logger.error(f"Fingerprint enrollment failed: {e}")
        raise HTTPException(status_code=400, detail=f"Fingerprint enrollment failed: {str(e)}")

@app.post("/fingerprint/verify", response_model=BiometricResponse)
async def verify_fingerprint(request: FingerprintVerifyRequest):
    """Verify fingerprint biometric data"""
    try:
        result = await fingerprint_service.verify(
            employee_id=request.employee_id,
            finger_type=request.finger_type,
            device_id=request.device_id
        )
        return result
    except Exception as e:
        logger.error(f"Fingerprint verification failed: {e}")
        raise HTTPException(status_code=400, detail=f"Fingerprint verification failed: {str(e)}")

# RFID endpoints
@app.post("/rfid/enroll", response_model=BiometricResponse)
async def enroll_rfid(request: RFIDEnrollRequest):
    """Enroll RFID card/tag"""
    try:
        result = await rfid_service.enroll(
            employee_id=request.employee_id,
            device_id=request.device_id,
            timeout=request.timeout
        )
        return result
    except Exception as e:
        logger.error(f"RFID enrollment failed: {e}")
        raise HTTPException(status_code=400, detail=f"RFID enrollment failed: {str(e)}")

@app.post("/rfid/verify", response_model=BiometricResponse)
async def verify_rfid(request: RFIDVerifyRequest):
    """Verify RFID card/tag"""
    try:
        result = await rfid_service.verify(
            uid=request.uid,
            device_id=request.device_id
        )
        return result
    except Exception as e:
        logger.error(f"RFID verification failed: {e}")
        raise HTTPException(status_code=400, detail=f"RFID verification failed: {str(e)}")

# Attendance verification endpoints
@app.post("/attendance/verify-facial", response_model=BiometricResponse)
async def verify_facial_attendance(request: FacialVerifyRequest):
    """Verify facial attendance for an employee"""
    try:
        if not facial_service:
            raise HTTPException(status_code=503, detail="Facial recognition service not available")

        # Use the facial service's verify method which handles everything
        result = await facial_service.verify(
            request.employee_id,
            request.image_data,
            request.confidence_threshold
        )

        return result

    except Exception as e:
        logger.error(f"Facial attendance verification failed: {e}")
        raise HTTPException(status_code=400, detail=f"Facial verification failed: {str(e)}")

@app.post("/attendance/verify-fingerprint", response_model=BiometricResponse)
async def verify_fingerprint_attendance(request: FingerprintVerifyRequest):
    """Verify fingerprint attendance for an employee"""
    try:
        if not fingerprint_service:
            raise HTTPException(status_code=503, detail="Fingerprint service not available")

        # Get stored biometric data for the employee
        from services.database_service import DatabaseService
        db_service = DatabaseService()

        stored_data = db_service.get_biometric_data(request.employee_id, "fingerprint")
        if not stored_data:
            raise HTTPException(status_code=404, detail="No fingerprint biometric data found for this employee")

        # Verify fingerprint
        result = await fingerprint_service.verify(
            fingerprint_data=request.fingerprint_data,
            stored_template=stored_data['template_data'],
            device_id=request.device_id
        )

        return result

    except Exception as e:
        logger.error(f"Fingerprint attendance verification failed: {e}")
        raise HTTPException(status_code=400, detail=f"Fingerprint verification failed: {str(e)}")

@app.post("/attendance/verify-rfid", response_model=BiometricResponse)
async def verify_rfid_attendance(request: RFIDVerifyRequest):
    """Verify RFID attendance for an employee"""
    try:
        if not rfid_service:
            raise HTTPException(status_code=503, detail="RFID service not available")

        # For RFID, we need to find the employee by UID first
        # This is a simplified approach - in production you'd have a proper RFID lookup
        from services.database_service import DatabaseService
        db_service = DatabaseService()

        # Try to find employee with this RFID UID in biometric data
        connection = db_service._get_connection()
        with connection.cursor() as cursor:
            cursor.execute("""
                SELECT eb.employee_id, e.NOMBRE, e.APELLIDO
                FROM employee_biometrics eb
                JOIN empleado e ON eb.employee_id = e.ID_EMPLEADO
                WHERE eb.biometric_type = 'rfid'
                AND JSON_EXTRACT(eb.biometric_data, '$.uid') = %s
                AND eb.activo = 'S'
                LIMIT 1
            """, (request.uid,))

            employee_result = cursor.fetchone()

        if not employee_result:
            raise HTTPException(status_code=404, detail="No employee found with this RFID UID")

        employee_id = employee_result['employee_id']

        # Get stored biometric data for verification
        stored_data = db_service.get_biometric_data(employee_id, "rfid")
        if not stored_data:
            raise HTTPException(status_code=404, detail="No RFID biometric data found for this employee")

        # Verify RFID
        result = await rfid_service.verify(
            uid=request.uid,
            stored_uid=stored_data['rfid_data'],
            device_id=request.device_id
        )

        return result

    except Exception as e:
        logger.error(f"RFID attendance verification failed: {e}")
        raise HTTPException(status_code=400, detail=f"RFID verification failed: {str(e)}")

@app.post("/attendance/capture-photo", response_model=PhotoCaptureResponse)
async def capture_attendance_photo():
    """Capture a photo for attendance registration using Python/OpenCV"""
    try:
        import cv2
        import os
        from datetime import datetime
        import numpy as np
        from PIL import Image

        # Initialize camera
        cap = cv2.VideoCapture(0)  # Use default camera

        if not cap.isOpened():
            raise HTTPException(status_code=500, detail="Could not access camera")

        # Set camera properties for better quality
        cap.set(cv2.CAP_PROP_FRAME_WIDTH, 1280)
        cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 720)
        cap.set(cv2.CAP_PROP_FPS, 30)

        # Wait for camera to initialize
        import time
        time.sleep(1)

        # Capture frame
        ret, frame = cap.read()
        cap.release()

        if not ret or frame is None:
            raise HTTPException(status_code=500, detail="Failed to capture image from camera")

        # Convert BGR to RGB
        frame_rgb = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)

        # Create PIL Image
        pil_image = Image.fromarray(frame_rgb)

        # Generate filename
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        filename = f"attendance_capture_{timestamp}.jpg"

        # Ensure uploads directory exists (relative to the main project directory)
        uploads_dir = os.path.join(os.path.dirname(os.path.dirname(__file__)), "uploads")
        os.makedirs(uploads_dir, exist_ok=True)

        file_path = os.path.join(uploads_dir, filename)

        # Save image with high quality
        pil_image.save(file_path, "JPEG", quality=95)

        # Get file info
        file_size = os.path.getsize(file_path)
        width, height = pil_image.size

        logger.info(f"Photo captured successfully: {filename} ({width}x{height}, {file_size} bytes)")

        return PhotoCaptureResponse(
            success=True,
            message="Photo captured successfully",
            filename=filename,
            file_path=file_path,
            file_size=file_size,
            image_width=width,
            image_height=height,
            capture_timestamp=datetime.now().isoformat()
        )

    except ImportError as e:
        logger.error(f"Required libraries not available: {e}")
        raise HTTPException(status_code=500, detail="OpenCV or PIL not available")
    except Exception as e:
        logger.error(f"Photo capture failed: {e}")
        raise HTTPException(status_code=500, detail=f"Photo capture failed: {str(e)}")

@app.get("/attendance/employee/{employee_id}")
async def get_employee_biometric_data(employee_id: int):
    """Get biometric data for an employee"""
    try:
        from services.database_service import DatabaseService
        db_service = DatabaseService()

        biometric_data = db_service.get_employee_biometric_summary(employee_id)

        if not biometric_data:
            raise HTTPException(status_code=404, detail="Employee not found or no biometric data available")

        return {
            "success": True,
            "employee_id": employee_id,
            "biometric_data": biometric_data
        }

    except Exception as e:
        logger.error(f"Failed to get employee biometric data: {e}")
        raise HTTPException(status_code=400, detail=f"Failed to get biometric data: {str(e)}")

# Error handlers
@app.exception_handler(Exception)
async def global_exception_handler(request, exc):
    logger.error(f"Unhandled exception: {exc}")
    return {"error": "Internal server error", "detail": str(exc)}

# Test endpoint
@app.get("/test")
async def test_endpoint():
    """Simple test endpoint"""
    return {"status": "ok", "message": "Service is running"}

if __name__ == "__main__":
    try:
        import uvicorn
        uvicorn.run(
            "app:app",
            host=settings.HOST,
            port=settings.PORT,
            reload=False,  # Disable reload to prevent shutdown issues
            log_level="info" if not settings.DEBUG else "debug"
        )
    except ImportError:
        logger.error("❌ Uvicorn not installed. Install with: pip install uvicorn")
        logger.error("💡 Run: pip install uvicorn[standard]")
        sys.exit(1)
