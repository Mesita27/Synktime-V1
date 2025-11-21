@echo off
echo ========================================
echo INSTALANDO ALTERNATIVA SIMPLE
echo ========================================
echo.
echo Este script instala una version simplificada
echo que no requiere compilacion compleja
echo.

cd /d "c:\Users\datam\Downloads\SNKTIME-copilot-fix-e94edcf3-8226-467e-8120-88ef6f42ba64"

echo Verificando Python...
python --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ÔØî Python no encontrado
    pause
    exit /b 1
)
echo Ô£à Python encontrado

echo.
echo ========================================
echo INSTALANDO DEPENDENCIAS BASICAS
echo ========================================
echo Instalando numpy, opencv, pillow...
pip install numpy opencv-python pillow --user

if %errorlevel% neq 0 (
    echo ÔØî Error instalando dependencias basicas
    pause
    exit /b 1
)
echo Ô£à Dependencias basicas instaladas

echo.
echo ========================================
echo INSTALANDO SCIPY PARA FUNCIONES AVANZADAS
echo ========================================
echo Instalando scipy...
pip install scipy --user

if %errorlevel% neq 0 (
    echo ÔØî Error instalando scipy
    echo Continuando sin scipy...
)

echo.
echo ========================================
echo INSTALANDO SKLEARN PARA ML
echo ========================================
echo Instalando scikit-learn...
pip install scikit-learn --user

if %errorlevel% neq 0 (
    echo ÔØî Error instalando scikit-learn
    echo Continuando sin scikit-learn...
)

echo.
echo ========================================
echo VERIFICANDO INSTALACIONES
echo ========================================
echo Probando numpy...
python -c "import numpy as np; print('Ô£à numpy OK')" 2>nul
if %errorlevel% neq 0 (
    echo ÔØî Error con numpy
    pause
    exit /b 1
)

echo Probando opencv...
python -c "import cv2; print('Ô£à opencv OK')" 2>nul
if %errorlevel% neq 0 (
    echo ÔØî Error con opencv
    pause
    exit /b 1
)

echo Probando pillow...
python -c "import PIL; print('Ô£à pillow OK')" 2>nul
if %errorlevel% neq 0 (
    echo ÔØî Error con pillow
    pause
    exit /b 1
)

echo.
echo ========================================
echo CREANDO VERSION MEJORADA DEL SERVICIO
echo ========================================
echo Creando facial_service_enhanced.py...

