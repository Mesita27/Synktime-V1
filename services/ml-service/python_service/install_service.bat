@echo off
REM SNKTIME Python Service - Windows Installation Script
REM Soluciona problemas comunes de instalación en Windows

echo 🚀 SNKTIME Python Service - Instalación para Windows
echo =====================================================

REM Verificar Python
echo.
echo 🐍 Verificando Python...
python --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Python no encontrado. Instala Python 3.8+ desde https://python.org
    pause
    exit /b 1
)

for /f "tokens=2" %%i in ('python --version 2^>^&1') do set PYTHON_VERSION=%%i
echo    Usando: %PYTHON_VERSION%

REM Obtener directorio del script
set "SCRIPT_DIR=%~dp0"
set "SERVICE_DIR=%SCRIPT_DIR:~0,-1%"

echo 📁 Directorio del servicio: %SERVICE_DIR%

REM Crear entorno virtual
echo.
echo 🔧 Creando entorno virtual...
if not exist "%SERVICE_DIR%\venv" (
    python -m venv "%SERVICE_DIR%\venv"
    echo    ✅ Entorno virtual creado
) else (
    echo    ✅ Entorno virtual ya existe
)

REM Activar entorno virtual
echo.
echo 🔧 Activando entorno virtual...
call "%SERVICE_DIR%\venv\Scripts\activate.bat"

REM Actualizar pip
echo.
echo 🔧 Actualizando pip...
python -m pip install --upgrade pip

REM Instalar dependencias básicas
echo.
echo 📦 Instalando dependencias básicas...
pip install wheel setuptools

REM Instalar FastAPI y dependencias core
echo.
echo 📦 Instalando FastAPI y dependencias core...
pip install fastapi uvicorn python-multipart pydantic pydantic-settings

REM Instalar dependencias de procesamiento
echo.
echo 📦 Instalando dependencias de procesamiento...
pip install numpy pillow httpx aiofiles

REM Instalar OpenCV
echo.
echo 📦 Instalando OpenCV...
pip install opencv-python
if %errorlevel% neq 0 (
    echo    ⚠️  OpenCV falló, intentando versión headless...
    pip install opencv-python-headless
)

REM Instalar dependencias de base de datos
echo.
echo 📦 Instalando dependencias de base de datos...
pip install pymysql aiomysql

REM Instalar dependencias de logging
echo.
echo 📦 Instalando dependencias de logging...
pip install structlog

REM Instalar dependencias de desarrollo
echo.
echo 📦 Instalando dependencias de desarrollo...
pip install pytest pytest-asyncio pytest-cov httpx

REM Instalar dependencias opcionales
echo.
echo 📦 Instalando dependencias opcionales...

echo    Instalando ONNX runtime...
pip install onnx onnxruntime

echo    Instalando soporte de hardware...
pip install pyserial pyusb

REM Instalar InsightFace
echo.
echo 🤖 Instalando InsightFace (IA para reconocimiento facial)...
echo    Esto puede tomar varios minutos...
pip install insightface
if %errorlevel% neq 0 (
    echo    ⚠️  InsightFace falló. Intentando método alternativo...
    pip install --no-deps insightface
    pip install mxnet scikit-image tqdm
)

REM Verificar instalación
echo.
echo 🔍 Verificando instalación...
python diagnose_service.py
if %errorlevel% neq 0 (
    echo    ⚠️  El diagnóstico falló, pero continuando...
)

REM Crear archivo de configuración
if not exist "%SERVICE_DIR%\.env" (
    echo.
    echo ⚙️  Creando archivo de configuración...
    (
        echo # SNKTIME Biometric Service Configuration
        echo HOST=127.0.0.1
        echo PORT=8000
        echo DEBUG=true
        echo.
        echo # Database settings
        echo DB_HOST=localhost
        echo DB_PORT=3306
        echo DB_NAME=synktime
        echo DB_USER=root
        echo DB_PASSWORD=
        echo.
        echo # InsightFace settings
        echo INSIGHTFACE_MODEL_PATH=models
        echo INSIGHTFACE_MODEL_NAME=buffalo_l
        echo FACE_DETECTION_THRESHOLD=0.5
        echo FACE_RECOGNITION_THRESHOLD=0.6
        echo.
        echo # Hardware settings
        echo FPRINTD_TIMEOUT=30
        echo RFID_TIMEOUT=10
        echo USB_SCAN_TIMEOUT=5
        echo.
        echo # Logging
        echo LOG_LEVEL=INFO
    ) > "%SERVICE_DIR%\.env"
    echo    ✅ Archivo .env creado
)

echo.
echo 🎉 Instalación completada!
echo.
echo 💡 Para iniciar el servicio:
echo    # Activar entorno virtual
echo    venv\Scripts\activate.bat
echo.
echo    # Iniciar servicio
echo    python app.py
echo    # o
echo    uvicorn app:app --host 127.0.0.1 --port 8000 --reload
echo.
echo 🔗 El servicio estará disponible en: http://127.0.0.1:8000
echo 📚 Documentación API en: http://127.0.0.1:8000/docs
echo.
pause
