@echo off
echo ========================================
echo  DEPLOYMENT A SERVIDOR LAMP - SYNKTIME
echo ========================================

REM Configuracion del servidor
set SERVER_IP=tu_servidor_ip
set SERVER_USER=tu_usuario
set SERVER_PATH=/var/www/html/synktime
set LOCAL_PATH=C:\Users\datam\Downloads\Synktime

echo.
echo Configuracion actual:
echo - Servidor: %SERVER_IP%
echo - Usuario: %SERVER_USER%
echo - Ruta remota: %SERVER_PATH%
echo - Ruta local: %LOCAL_PATH%
echo.

REM Verificar si pscp esta disponible (PuTTY)
where pscp >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: pscp no encontrado. Instala PuTTY o usa otra opcion.
    echo Descarga PuTTY desde: https://www.putty.org/
    pause
    exit /b 1
)

echo ¿Deseas continuar con el deployment? (S/N)
set /p CONFIRM=
if /i "%CONFIRM%" NEQ "S" (
    echo Deployment cancelado.
    pause
    exit /b 0
)

echo.
echo === INICIANDO DEPLOYMENT ===

REM Crear backup en el servidor
echo 1. Creando backup en servidor...
plink -batch %SERVER_USER%@%SERVER_IP% "cd /var/www/html && tar -czf synktime_backup_$(date +%%Y%%m%%d_%%H%%M%%S).tar.gz synktime/ 2>/dev/null || echo 'Backup omitido - directorio no existe'"

REM Crear directorio si no existe
echo 2. Preparando directorio en servidor...
plink -batch %SERVER_USER%@%SERVER_IP% "mkdir -p %SERVER_PATH%"

REM Subir archivos (excluyendo archivos innecesarios)
echo 3. Subiendo archivos al servidor...
pscp -r -q ^
    -x "*.log" ^
    -x "*.tmp" ^
    -x "node_modules" ^
    -x ".git" ^
    -x "*.bat" ^
    -x "*.exe" ^
    %LOCAL_PATH%\* %SERVER_USER%@%SERVER_IP%:%SERVER_PATH%/

if %ERRORLEVEL% EQU 0 (
    echo.
    echo === DEPLOYMENT COMPLETADO ===
    echo Archivos subidos exitosamente a: %SERVER_IP%:%SERVER_PATH%
    
    REM Configurar permisos
    echo 4. Configurando permisos...
    plink -batch %SERVER_USER%@%SERVER_IP% "chmod -R 755 %SERVER_PATH% && chown -R www-data:www-data %SERVER_PATH%"
    
    echo.
    echo ✅ Deployment finalizado exitosamente
) else (
    echo.
    echo ❌ Error durante el deployment
)

echo.
pause