@echo off
echo ========================================
echo INSTALACION SIMPLIFICADA DE INSIGHTFACE
echo ========================================
echo.

echo Este script instala InsightFace sin compilar extensiones C++
echo que requieren Microsoft Visual C++ Build Tools
echo.

echo Verificando Python...
python --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ ERROR: Python no esta disponible
    pause
    exit /b 1
)

echo ✅ Python encontrado
echo.

echo ========================================
echo METODO 1: Instalar desde GitHub (Recomendado)
echo ========================================

echo Descargando InsightFace desde GitHub...
pip install git+https://github.com/deepinsight/insightface.git

if %errorlevel% equ 0 (
    echo ✅ InsightFace instalado correctamente desde GitHub
    goto :test_installation
)

echo.
echo Metodo alternativo fallido, intentando metodo 2...
echo.

echo ========================================
echo METODO 2: Instalar version precompilada
echo ========================================

echo Buscando versiones precompiladas...
pip install insightface --only-binary=all

if %errorlevel% equ 0 (
    echo ✅ InsightFace instalado desde binarios precompilados
    goto :test_installation
)

echo.
echo Metodo 2 fallido, intentando metodo 3...
echo.

echo ========================================
echo METODO 3: Instalar con --no-build-isolation
echo ========================================

echo Intentando instalacion sin aislamiento de compilacion...
pip install insightface --no-build-isolation

if %errorlevel% equ 0 (
    echo ✅ InsightFace instalado con --no-build-isolation
    goto :test_installation
)

echo.
echo Todos los metodos automaticos fallaron
echo.

:test_installation
echo.
echo ========================================
echo VERIFICANDO INSTALACION
echo ========================================

python -c "
try:
    import insightface
    print('✅ InsightFace importado correctamente')
    from insightface.app import FaceAnalysis
    print('✅ FaceAnalysis disponible')
    print('✅ Instalacion exitosa!')
except ImportError as e:
    print('❌ Error al importar InsightFace:', e)
    exit(1)
except Exception as e:
    print('❌ Error general:', e)
    exit(1)
"

if %errorlevel% equ 0 (
    echo.
    echo ========================================
    echo ¡INSTALACION COMPLETADA EXITOSAMENTE!
    echo ========================================
    echo.
    echo InsightFace esta listo para usar
    echo Ahora puedes ejecutar tu sistema biométrico
    echo.
) else (
    echo.
    echo ❌ ERROR: InsightFace no funciona correctamente
    echo.
    echo SOLUCIONES MANUALES:
    echo.
    echo 1. Instalar Microsoft Visual C++ Build Tools:
    echo    https://visualstudio.microsoft.com/visual-cpp-build-tools/
    echo.
    echo 2. Luego ejecutar:
    echo    pip install insightface
    echo.
    echo 3. O usar version alternativa:
    echo    pip install git+https://github.com/deepinsight/insightface.git
    echo.
    pause
    exit /b 1
)

echo.
pause
