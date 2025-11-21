#!/bin/bash
# SNKTIME Python Service - Script de Instalación Mejorado
# Soluciona problemas comunes de instalación

set -e

echo "🚀 SNKTIME Python Service - Instalación Mejorada"
echo "================================================"

# Detectar sistema operativo
if [[ "$OSTYPE" == "msys" ]] || [[ "$OSTYPE" == "win32" ]]; then
    IS_WINDOWS=true
    echo "🎯 Detectado: Windows"
else
    IS_WINDOWS=false
    echo "🎯 Detectado: Linux/Mac"
fi

# Obtener directorio del script
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
SERVICE_DIR="$SCRIPT_DIR"

echo "📁 Directorio del servicio: $SERVICE_DIR"

# Verificar Python
echo ""
echo "🐍 Verificando Python..."
if command -v python3 &> /dev/null; then
    PYTHON_CMD="python3"
    PIP_CMD="pip3"
elif command -v python &> /dev/null; then
    PYTHON_CMD="python"
    PIP_CMD="pip"
else
    echo "❌ Python no encontrado. Instala Python 3.8+ primero."
    exit 1
fi

PYTHON_VERSION=$($PYTHON_CMD --version 2>&1 | cut -d' ' -f2)
echo "   Usando: $PYTHON_CMD ($PYTHON_VERSION)"

# Crear entorno virtual
echo ""
echo "🔧 Creando entorno virtual..."
if [ ! -d "$SERVICE_DIR/venv" ]; then
    $PYTHON_CMD -m venv "$SERVICE_DIR/venv"
    echo "   ✅ Entorno virtual creado"
else
    echo "   ✅ Entorno virtual ya existe"
fi

# Activar entorno virtual
echo ""
echo "🔧 Activando entorno virtual..."
if [ "$IS_WINDOWS" = true ]; then
    source "$SERVICE_DIR/venv/Scripts/activate"
else
    source "$SERVICE_DIR/venv/bin/activate"
fi

# Actualizar pip
echo ""
echo "🔧 Actualizando pip..."
$PIP_CMD install --upgrade pip

# Instalar dependencias básicas primero
echo ""
echo "📦 Instalando dependencias básicas..."
$PIP_CMD install wheel setuptools

# Instalar FastAPI y dependencias core
echo ""
echo "📦 Instalando FastAPI y dependencias core..."
$PIP_CMD install fastapi uvicorn python-multipart pydantic pydantic-settings

# Instalar dependencias de procesamiento de datos
echo ""
echo "📦 Instalando dependencias de procesamiento..."
$PIP_CMD install numpy pillow httpx aiofiles

# Instalar OpenCV (puede ser problemático en algunos sistemas)
echo ""
echo "📦 Instalando OpenCV..."
if $PIP_CMD install opencv-python; then
    echo "   ✅ OpenCV instalado correctamente"
else
    echo "   ⚠️  OpenCV falló, intentando versión headless..."
    $PIP_CMD install opencv-python-headless
fi

# Instalar dependencias de base de datos
echo ""
echo "📦 Instalando dependencias de base de datos..."
$PIP_CMD install pymysql aiomysql

# Instalar dependencias de logging
echo ""
echo "📦 Instalando dependencias de logging..."
$PIP_CMD install structlog

# Instalar dependencias de desarrollo
echo ""
echo "📦 Instalando dependencias de desarrollo..."
$PIP_CMD install pytest pytest-asyncio pytest-cov httpx

# Instalar dependencias opcionales con manejo de errores
echo ""
echo "📦 Instalando dependencias opcionales..."

# Instalar ONNX para InsightFace
echo "   Instalando ONNX runtime..."
if $PIP_CMD install onnx onnxruntime; then
    echo "   ✅ ONNX instalado"
else
    echo "   ⚠️  ONNX falló (no crítico)"
fi

# Instalar dependencias de hardware
echo "   Instalando soporte de hardware..."
$PIP_CMD install pyserial pyusb 2>/dev/null && echo "   ✅ Hardware support instalado" || echo "   ⚠️  Hardware support falló (no crítico)"

# Instalar InsightFace (puede fallar en algunos sistemas)
echo ""
echo "🤖 Instalando InsightFace (IA para reconocimiento facial)..."
echo "   Esto puede tomar varios minutos..."

# Método 1: Instalar directamente
if $PIP_CMD install insightface; then
    echo "   ✅ InsightFace instalado correctamente"
else
    echo "   ⚠️  InsightFace falló. Intentando método alternativo..."

    # Método 2: Instalar con --no-deps y luego dependencias específicas
    echo "   Intentando instalación alternativa..."
    $PIP_CMD install --no-deps insightface 2>/dev/null || echo "   ❌ InsightFace no disponible"

    # Instalar dependencias específicas que pueden faltar
    $PIP_CMD install mxnet scikit-image tqdm 2>/dev/null || echo "   ⚠️  Algunas dependencias de InsightFace faltan"
fi

# Verificar instalación
echo ""
echo "🔍 Verificando instalación..."
echo "   Ejecutando diagnóstico..."

if [ "$IS_WINDOWS" = true ]; then
    "$SERVICE_DIR/venv/Scripts/python" diagnose_service.py
else
    "$SERVICE_DIR/venv/bin/python" diagnose_service.py
fi

# Crear archivo de configuración si no existe
ENV_FILE="$SERVICE_DIR/.env"
if [ ! -f "$ENV_FILE" ]; then
    echo ""
    echo "⚙️  Creando archivo de configuración..."
    cat > "$ENV_FILE" << 'EOL'
# SNKTIME Biometric Service Configuration
HOST=127.0.0.1
PORT=8000
DEBUG=true

# Database settings
DB_HOST=localhost
DB_PORT=3306
DB_NAME=synktime
DB_USER=root
DB_PASSWORD=

# InsightFace settings
INSIGHTFACE_MODEL_PATH=models
INSIGHTFACE_MODEL_NAME=buffalo_l
FACE_DETECTION_THRESHOLD=0.5
FACE_RECOGNITION_THRESHOLD=0.6

# Hardware settings
FPRINTD_TIMEOUT=30
RFID_TIMEOUT=10
USB_SCAN_TIMEOUT=5

# Logging
LOG_LEVEL=INFO
EOL
    echo "   ✅ Archivo .env creado"
fi

echo ""
echo "🎉 Instalación completada!"
echo ""
echo "💡 Para iniciar el servicio:"
echo "   # Activar entorno virtual"
if [ "$IS_WINDOWS" = true ]; then
    echo "   venv\\Scripts\\activate"
else
    echo "   source venv/bin/activate"
fi
echo ""
echo "   # Iniciar servicio"
echo "   python app.py"
echo "   # o"
echo "   uvicorn app:app --host 127.0.0.1 --port 8000 --reload"
echo ""
echo "🔗 El servicio estará disponible en: http://127.0.0.1:8000"
echo "📚 Documentación API en: http://127.0.0.1:8000/docs"
