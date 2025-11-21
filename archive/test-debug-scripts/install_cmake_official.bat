@echo off
echo ========================================
echo INSTALANDO CMAKE DESDE SITIO OFICIAL
echo ========================================
echo.
echo Descargando e instalando CMake...
echo.

cd /d "%TEMP%"

echo Descargando CMake...
powershell -Command "& {Invoke-WebRequest -Uri 'https://github.com/Kitware/CMake/releases/download/v3.28.0/cmake-3.28.0-windows-x86_64.zip' -OutFile 'cmake.zip'}"

if not exist cmake.zip (
    echo ÔØî Error descargando CMake
    pause
    exit /b 1
)

echo Extrayendo CMake...
powershell -Command "& {Expand-Archive -Path 'cmake.zip' -DestinationPath '.' -Force}"

if not exist cmake-3.28.0-windows-x86_64 (
    echo ÔØî Error extrayendo CMake
    pause
    exit /b 1
)

echo Copiando CMake a directorio del sistema...
if not exist "C:\CMake" mkdir "C:\CMake"
xcopy "cmake-3.28.0-windows-x86_64\bin\*" "C:\CMake\" /E /I /Y
xcopy "cmake-3.28.0-windows-x86_64\share\*" "C:\CMake\share\" /E /I /Y

echo Agregando CMake al PATH...
setx PATH "%PATH%;C:\CMake" /M

echo Verificando instalacion...
"C:\CMake\cmake.exe" --version
if %errorlevel% neq 0 (
    echo ÔØî Error verificando CMake
    pause
    exit /b 1
)

echo Ô£à CMake instalado correctamente!

echo.
echo ========================================
echo INSTALANDO FACE_RECOGNITION
echo ========================================
cd /d "c:\Users\datam\Downloads\SNKTIME-copilot-fix-e94edcf3-8226-467e-8120-88ef6f42ba64"

echo Instalando face_recognition...
pip install face-recognition --user

if %errorlevel% neq 0 (
    echo ÔØî Error instalando face-recognition
    echo Intentando con --no-build-isolation...
    pip install face-recognition --user --no-build-isolation
)

if %errorlevel% neq 0 (
    echo ÔØî Error instalando face-recognition
    echo.
    echo SOLUCION MANUAL:
    echo 1. Reinicie la terminal para que el PATH se actualice
    echo 2. Ejecute: pip install face-recognition --user
    echo 3. Si falla, instale Microsoft Visual C++ Build Tools
    pause
    exit /b 1
)

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
