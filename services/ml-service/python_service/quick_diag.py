#!/usr/bin/env python3
"""
SNKTIME Python Service - Quick Diagnostic Script
Diagnóstico rápido para verificar el estado del servicio
"""

import sys
import socket
import os

print('Python version:', sys.version.split()[0])

try:
    import fastapi
    print('FastAPI disponible')
except ImportError:
    print('FastAPI no disponible')

try:
    import cv2
    print('OpenCV disponible')
except ImportError:
    print('OpenCV no disponible')

try:
    import insightface
    print('InsightFace disponible')
except ImportError:
    print('InsightFace no disponible (funcionalidad limitada)')

# Verificar puerto
sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
result = sock.connect_ex(('127.0.0.1', 8000))
sock.close()
if result == 0:
    print('Puerto 8000 ocupado')
else:
    print('Puerto 8000 disponible')

print('Directorio actual:', os.getcwd())
