"""
Configuration settings for SNKTIME Biometric Service
"""

import os
from typing import List
from pydantic_settings import BaseSettings

class Settings(BaseSettings):
    # Service configuration
    HOST: str = "127.0.0.1"
    PORT: int = 8000  # Changed back to 8000 for JavaScript compatibility
    DEBUG: bool = False

    # CORS settings
    CORS_ORIGINS: List[str] = [
        "http://localhost",
        "http://localhost:80",
        "http://localhost:8080",
        "http://127.0.0.1",
        "http://127.0.0.1:80",
        "http://127.0.0.1:8080",
        "http://localhost:3000",  # Common development port
        "http://127.0.0.1:3000",
        "*",  # Allow all origins for development
    ]

    # Database settings (for direct database access if needed)
    DB_HOST: str = "localhost"
    DB_PORT: int = 3306
    DB_NAME: str = "synktime"
    DB_USER: str = "root"
    DB_PASSWORD: str = ""

    # InsightFace settings
    INSIGHTFACE_MODEL_PATH: str = "models"
    INSIGHTFACE_MODEL_NAME: str = "buffalo_l"
    FACE_DETECTION_THRESHOLD: float = 0.5
    FACE_RECOGNITION_THRESHOLD: float = 0.85

    # Fingerprint settings
    FPRINTD_TIMEOUT: int = 30
    FINGERPRINT_RETRY_ATTEMPTS: int = 3

    # RFID settings
    RFID_TIMEOUT: int = 10
    RFID_BAUDRATE: int = 9600

    # Device scanning
    USB_SCAN_TIMEOUT: int = 5

    # Logging
    LOG_LEVEL: str = "INFO"

    class Config:
        env_file = ".env"
        case_sensitive = False
    LOG_FILE: str = "biometric_service.log"
    
    # Security
    API_KEY: str = ""  # Optional API key for authentication
    
    class Config:
        env_file = ".env"
        env_file_encoding = "utf-8"

# Create settings instance
settings = Settings()