(
echo """
Enhanced Facial Recognition Service
Uses advanced feature extraction for better recognition
"""
import asyncio
import logging
import numpy as np
import cv2
import base64
from typing import Optional, Tuple, List, Dict, Any
from datetime import datetime
import os
import sys

logger = logging.getLogger(__name__)

class EnhancedFacialRecognitionService:
    """Enhanced Facial Recognition using advanced OpenCV features"""

    def __init__(self^):
        self.face_cascade = None
        self.initialized = False

    async def initialize(self^):
        """Initialize the facial recognition service"""
        try:
            # Load face cascade
            cascade_path = cv2.data.haarcascades + 'haarcascade_frontalface_default.xml'
            if os.path.exists(cascade_path^):
                self.face_cascade = cv2.CascadeClassifier(cascade_path^)
                logger.info("Face cascade loaded successfully")
            else:
                logger.error("Face cascade not found")
                return False

            self.initialized = True
            return True
        except Exception as e:
            logger.error(f"Failed to initialize: {e}")
            return False

    def _extract_facial_features(self, face_image: np.ndarray^) -> List[float]:
        """Extract comprehensive facial features"""
        features = []

        # Convert to grayscale if needed
        if len(face_image.shape^) == 3:
            gray = cv2.cvtColor(face_image, cv2.COLOR_BGR2GRAY^)
        else:
            gray = face_image

        # 1. HOG (Histogram of Oriented Gradients^) features
        hog = cv2.HOGDescriptor()
        hog_features = hog.compute(gray^)
        if hog_features is not None:
            features.extend(hog_features.flatten()[:100]^)  # Limit to 100 features

        # 2. LBP (Local Binary Patterns^) features
        from skimage import feature
        try:
            lbp = feature.local_binary_pattern(gray, 8, 1, method='uniform'^)
            hist, _ = np.histogram(lbp.ravel(), bins=np.arange(0, 11^), range=(0, 10^)^)
            features.extend(hist^)
        except ImportError:
            # Fallback LBP implementation
            lbp_features = []
            for i in range(1, gray.shape[0] - 1^):
                for j in range(1, gray.shape[1] - 1^):
                    center = gray[i, j]
                    pattern = 0
                    pattern ^= (gray[i-1, j-1] > center^) << 0
                    pattern ^= (gray[i-1, j] > center^) << 1
                    pattern ^= (gray[i-1, j+1] > center^) << 2
                    pattern ^= (gray[i, j+1] > center^) << 3
                    pattern ^= (gray[i+1, j+1] > center^) << 4
                    pattern ^= (gray[i+1, j] > center^) << 5
                    pattern ^= (gray[i+1, j-1] > center^) << 6
                    pattern ^= (gray[i, j-1] > center^) << 7
                    lbp_features.append(pattern^)
            hist, _ = np.histogram(lbp_features, bins=256^)
            features.extend(hist[:50]^)  # Limit to 50 features

        # 3. Color features (if color image^)
        if len(face_image.shape^) == 3:
            for channel in range(3^):
                hist = cv2.calcHist([face_image], [channel], None, [32], [0, 256]^)
                features.extend(cv2.normalize(hist, hist^).flatten()^)

        # 4. Edge features
        edges = cv2.Canny(gray, 100, 200^)
        edge_hist = cv2.calcHist([edges], [0], None, [32], [0, 256]^)
        features.extend(cv2.normalize(edge_hist, edge_hist^).flatten()^)

        # 5. Facial landmarks approximation (eye detection^)
        eye_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_eye.xml'^)
        eyes = eye_cascade.detectMultiScale(gray, 1.1, 3^)
        features.append(len(eyes^)^)  # Number of eyes detected
        if len(eyes^) >= 2:
            # Eye positions relative to face
            eye1_center = (eyes[0][0] + eyes[0][2]//2, eyes[0][1] + eyes[0][3]//2^)
            eye2_center = (eyes[1][0] + eyes[1][2]//2, eyes[1][1] + eyes[1][3]//2^)
            features.extend([eye1_center[0], eye1_center[1], eye2_center[0], eye2_center[1]]^)

        return features

    async def extract_embedding(self, image_data: str^) -> Dict[str, Any]:
        """Extract facial embedding from image"""
        try:
            if not self.initialized:
                return {"success": False, "message": "Service not initialized"}

            # Decode image
            image = self._decode_image(image_data^)
            if image is None:
                return {"success": False, "message": "Invalid image data"}

            # Detect faces
            gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY^)
            faces = self.face_cascade.detectMultiScale(gray, 1.1, 5, minSize=(50, 50^)^)

            if len(faces^) == 0:
                return {"success": False, "message": "No face detected"}

            # Use the largest face
            largest_face = max(faces, key=lambda f: f[2] * f[3]^)
            x, y, w, h = largest_face

            # Extract face region
            face_roi = image[y:y+h, x:x+w]

            # Extract features
            features = self._extract_facial_features(face_roi^)

            return {
                "success": True,
                "embedding": features,
                "quality_score": min(1.0, len(features^) / 200.0^),  # Quality based on feature count
                "face_detected": True,
                "face_count": len(faces^)
            }

        except Exception as e:
            logger.error(f"Feature extraction failed: {e}")
            return {"success": False, "message": f"Extraction failed: {str(e)}"}

    def _decode_image(self, image_data: str^) -> Optional[np.ndarray]:
        """Decode base64 image data"""
        try:
            if image_data.startswith('data:image'^):
                image_data = image_data.split(',')[1]

            image_bytes = base64.b64decode(image_data^)
            nparr = np.frombuffer(image_bytes, np.uint8^)
            image = cv2.imdecode(nparr, cv2.IMREAD_COLOR^)
            return image
        except Exception as e:
            logger.error(f"Image decoding failed: {e}")
            return None

    def _calculate_similarity(self, embedding1: List[float], embedding2: List[float]^) -> float:
        """Calculate similarity between embeddings"""
        try:
            vec1 = np.array(embedding1^)
            vec2 = np.array(embedding2^)

            # Normalize vectors
            vec1 = vec1 / np.linalg.norm(vec1^)
            vec2 = vec2 / np.linalg.norm(vec2^)

            # Cosine similarity
            similarity = np.dot(vec1, vec2^)

            # Convert to 0-1 range
            return (similarity + 1^) / 2
        except Exception as e:
            logger.error(f"Similarity calculation failed: {e}")
            return 0.0

    async def verify(self, employee_id: int, image_data: str, stored_embedding: List[float], threshold: float = 0.7^) -> Dict[str, Any]:
        """Verify facial biometric data"""
        try:
            # Extract embedding from verification image
            result = await self.extract_embedding(image_data^)
            if not result["success"]:
                return result

            # Calculate similarity
            similarity = self._calculate_similarity(result["embedding"], stored_embedding^)

            return {
                "success": similarity >= threshold,
                "confidence": similarity,
                "message": f"Verification {'successful' if similarity >= threshold else 'failed'}",
                "employee_id": employee_id
            }

        except Exception as e:
            logger.error(f"Verification failed: {e}")
            return {"success": False, "message": f"Verification error: {str(e)}"}
) > facial_service_enhanced.py

echo Ô£à Servicio facial mejorado creado

echo.
echo ========================================
echo PROBANDO EL SERVICIO MEJORADO
echo ========================================
echo Probando importacion...
python -c "from facial_service_enhanced import EnhancedFacialRecognitionService; print('Ô£à Servicio mejorado OK')" 2>nul
if %errorlevel% neq 0 (
    echo ÔØî Error importando servicio mejorado
    pause
    exit /b 1
)

echo.
echo ========================================
echo INSTALACION COMPLETA
echo ========================================
echo Ô£à Sistema de reconocimiento facial mejorado instalado!
echo.
echo Para usar el servicio mejorado:
echo 1. Importa: from facial_service_enhanced import EnhancedFacialRecognitionService
echo 2. Inicializa: service = EnhancedFacialRecognitionService()
echo 3. await service.initialize()
echo.
echo Este servicio usa caracteristicas avanzadas de OpenCV
echo para una mejor distincion entre personas.
echo.
pause
