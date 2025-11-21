#!/bin/bash
# SNKTIME Python Service Installation Script
# This script sets up the Python biometric service environment

set -e

echo "🚀 SNKTIME Python Biometric Service Installer"
echo "==============================================="

# Get the directory of the script
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
SERVICE_DIR="$SCRIPT_DIR"

echo "📁 Service directory: $SERVICE_DIR"

# Check Python version
echo "🐍 Checking Python version..."
PYTHON_VERSION=$(python3 --version 2>&1 | cut -d' ' -f2 | cut -d'.' -f1,2)
echo "   Python version: $PYTHON_VERSION"

if [[ $(echo "$PYTHON_VERSION >= 3.8" | bc -l) -eq 0 ]]; then
    echo "❌ Python 3.8+ is required. Current version: $PYTHON_VERSION"
    exit 1
fi

# Create virtual environment
echo "🔧 Creating Python virtual environment..."
if [ ! -d "$SERVICE_DIR/venv" ]; then
    python3 -m venv "$SERVICE_DIR/venv"
    echo "   ✅ Virtual environment created"
else
    echo "   ✅ Virtual environment already exists"
fi

# Activate virtual environment
echo "🔧 Activating virtual environment..."
source "$SERVICE_DIR/venv/bin/activate"

# Upgrade pip
echo "🔧 Upgrading pip..."
pip install --upgrade pip

# Install basic dependencies first (core FastAPI dependencies)
echo "📦 Installing core dependencies..."
pip install fastapi uvicorn python-multipart pydantic pydantic-settings httpx aiofiles

echo "📦 Installing data processing dependencies..."
pip install numpy opencv-python pillow

echo "📦 Installing database dependencies..."
pip install pymysql aiomysql

echo "📦 Installing logging dependencies..."
pip install structlog

# Install optional dependencies with error handling
echo "📦 Installing optional dependencies..."

# Install ONNX dependencies for InsightFace
echo "   Installing ONNX runtime..."
pip install onnx onnxruntime || echo "   ⚠️  ONNX installation failed (non-critical)"

# Install serial/USB dependencies 
echo "   Installing serial/USB support..."
pip install pyserial pyusb || echo "   ⚠️  Serial/USB installation failed (non-critical)"

# Install Linux-specific dependencies
if [[ "$OSTYPE" == "linux-gnu"* ]]; then
    echo "   Installing Linux-specific dependencies..."
    pip install pydbus || echo "   ⚠️  D-Bus installation failed (non-critical)"
fi

# Install development dependencies
echo "📦 Installing development dependencies..."
pip install pytest pytest-asyncio pytest-cov

# Try to install InsightFace (may fail on some systems)
echo "🤖 Installing InsightFace (AI facial recognition)..."
pip install insightface || echo "   ⚠️  InsightFace installation failed (will use fallback mode)"

echo ""
echo "✅ Python dependencies installation completed!"
echo ""

# Create .env file if it doesn't exist
ENV_FILE="$SERVICE_DIR/.env"
if [ ! -f "$ENV_FILE" ]; then
    echo "⚙️  Creating default .env configuration file..."
    cat > "$ENV_FILE" << EOL
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

# Logging
LOG_LEVEL=INFO
LOG_FILE=biometric_service.log
EOL
    echo "   ✅ .env file created with default settings"
else
    echo "   ✅ .env file already exists"
fi

# Create models directory for InsightFace
MODELS_DIR="$SERVICE_DIR/models"
if [ ! -d "$MODELS_DIR" ]; then
    mkdir -p "$MODELS_DIR"
    echo "   ✅ Models directory created: $MODELS_DIR"
fi

# Test the installation
echo ""
echo "🧪 Testing installation..."
python3 -c "
import sys
print(f'✅ Python: {sys.version}')

try:
    import fastapi
    print(f'✅ FastAPI: {fastapi.__version__}')
except ImportError:
    print('❌ FastAPI not found')

try:
    import numpy
    print(f'✅ NumPy: {numpy.__version__}')
except ImportError:
    print('❌ NumPy not found')

try:
    import cv2
    print(f'✅ OpenCV: {cv2.__version__}')
except ImportError:
    print('❌ OpenCV not found')

try:
    import insightface
    print('✅ InsightFace: Available')
except ImportError:
    print('⚠️  InsightFace: Not available (will use fallback mode)')

try:
    import serial
    print('✅ PySerial: Available')
except ImportError:
    print('⚠️  PySerial: Not available (RFID support disabled)')
    
print('\\n🎉 Installation test completed!')
"

echo ""
echo "🎉 SNKTIME Python Biometric Service installation completed!"
echo ""
echo "Next steps:"
echo "1. Activate the virtual environment: source $SERVICE_DIR/venv/bin/activate"
echo "2. Configure database settings in: $ENV_FILE"
echo "3. Start the service: python3 app.py"
echo "4. Test the service: curl http://127.0.0.1:8000/health"
echo ""
echo "For development/testing without external dependencies:"
echo "python3 test_service.py"
echo ""