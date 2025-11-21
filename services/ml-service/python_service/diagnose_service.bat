@echo off
REM SNKTIME Python Service - Windows Diagnostic Script
REM Diagnóstico completo del servicio biométrico

echo 🔍 SNKTIME Python Service - Diagnóstico para Windows
echo ======================================================

REM Obtener directorio del script
set "SCRIPT_DIR=%~dp0"
set "SERVICE_DIR=%SCRIPT_DIR:~0,-1%"

echo 📁 Directorio del servicio: %SERVICE_DIR%

REM Verificar Python
echo.
echo 🐍 Verificando Python...
python --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ ERROR: Python no encontrado
    echo    Solución: Instala Python 3.8+ desde https://python.org
    goto :error
)

for /f "tokens=2" %%i in ('python --version 2^>^&1') do set PYTHON_VERSION=%%i
echo ✅ Python encontrado: %PYTHON_VERSION%

REM Verificar versión de Python
python -c "import sys; v=sys.version_info; exit(0 if (v.major==3 and v.minor>=8) else 1)" >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ ERROR: Python versión muy antigua (requiere 3.8+)
    goto :error
)
echo ✅ Versión de Python compatible

REM Verificar entorno virtual
echo.
echo 🔧 Verificando entorno virtual...
if not exist "%SERVICE_DIR%\venv" (
    echo ❌ ERROR: Entorno virtual no encontrado
    echo    Solución: Ejecuta install_service.bat
    goto :error
)
echo ✅ Entorno virtual encontrado

REM Activar entorno virtual
call "%SERVICE_DIR%\venv\Scripts\activate.bat" >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ ERROR: No se pudo activar entorno virtual
    goto :error
)
echo ✅ Entorno virtual activado

REM Verificar dependencias críticas
echo.
echo 📦 Verificando dependencias críticas...

python -c "import fastapi" >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ ERROR: FastAPI no instalado
    goto :error
)
echo ✅ FastAPI disponible

python -c "import uvicorn" >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ ERROR: Uvicorn no instalado
    goto :error
)
echo ✅ Uvicorn disponible

python -c "import cv2" >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ ERROR: OpenCV no instalado
    goto :error
)
echo ✅ OpenCV disponible

python -c "import numpy" >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ ERROR: NumPy no instalado
    goto :error
)
echo ✅ NumPy disponible

python -c "import pymysql" >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ ERROR: PyMySQL no instalado
    goto :error
)
echo ✅ PyMySQL disponible

REM Verificar dependencias opcionales
echo.
echo 📦 Verificando dependencias opcionales...

python -c "import insightface" >nul 2>&1
if %errorlevel% neq 0 (
    echo ⚠️  InsightFace no disponible (funcionalidad limitada)
) else (
    echo ✅ InsightFace disponible
)

python -c "import onnxruntime" >nul 2>&1
if %errorlevel% neq 0 (
    echo ⚠️  ONNX Runtime no disponible
) else (
    echo ✅ ONNX Runtime disponible
)

REM Verificar puerto
echo.
echo 🔌 Verificando puerto 8000...
netstat -an | find "8000" >nul 2>&1
if %errorlevel% equ 0 (
    echo ⚠️  Puerto 8000 ocupado
    echo    Intentando identificar proceso...
    for /f "tokens=5" %%a in ('netstat -aon ^| find ":8000" ^| find "LISTENING"') do (
        echo    PID: %%a
        tasklist /FI "PID eq %%a" 2>nul | find "%%a"
    )
) else (
    echo ✅ Puerto 8000 disponible
)

REM Verificar archivos del servicio
echo.
echo 📁 Verificando archivos del servicio...

if not exist "%SERVICE_DIR%\app.py" (
    echo ❌ ERROR: app.py no encontrado
    goto :error
)
echo ✅ app.py encontrado

if not exist "%SERVICE_DIR%\services" (
    echo ❌ ERROR: Directorio services no encontrado
    goto :error
)
echo ✅ Directorio services encontrado

if not exist "%SERVICE_DIR%\config" (
    echo ❌ ERROR: Directorio config no encontrado
    goto :error
)
echo ✅ Directorio config encontrado

REM Verificar configuración
echo.
echo ⚙️  Verificando configuración...
if not exist "%SERVICE_DIR%\.env" (
    echo ⚠️  Archivo .env no encontrado (usando valores por defecto)
) else (
    echo ✅ Archivo .env encontrado
)

REM Verificar conectividad de base de datos
echo.
echo 🗄️  Verificando conectividad de base de datos...
python -c "
import os
from dotenv import load_dotenv
load_dotenv()

try:
    import pymysql
    host = os.getenv('DB_HOST', 'localhost')
    port = int(os.getenv('DB_PORT', 3306))
    user = os.getenv('DB_USER', 'root')
    password = os.getenv('DB_PASSWORD', '')
    db = os.getenv('DB_NAME', 'synktime')

    conn = pymysql.connect(
        host=host,
        port=port,
        user=user,
        password=password,
        database=db,
        connect_timeout=5
    )
    conn.close()
    print('✅ Conexión a base de datos exitosa')
except Exception as e:
    print('⚠️  No se pudo conectar a base de datos:', str(e))
" 2>nul

REM Verificar dispositivos conectados
echo.
echo 🔌 Verificando dispositivos conectados...
python -c "
import os
import serial.tools.list_ports

print('Puertos serie disponibles:')
ports = list(serial.tools.list_ports.comports())
if ports:
    for port in ports:
        print(f'  - {port.device}: {port.description}')
else:
    print('  Ninguno')

# Verificar dispositivos USB
try:
    import pyusb.core as usb
    devices = usb.find(find_all=True)
    usb_count = len(list(devices))
    print(f'Dispositivos USB encontrados: {usb_count}')
except ImportError:
    print('⚠️  pyusb no disponible (instala con: pip install pyusb)')
except Exception as e:
    print(f'⚠️  Error al verificar USB: {e}')
" 2>nul

echo.
echo 🎉 Diagnóstico completado exitosamente!
echo.
echo 💡 Si hay errores arriba, revisa las soluciones en README.md
echo.
echo 🚀 Para iniciar el servicio:
echo    start_service.bat
echo.
pause
exit /b 0

:error
echo.
echo ❌ Diagnóstico falló. Revisa los errores arriba.
echo.
pause
exit /b 1
