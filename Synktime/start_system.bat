@echo off
REM Script de Inicio Recho 📋 Verificando dependencias de Python...
echo.

REM Verificar e instalar InsightFace automáticamente
echo 🔍 Verificando InsightFace...
python -c "import insightface" >nul 2>&1
if %errorlevel% neq 0 (
    echo ⚠️ InsightFace no está instalado
    echo 🤖 Iniciando instalación automática...
    
    REM Ejecutar el script de instalación de InsightFace
    if exist "install_insightface.bat" (
        call install_insightface.bat
        if %errorlevel% neq 0 (
            echo ❌ ERROR: Falló la instalación de InsightFace
            echo 💡 Solución: Ejecuta manualmente install_insightface.bat
            pause
            exit /b 1
        )
    ) else (
        echo ❌ ERROR: No se encontró install_insightface.bat
        echo 💡 Solución: Descarga Microsoft Visual C++ Build Tools desde:
        echo    https://visualstudio.microsoft.com/visual-cpp-build-tools/
        echo    Luego instala InsightFace manualmente: pip install insightface
        pause
        exit /b 1
    )
) else (
    echo ✅ InsightFace ya está instalado
)

REM Instalar dependencias de Python si requirements.txt existe
if exist "python_service\requirements.txt" (
    echo 🔧 Instalando dependencias de Python...
    pip install -r python_service\requirements.txt
    if %errorlevel% neq 0 (
        echo ❌ ERROR: Falló la instalación de dependencias
        pause
        exit /b 1
    )
) else (
    echo ⚠️ No se encontró requirements.txt
    echo 🔧 Instalando dependencias básicas...
    pip install fastapi uvicorn python-multipart opencv-python mysql-connector-python
)
echo.
echo ========================================
echo 🏁 INICIANDO SISTEMA BIOMÉTRICO
echo ========================================
echo.
echo ========================================
echo 🏁 INICIANDO SISTEMA BIOMÉTRICO
echo ========================================
echo.

REM Verificar si Python está instalado
python --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ ERROR: Python no está instalado o no está en el PATH
    echo 📥 Descarga Python desde: https://python.org
    pause
    exit /b 1
)

REM Verificar si Node.js está instalado (opcional)
node --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ⚠️ ADVERTENCIA: Node.js no está instalado
    echo 📦 Recomendado para desarrollo frontend
)

REM Crear directorio de logs si no existe
if not exist "logs" mkdir logs

REM Crear directorio de backups si no existe
if not exist "backups" mkdir backups

echo 📋 Verificando dependencias de Python...
echo.

REM Instalar dependencias de Python si requirements.txt existe
if exist "python_service\requirements.txt" (
    echo 🔧 Instalando dependencias de Python...
    pip install -r python_service\requirements.txt
    if %errorlevel% neq 0 (
        echo ❌ ERROR: Falló la instalación de dependencias
        pause
        exit /b 1
    )
) else (
    echo ⚠️ No se encontró requirements.txt
    echo 🔧 Instalando dependencias básicas...
    pip install fastapi uvicorn python-multipart opencv-python face-recognition mysql-connector-python
)

echo.
echo 🚀 Iniciando servicios...
echo.

REM Función para iniciar servicio en background
start "Python API Service" cmd /c "cd /d %~dp0python_service && python -m uvicorn app:app --host 127.0.0.1 --port 8000 --reload"

REM Esperar un momento para que el servicio inicie
timeout /t 3 /nobreak >nul

echo ✅ Servicios iniciados correctamente
echo.
echo 🌐 URLs disponibles:
echo    📊 Demo del Sistema: http://localhost/biometric_attendance_demo.html
echo    🎯 Sistema Completo: http://localhost/biometric_attendance_verification.html
echo    🔧 API Python: http://127.0.0.1:8000/docs
echo    📖 Documentación: BIOMETRIC_ATTENDANCE_README.md
echo.
echo 🧪 Para probar el sistema:
echo    📝 Ejecuta: python test_system_quick.py
echo.
echo ⏹️ Presiona Ctrl+C para detener todos los servicios
echo.

REM Mantener la ventana abierta
pause
