@echo off
echo ========================================
echo INSTALACION ALTERNATIVA: face_recognition
echo ========================================
echo.

echo Esta opcion instala face_recognition en lugar de InsightFace
echo face_recognition es mas facil de instalar y tambien funciona bien
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
echo INSTALANDO face_recognition
echo ========================================

echo Instalando face_recognition...
pip install face-recognition

if %errorlevel% neq 0 (
    echo ❌ Error instalando face-recognition
    echo Intentando con dlib incluido...
    pip install face-recognition dlib
)

if %errorlevel% neq 0 (
    echo ❌ Error instalando face-recognition con dlib
    echo Intentando version binaria...
    pip install face-recognition --only-binary=all
)

if %errorlevel% neq 0 (
    echo ❌ Todos los metodos fallaron
    echo.
    echo SOLUCIONES:
    echo 1. Instalar CMake: pip install cmake
    echo 2. Instalar Microsoft Visual C++ Build Tools
    echo 3. Luego: pip install face-recognition
    echo.
    pause
    exit /b 1
)

echo.
echo ✅ face_recognition instalado correctamente
echo.

echo ========================================
echo VERIFICANDO INSTALACION
echo ========================================

python -c "
try:
    import face_recognition
    print('✅ face_recognition importado correctamente')
    print('✅ Instalacion exitosa!')
except ImportError as e:
    print('❌ Error al importar face_recognition:', e)
    exit(1)
except Exception as e:
    print('❌ Error general:', e)
    exit(1)
"

if %errorlevel% equ 0 (
    echo.
    echo ========================================
    echo ¡INSTALACION COMPLETADA!
    echo ========================================
    echo.
    echo face_recognition esta listo para usar
    echo Esta libreria proporciona reconocimiento facial confiable
    echo.
) else (
    echo.
    echo ❌ ERROR: face_recognition no funciona correctamente
    pause
    exit /b 1
)

echo.
echo NOTA: Ahora necesitas actualizar tu codigo para usar face_recognition
echo en lugar de InsightFace. Es una libreria diferente pero compatible.
echo.
pause
