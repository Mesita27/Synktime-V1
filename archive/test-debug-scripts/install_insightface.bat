@echo off
echo ========================================
echo INSTALADOR AUTOMATICO DE INSIGHTFACE
echo ========================================
echo.

echo Verificando si Python esta disponible...
python --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Python no esta instalado o no esta en el PATH
    echo Por favor instala Python primero
    pause
    exit /b 1
)

echo Verificando si pip esta disponible...
pip --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: pip no esta disponible
    pause
    exit /b 1
)

echo.
echo Verificando si InsightFace ya esta instalado...
python -c "import insightface; print('InsightFace ya esta instalado')" >nul 2>&1
if %errorlevel% equ 0 (
    echo InsightFace ya esta instalado correctamente!
    goto :test_insightface
)

echo.
echo InsightFace no esta instalado. Iniciando instalacion...
echo.

echo ========================================
echo PASO 1: Verificar Microsoft Visual C++ Build Tools
echo ========================================

echo Verificando si Microsoft Visual C++ Build Tools esta instalado...
reg query "HKLM\SOFTWARE\Microsoft\VisualStudio\14.0\VC" >nul 2>&1
if %errorlevel% equ 0 (
    echo Microsoft Visual C++ Build Tools 2015 ya esta instalado
    goto :install_insightface
)

reg query "HKLM\SOFTWARE\Microsoft\VisualStudio\15.0\VC" >nul 2>&1
if %errorlevel% equ 0 (
    echo Microsoft Visual C++ Build Tools 2017 ya esta instalado
    goto :install_insightface
)

reg query "HKLM\SOFTWARE\Microsoft\VisualStudio\16.0\VC" >nul 2>&1
if %errorlevel% equ 0 (
    echo Microsoft Visual C++ Build Tools 2019 ya esta instalado
    goto :install_insightface
)

reg query "HKLM\SOFTWARE\Microsoft\VisualStudio\17.0\VC" >nul 2>&1
if %errorlevel% equ 0 (
    echo Microsoft Visual C++ Build Tools 2022 ya esta instalado
    goto :install_insightface
)

echo.
echo Microsoft Visual C++ Build Tools no encontrado
echo Descargando e instalando automaticamente...
echo.

echo ========================================
echo DESCARGANDO VISUAL STUDIO BUILD TOOLS
echo ========================================

echo Descargando Visual Studio Build Tools 2022...
powershell -Command "& {Invoke-WebRequest -Uri 'https://aka.ms/vs/17/release/vs_BuildTools.exe' -OutFile 'vs_BuildTools.exe'}"
if %errorlevel% neq 0 (
    echo ERROR: No se pudo descargar Visual Studio Build Tools
    echo Descargalo manualmente desde: https://visualstudio.microsoft.com/visual-cpp-build-tools/
    pause
    exit /b 1
)

echo.
echo Instalando Visual Studio Build Tools...
echo NOTA: La instalacion puede tomar varios minutos...
echo.

vs_BuildTools.exe --quiet --wait --norestart --nocache ^
    --installPath "%ProgramFiles(x86)%\Microsoft Visual Studio\2022\BuildTools" ^
    --add Microsoft.VisualStudio.Workload.VCTools ^
    --add Microsoft.VisualStudio.Component.VC.Tools.x86.x64 ^
    --add Microsoft.VisualStudio.Component.Windows10SDK.19041

if %errorlevel% neq 0 (
    echo ERROR: Fallo la instalacion de Visual Studio Build Tools
    echo Intenta instalarlo manualmente desde: https://visualstudio.microsoft.com/visual-cpp-build-tools/
    pause
    exit /b 1
)

echo.
echo Visual Studio Build Tools instalado correctamente!
echo.

:install_insightface
echo ========================================
echo PASO 2: Instalar InsightFace
echo ========================================

echo Actualizando pip...
python -m pip install --upgrade pip

echo.
echo Instalando InsightFace...
echo NOTA: La instalacion puede tomar varios minutos...
echo.

pip install insightface

if %errorlevel% neq 0 (
    echo.
    echo ERROR: Fallo la instalacion de InsightFace
    echo Intentando con metodo alternativo...
    echo.

    pip install insightface --no-cache-dir --force-reinstall

    if %errorlevel% neq 0 (
        echo.
        echo ERROR: No se pudo instalar InsightFace
        echo Posibles soluciones:
        echo 1. Reinicia la computadora y ejecuta este script nuevamente
        echo 2. Instala manualmente desde: https://github.com/deepinsight/insightface
        echo 3. Usa una version precompilada si esta disponible
        pause
        exit /b 1
    )
)

:test_insightface
echo.
echo ========================================
echo PASO 3: Verificar instalacion
echo ========================================

echo Verificando que InsightFace funcione correctamente...
python -c "import insightface; from insightface.app import FaceAnalysis; print('✓ InsightFace importado correctamente'); print('✓ FaceAnalysis disponible'); print('✓ Instalacion exitosa!')"

if %errorlevel% equ 0 (
    echo.
    echo ========================================
    echo ¡INSTALACION COMPLETADA EXITOSAMENTE!
    echo ========================================
    echo.
    echo InsightFace esta listo para usar en tu sistema de reconocimiento facial
    echo Ahora puedes ejecutar tu aplicacion Python
    echo.
) else (
    echo.
    echo ERROR: InsightFace no funciona correctamente
    echo Revisa los mensajes de error arriba
    pause
    exit /b 1
)

echo.
echo Presiona cualquier tecla para continuar...
pause >nul
