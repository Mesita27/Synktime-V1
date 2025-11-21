@echo off
echo Iniciando servicio de Python para captura de fotos...
cd /d "%~dp0python_service"

echo Verificando si Python esta disponible...
python --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Python no esta instalado o no esta en el PATH
    pause
    exit /b 1
)

echo Verificando dependencias...
if not exist venv (
    echo Creando entorno virtual...
    python -m venv venv
)

echo Activando entorno virtual...
call venv\Scripts\activate.bat

echo Instalando dependencias...
pip install -r requirements.txt

echo Iniciando servicio en puerto 8001...
uvicorn app:app --host 0.0.0.0 --port 8001 --reload

pause
