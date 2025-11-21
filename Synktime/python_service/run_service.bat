@echo off
REM SNKTIME Python Biometric Service Launcher
REM Windows batch script to run the biometric service

echo 🚀 Starting SNKTIME Python Biometric Service
echo ==============================================

REM Set the service directory
set SERVICE_DIR=%~dp0
cd /d %SERVICE_DIR%

REM Check if Python is installed
python --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Python is not installed or not in PATH
    echo Please install Python 3.8+ from https://python.org
    pause
    exit /b 1
)

REM Check Python version
for /f "tokens=2" %%i in ('python --version 2^>^&1') do set PYTHON_VERSION=%%i
echo 🐍 Python version: %PYTHON_VERSION%

REM Create virtual environment if it doesn't exist
if not exist venv (
    echo 🔧 Creating virtual environment...
    python -m venv venv
    if %errorlevel% neq 0 (
        echo ❌ Failed to create virtual environment
        pause
        exit /b 1
    )
    echo ✅ Virtual environment created
) else (
    echo ✅ Virtual environment already exists
)

REM Activate virtual environment
echo 🔧 Activating virtual environment...
call venv\Scripts\activate.bat
if %errorlevel% neq 0 (
    echo ❌ Failed to activate virtual environment
    pause
    exit /b 1
)

REM Install/update dependencies
echo 📦 Installing/updating dependencies...
pip install -r requirements.txt
if %errorlevel% neq 0 (
    echo ❌ Failed to install dependencies
    pause
    exit /b 1
)

REM Install InsightFace (optional, for facial recognition)
echo 📦 Installing InsightFace for facial recognition...
pip install insightface
if %errorlevel% neq 0 (
    echo ⚠️  InsightFace installation failed (optional)
    echo    Facial recognition will use fallback mode
)

REM Set environment variables
echo 🔧 Setting environment variables...
set PYTHONPATH=%SERVICE_DIR%
set SNKTIME_ENV=development

REM Start the service
echo 🚀 Starting biometric service...
echo.
echo Service will be available at:
echo   - Local:   http://localhost:8000
echo   - Docs:    http://localhost:8000/docs
echo   - Health:  http://localhost:8000/health
echo.
echo Press Ctrl+C to stop the service
echo.

python app.py

REM Deactivate virtual environment on exit
call venv\Scripts\deactivate.bat

echo.
echo Service stopped.
pause
