@echo off
echo ========================================
echo SNKTIME - SOLUCION COMPLETA DE RECONOCIMIENTO FACIAL
echo ========================================
echo.
echo Esta solucion resuelve el problema de que el sistema
echo aceptaba cualquier rostro con alta confianza.
echo.
echo OPCIONES DISPONIBLES:
echo 1. Usar Servicio Facial Mejorado (RECOMENDADO)
echo 2. Intentar instalar InsightFace nuevamente
echo 3. Intentar instalar face_recognition
echo 4. Ver estado actual del sistema
echo.

:menu
set /p choice="Seleccione una opcion (1-4): "

if "%choice%"=="1" goto enhanced_service
if "%choice%"=="2" goto insightface_install
if "%choice%"=="3" goto face_recognition_install
if "%choice%"=="4" goto check_status
echo Opcion invalida. Intente nuevamente.
goto menu

:enhanced_service
echo.
echo ========================================
echo USANDO SERVICIO FACIAL MEJORADO
echo ========================================
echo.
echo Este servicio usa caracteristicas avanzadas de OpenCV
echo para una mejor distincion entre personas.
echo.
echo Ventajas:
echo - No requiere compilacion compleja
echo - Funciona con OpenCV estandar
echo - Mejor precision que el metodo anterior
echo - Mas rapido y eficiente
echo.

cd /d "c:\Users\datam\Downloads\SNKTIME-copilot-fix-e94edcf3-8226-467e-8120-88ef6f42ba64"

echo Probando servicio mejorado...
python test_enhanced_service.py

if %errorlevel% equ 0 (
    echo.
    echo Ô£à SERVICIO MEJORADO FUNCIONANDO CORRECTAMENTE!
    echo.
    echo Para usar en produccion:
    echo 1. El servicio ya esta integrado en el sistema
    echo 2. Reinicie el servidor Python si esta ejecutandose
    echo 3. Las verificaciones ahora seran mas precisas
) else (
    echo.
    echo ÔØî Error con el servicio mejorado
    echo Intentando solucion automatica...
    call install_enhanced_recognition.bat
)
goto end

:insightface_install
echo.
echo ========================================
echo INSTALANDO INSIGHTFACE
echo ========================================
echo.
cd /d "c:\Users\datam\Downloads\SNKTIME-copilot-fix-e94edcf3-8226-467e-8120-88ef6f42ba64"

if exist install_insightface_simple.bat (
    call install_insightface_simple.bat
) else (
    echo ÔØî Script de instalacion no encontrado
    echo Use: pip install insightface
)
goto end

:face_recognition_install
echo.
echo ========================================
echo INSTALANDO FACE_RECOGNITION
echo ========================================
echo.
cd /d "c:\Users\datam\Downloads\SNKTIME-copilot-fix-e94edcf3-8226-467e-8120-88ef6f42ba64"

if exist install_face_recognition.bat (
    call install_face_recognition.bat
) else (
    echo ÔØî Script de instalacion no encontrado
    echo Use: pip install face-recognition
)
goto end

:check_status
echo.
echo ========================================
echo VERIFICANDO ESTADO DEL SISTEMA
echo ========================================
echo.
cd /d "c:\Users\datam\Downloads\SNKTIME-copilot-fix-e94edcf3-8226-467e-8120-88ef6f42ba64"

echo Verificando Python...
python --version
if %errorlevel% neq 0 (
    echo ÔØî Python no encontrado
    goto end
)

echo.
echo Verificando dependencias...
python -c "import cv2; print('OpenCV: OK')" 2>nul
python -c "import numpy; print('NumPy: OK')" 2>nul

echo.
echo Verificando servicios faciales...
python -c "import insightface; print('InsightFace: DISPONIBLE')" 2>nul || echo "InsightFace: NO DISPONIBLE"

python -c "import face_recognition; print('face_recognition: DISPONIBLE')" 2>nul || echo "face_recognition: NO DISPONIBLE"

python -c "from facial_service_enhanced import EnhancedFacialRecognitionService; print('Servicio Mejorado: DISPONIBLE')" 2>nul || echo "Servicio Mejorado: NO DISPONIBLE"

echo.
echo Verificando archivos de configuracion...
if exist python_service\app.py (
    echo "Archivo principal: OK"
) else (
    echo "Archivo principal: FALTA"
)

if exist facial_service_enhanced.py (
    echo "Servicio mejorado: OK"
) else (
    echo "Servicio mejorado: FALTA"
)

goto end

:end
echo.
echo ========================================
echo PROCESO COMPLETADO
echo ========================================
echo.
echo Si tiene problemas, puede:
echo 1. Usar el Servicio Facial Mejorado (opcion 1)
echo 2. Revisar los logs de error
echo 3. Verificar la instalacion de Python y OpenCV
echo.
pause
