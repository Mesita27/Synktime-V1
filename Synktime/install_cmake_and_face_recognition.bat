@echo off
echo ========================================
echo INSTALANDO CMAKE Y DEPENDENCIAS
echo ========================================
echo.
echo Este script instala CMake y las herramientas necesarias
echo para compilar bibliotecas de reconocimiento facial
echo.

echo Verificando Python...
python --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ÔØî Python no encontrado. Instale Python primero.
    pause
    exit /b 1
)
echo Ô£à Python encontrado

echo.
echo ========================================
echo INSTALANDO CMAKE
echo ========================================
echo Instalando CMake via pip...
pip install cmake --user

echo.
echo Verificando CMake...
cmake --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ÔØî CMake no se pudo instalar automaticamente
    echo.
    echo SOLUCIONES MANUALES:
    echo 1. Descargue CMake desde: https://cmake.org/download/
    echo 2. Instale Microsoft Visual C++ Build Tools desde:
    echo    https://visualstudio.microsoft.com/visual-cpp-build-tools/
    echo 3. O use Visual Studio Community Edition
    echo.
    echo Luego ejecute: pip install face-recognition
    pause
    exit /b 1
)
echo Ô£à CMake instalado correctamente

echo.
echo ========================================
echo INSTALANDO FACE_RECOGNITION
echo ========================================
echo Instalando face_recognition...
pip install face-recognition --user

if %errorlevel% neq 0 (
    echo ÔØî Error instalando face-recognition
    echo.
    echo Intentando con --no-build-isolation...
    pip install face-recognition --user --no-build-isolation
)

if %errorlevel% neq 0 (
    echo ÔØî Error instalando face-recognition
    echo.
    echo SOLUCIONES:
    echo 1. Instale Microsoft Visual C++ Build Tools
    echo 2. O use: pip install dlib --user (primero)
    echo 3. Luego: pip install face-recognition --user
    pause
    exit /b 1
)

echo.
echo Ô£à face_recognition instalado correctamente!
echo.
echo ========================================
echo VERIFICANDO INSTALACION
echo ========================================
echo Probando importacion...
python -c "import face_recognition; print('Ô£à face_recognition funciona correctamente!')" 2>nul
if %errorlevel% neq 0 (
    echo ÔØî Error importando face_recognition
    pause
    exit /b 1
)

echo.
echo ========================================
echo INSTALACION COMPLETA
echo ========================================
echo Ô£à Todas las dependencias instaladas correctamente!
echo Ahora puede usar el sistema de reconocimiento facial.
echo.
pause
