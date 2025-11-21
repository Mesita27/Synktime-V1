@echo off
echo ========================================
echo INSTALANDO DEPENDENCIAS PRECOMPILADAS
echo ========================================
echo.
echo Este script intenta instalar versiones precompiladas
echo que no requieren compilacion de C++
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
echo INTENTANDO INSTALACIONES PRECOMPILADAS
echo ========================================

echo 1. Intentando instalar dlib precompilado...
pip install dlib --user --only-binary=all

if %errorlevel% equ 0 (
    echo Ô£à dlib instalado correctamente
    goto :install_face_recognition
)

echo ÔØî dlib precompilado no disponible
echo Intentando instalar desde conda-forge...

echo 2. Intentando con conda (si esta disponible)...
conda --version >nul 2>&1
if %errorlevel% equ 0 (
    echo Ô£à Conda encontrado, instalando dlib...
    conda install -c conda-forge dlib -y
    if %errorlevel% equ 0 goto :install_face_recognition
)

echo ÔØî Conda no disponible o fallo instalacion

echo 3. Intentando alternativa: deepface...
echo DeepFace no requiere compilacion compleja
pip install deepface --user

if %errorlevel% equ 0 (
    echo Ô£à DeepFace instalado correctamente!
    goto :test_deepface
)

echo ÔØî Todas las alternativas fallaron

echo.
echo ========================================
echo SOLUCIONES MANUALES
echo ========================================
echo Si todas las opciones fallan:
echo.
echo 1. INSTALAR VISUAL STUDIO BUILD TOOLS:
echo    - Descargue desde: https://visualstudio.microsoft.com/visual-cpp-build-tools/
echo    - Seleccione "Desktop development with C++"
echo    - Instale
echo.
echo 2. LUEGO EJECUTE:
echo    pip install dlib --user
echo    pip install face-recognition --user
echo.
echo 3. O USE DOCKER:
echo    docker run -it python:3.9 pip install face-recognition
echo.
pause
exit /b 1

:install_face_recognition
echo.
echo Instalando face-recognition...
pip install face-recognition --user

if %errorlevel% neq 0 (
    echo ÔØî Error instalando face-recognition
    goto :manual_solutions
)

echo Ô£à face_recognition instalado correctamente!
goto :test_installation

:test_deepface
echo.
echo ========================================
echo PROBANDO DEEPFACE
echo ========================================
python -c "from deepface import DeepFace; print('Ô£à DeepFace funciona correctamente!')" 2>nul
if %errorlevel% equ 0 (
    echo Ô£à DeepFace esta listo para usar!
    goto :success
)

echo ÔØî Error con DeepFace
goto :manual_solutions

:test_installation
echo.
echo ========================================
echo PROBANDO FACE_RECOGNITION
echo ========================================
python -c "import face_recognition; print('Ô£à face_recognition funciona correctamente!')" 2>nul
if %errorlevel% equ 0 (
    echo Ô£à face_recognition esta listo para usar!
    goto :success
)

echo ÔØî Error importando face_recognition
goto :manual_solutions

:success
echo.
echo ========================================
echo INSTALACION EXITOSA
echo ========================================
echo Ô£à Sistema de reconocimiento facial instalado!
echo Ahora puede usar el sistema biometrico con reconocimiento real.
echo.
pause
exit /b 0

:manual_solutions
echo.
echo ========================================
echo INSTALACION FALLIDA
echo ========================================
echo ÔØî No se pudo instalar automaticamente
echo.
echo SOLUCIONES MANUALES:
echo 1. Instale Microsoft Visual C++ Build Tools
echo 2. Instale CMake desde https://cmake.org/download/
echo 3. Luego ejecute: pip install face-recognition --user
echo 4. O use DeepFace como alternativa
echo.
pause
exit /b 1
