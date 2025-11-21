@echo off
REM SNKTIME Python Service - Windows Startup Script
REM Inicia el servicio de forma rapida y confiable

echo Iniciando SNKTIME Python Service...
echo =====================================

REM Obtener directorio del script
set "SCRIPT_DIR=%~dp0"
set "SERVICE_DIR=%SCRIPT_DIR:~0,-1%"

echo Directorio del servicio: %SERVICE_DIR%

REM Verificar si existe entorno virtual
if not exist "%SERVICE_DIR%\venv" (
    echo ERROR: Entorno virtual no encontrado. Ejecuta install_service.bat primero.
    pause
    exit /b 1
)

REM Activar entorno virtual
echo.
echo Activando entorno virtual...
call "%SERVICE_DIR%\venv\Scripts\activate.bat"

REM Verificar dependencias criticas
echo.
echo Verificando dependencias criticas...
python -c "import fastapi, uvicorn, cv2, numpy, pymysql" >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Dependencias faltantes. Ejecuta install_service.bat primero.
    pause
    exit /b 1
)
echo Dependencias verificadas

REM Verificar puerto disponible
echo.
echo Verificando puerto 8000...
netstat -an | find "8000" >nul 2>&1
if %errorlevel% equ 0 (
    echo Puerto 8000 ocupado. Intentando detener procesos...
    for /f "tokens=5" %%a in ('netstat -aon ^| find ":8000" ^| find "LISTENING"') do (
        taskkill /PID %%a /F >nul 2>&1
    )
    timeout /t 2 >nul
)

REM Ejecutar diagnostico rapido
echo.
echo Ejecutando diagnostico rapido...
python quick_diag.py

REM Iniciar servicio
echo.
echo Iniciando servicio...
echo Presiona Ctrl+C para detener
echo.
python app.py

pause
