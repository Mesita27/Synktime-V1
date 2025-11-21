#!/bin/bash
# SNKTIME - Instalación Rápida de Dependencias Python y Lanzamiento
# Para Ubuntu 24.04 con LAMP ya instalado

set -e

# Colores
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

echo "🐍 SNKTIME - Instalación Python y Lanzamiento"
echo "============================================="

# Verificar que estamos en el directorio correcto
if [ ! -f "python_service/requirements.txt" ]; then
    print_error "Ejecuta este script desde el directorio raíz del proyecto SNKTIME"
    exit 1
fi

# Cambiar al directorio del servicio Python
cd python_service

print_status "Instalando dependencias del sistema..."

# Actualizar paquetes
sudo apt update

# Instalar dependencias críticas para OpenCV e InsightFace
sudo apt install -y \
    python3-dev \
    build-essential \
    cmake \
    libgl1-mesa-glx \
    libglib2.0-0 \
    libsm6 \
    libxext6 \
    libxrender-dev \
    libgomp1 \
    libgthread-2.0-0 \
    libgtk2.0-dev \
    pkg-config \
    libopenblas-dev \
    liblapack-dev \
    libjpeg-dev \
    libpng-dev \
    libtiff-dev \
    libavcodec-dev \
    libavformat-dev \
    libswscale-dev \
    libv4l-dev \
    libxvidcore-dev \
    libx264-dev \
    libgtk-3-dev \
    libatlas-base-dev \
    gfortran

print_success "Dependencias del sistema instaladas"

# Crear entorno virtual si no existe
if [ ! -d "venv" ]; then
    print_status "Creando entorno virtual..."
    python3 -m venv venv
    print_success "Entorno virtual creado"
else
    print_warning "Entorno virtual ya existe"
fi

# Activar entorno virtual
print_status "Activando entorno virtual..."
source venv/bin/activate

# Actualizar pip
print_status "Actualizando pip..."
pip install --upgrade pip

# Instalar dependencias Python paso a paso para mejor control de errores
print_status "Instalando dependencias Python..."

# 1. Instalar numpy primero (base para otras librerías)
print_status "Instalando NumPy..."
pip install numpy==1.24.3

# 2. Instalar OpenCV
print_status "Instalando OpenCV..."
pip install opencv-python-headless==4.8.0.76

# 3. Instalar Pillow
print_status "Instalando Pillow..."
pip install Pillow>=10.0.0

# 4. Instalar PyMySQL
print_status "Instalando PyMySQL..."
pip install PyMySQL>=1.1.0

# 5. Instalar FastAPI y Uvicorn
print_status "Instalando FastAPI y Uvicorn..."
pip install fastapi>=0.104.0 uvicorn[standard]>=0.24.0

# 6. Instalar Pydantic
print_status "Instalando Pydantic..."
pip install pydantic>=2.4.0 pydantic-settings>=2.0.0

# 7. Instalar otras dependencias
print_status "Instalando dependencias adicionales..."
pip install httpx>=0.25.0 aiofiles>=23.2.0 python-multipart>=0.0.6

# 8. Instalar dependencias opcionales
print_status "Instalando dependencias opcionales..."
pip install pyserial>=3.5 pyusb>=1.2.1 pydbus>=0.6.0 aiomysql>=0.2.0 structlog>=23.1.0

# 9. Intentar instalar InsightFace (puede fallar en algunos sistemas)
print_status "Intentando instalar InsightFace..."
if pip install insightface>=0.7.3 onnx>=1.14.0 onnxruntime>=1.16.0; then
    print_success "InsightFace instalado correctamente"
else
    print_warning "InsightFace no se pudo instalar (puede requerir configuración adicional)"
    print_warning "El sistema funcionará sin reconocimiento facial avanzado"
fi

print_success "Instalación de dependencias completada"

# Verificar instalación
print_status "Verificando instalación crítica..."

python3 -c "
try:
    import sys
    sys.path.insert(0, '.')
    import cv2
    import numpy as np
    import pymysql
    from fastapi import FastAPI
    print('✅ Dependencias críticas verificadas')
except ImportError as e:
    print(f'❌ Error de importación: {e}')
    exit(1)
"

# Configurar archivo .env si no existe
if [ ! -f ".env" ]; then
    print_status "Configurando archivo .env..."
    cp .env.example .env
    print_success "Archivo .env creado"
else
    print_warning "Archivo .env ya existe"
fi

# Crear directorios necesarios
mkdir -p logs
mkdir -p models

print_success "Configuración completada"

# Función para iniciar el servicio
start_service() {
    print_status "Iniciando servicio Python..."

    # Verificar que el puerto 8000 esté disponible
    if lsof -Pi :8000 -sTCP:LISTEN -t >/dev/null; then
        print_warning "Puerto 8000 ocupado, deteniendo servicio anterior..."
        pkill -f "uvicorn.*app:app" || true
        sleep 2
    fi

    # Iniciar servicio en background
    nohup uvicorn app:app --host 0.0.0.0 --port 8000 --reload > logs/service.log 2>&1 &
    SERVICE_PID=$!

    echo $SERVICE_PID > service.pid

    print_success "Servicio iniciado con PID: $SERVICE_PID"
    print_success "API disponible en: http://localhost:8000"
    print_success "Documentación: http://localhost:8000/docs"
    print_success "Logs: tail -f logs/service.log"

    # Verificar que el servicio esté respondiendo
    sleep 3
    if curl -s http://localhost:8000/docs > /dev/null; then
        print_success "Servicio verificado y funcionando correctamente"
    else
        print_warning "Servicio iniciado pero no responde aún. Revisa los logs."
    fi
}

# Preguntar si quiere iniciar el servicio
echo ""
echo "¿Deseas iniciar el servicio Python ahora? (y/n)"
read -r response
if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
    start_service
else
    print_status "Servicio no iniciado. Para iniciarlo manualmente:"
    print_status "  cd python_service"
    print_status "  source venv/bin/activate"
    print_status "  uvicorn app:app --host 0.0.0.0 --port 8000 --reload"
fi

echo ""
print_success "Instalación completada exitosamente!"
echo ""
echo "Comandos útiles:"
echo "• Iniciar servicio: ./start_service.sh"
echo "• Ver logs: tail -f python_service/logs/service.log"
echo "• Detener servicio: pkill -f uvicorn"
echo "• Verificar estado: curl http://localhost:8000/docs"
