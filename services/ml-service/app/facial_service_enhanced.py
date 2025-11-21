"""
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

    def __init__(self):
        self.face_cascade = None
        self.initialized = False

    async def initialize(self):
        """Initialize the facial recognition service"""
        try:
            # Load face cascade
            cascade_path = cv2.data.haarcascades + 'haarcascade_frontalface_default.xml'
            if os.path.exists(cascade_path):
                self.face_cascade = cv2.CascadeClassifier(cascade_path)
                logger.info("Face cascade loaded successfully")
            else:
                logger.error("Face cascade not found")
                return False

            self.initialized = True
            return True
        except Exception as e:
            logger.error(f"Failed to initialize: {e}")
            return False

    def _extract_facial_features(self, face_image: np.ndarray) -> List[float]:
        """Extract comprehensive facial features"""
        features = []

        # Convert to grayscale if needed
        if len(face_image.shape) == 3:
            gray = cv2.cvtColor(face_image, cv2.COLOR_BGR2GRAY)
        else:
            gray = face_image

        # 1. HOG (Histogram of Oriented Gradients) features
        try:
            hog = cv2.HOGDescriptor()
            hog_features = hog.compute(gray)
            if hog_features is not None:
                features.extend(hog_features.flatten()[:100])  # Limit to 100 features
        except:
            pass  # Skip if HOG fails

        # 2. LBP (Local Binary Patterns) features - simplified implementation
        lbp_features = []
        for i in range(1, min(gray.shape[0] - 1, 50)):  # Limit processing for speed
            for j in range(1, min(gray.shape[1] - 1, 50)):
                center = gray[i, j]
                pattern = 0
                # Check 8 neighbors
                neighbors = [
                    (i-1, j-1), (i-1, j), (i-1, j+1),
                    (i, j+1), (i+1, j+1), (i+1, j),
                    (i+1, j-1), (i, j-1)
                ]
                for idx, (ni, nj) in enumerate(neighbors):
                    if 0 <= ni < gray.shape[0] and 0 <= nj < gray.shape[1]:
                        if gray[ni, nj] > center:
                            pattern |= (1 << idx)
                lbp_features.append(pattern)

        if lbp_features:
            hist, _ = np.histogram(lbp_features, bins=256)
            features.extend(hist[:50])  # Limit to 50 features

        # 3. Color features (if color image)
        if len(face_image.shape) == 3:
            for channel in range(3):
                hist = cv2.calcHist([face_image], [channel], None, [32], [0, 256])
                features.extend(cv2.normalize(hist, hist).flatten())

        # 4. Edge features
        edges = cv2.Canny(gray, 100, 200)
        edge_hist = cv2.calcHist([edges], [0], None, [32], [0, 256])
        features.extend(cv2.normalize(edge_hist, edge_hist).flatten())

        # 5. Basic texture features
        sobelx = cv2.Sobel(gray, cv2.CV_64F, 1, 0, ksize=3)
        sobely = cv2.Sobel(gray, cv2.CV_64F, 0, 1, ksize=3)
        texture = np.sqrt(sobelx**2 + sobely**2)
        texture_hist = cv2.calcHist([texture.astype(np.uint8)], [0], None, [32], [0, 256])
        features.extend(cv2.normalize(texture_hist, texture_hist).flatten())

        return features

    async def extract_embedding(self, image_data: str) -> Dict[str, Any]:
        """Extract facial embedding from image"""
        try:
            if not self.initialized:
                return {"success": False, "message": "Service not initialized"}

            # Decode image
            image = self._decode_image(image_data)
            if image is None:
                return {"success": False, "message": "Invalid image data"}

            # Detect faces
            gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
            faces = self.face_cascade.detectMultiScale(gray, 1.1, 5, minSize=(50, 50))

            if len(faces) == 0:
                return {"success": False, "message": "No face detected"}

            # Use the largest face
            largest_face = max(faces, key=lambda f: f[2] * f[3])
            x, y, w, h = largest_face

            # Extract face region with some margin
            margin = int(min(w, h) * 0.1)
            x1 = max(0, x - margin)
            y1 = max(0, y - margin)
            x2 = min(image.shape[1], x + w + margin)
            y2 = min(image.shape[0], y + h + margin)

            face_roi = image[y1:y2, x1:x2]

            # Extract features
            features = self._extract_facial_features(face_roi)

            return {
                "success": True,
                "embedding": features,
                "quality_score": min(1.0, len(features) / 200.0),  # Quality based on feature count
                "face_detected": True,
                "face_count": len(faces)
            }

        except Exception as e:
            logger.error(f"Feature extraction failed: {e}")
            return {"success": False, "message": f"Extraction failed: {str(e)}"}

    def _decode_image(self, image_data: str) -> Optional[np.ndarray]:
        """Decode base64 image data"""
        try:
            if image_data.startswith('data:image'):
                image_data = image_data.split(',')[1]

            image_bytes = base64.b64decode(image_data)
            nparr = np.frombuffer(image_bytes, np.uint8)
            image = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
            return image
        except Exception as e:
            logger.error(f"Image decoding failed: {e}")
            return None

    def _calculate_similarity(self, embedding1: List[float], embedding2: List[float]) -> float:
        """Calculate similarity between embeddings"""
        try:
            if not embedding1 or not embedding2:
                return 0.0

            vec1 = np.array(embedding1)
            vec2 = np.array(embedding2)

            # Handle different embedding sizes
            min_len = min(len(vec1), len(vec2))
            vec1 = vec1[:min_len]
            vec2 = vec2[:min_len]

            # Normalize vectors
            norm1 = np.linalg.norm(vec1)
            norm2 = np.linalg.norm(vec2)

            if norm1 == 0 or norm2 == 0:
                return 0.0

            vec1 = vec1 / norm1
            vec2 = vec2 / norm2

            # Cosine similarity
            similarity = np.dot(vec1, vec2)

            # Convert to 0-1 range
            return max(0.0, min(1.0, (similarity + 1) / 2))
        except Exception as e:
            logger.error(f"Similarity calculation failed: {e}")
            return 0.0

    async def verify(self, employee_id: int, image_data: str, stored_embedding: List[float], threshold: float = 0.7) -> Dict[str, Any]:
        """Verify facial biometric data"""
        try:
            # Extract embedding from verification image
            result = await self.extract_embedding(image_data)
            if not result["success"]:
                return result

            # Calculate similarity
            similarity = self._calculate_similarity(result["embedding"], stored_embedding)

            return {
                "success": similarity >= threshold,
                "confidence": similarity,
                "message": f"Verification {'successful' if similarity >= threshold else 'failed'} (confidence: {similarity:.2f})",
                "employee_id": employee_id,
                "quality_score": result.get("quality_score", 0.0)
            }

        except Exception as e:
            logger.error(f"Verification failed: {e}")
            return {"success": False, "message": f"Verification error: {str(e)}"}
