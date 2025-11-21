#!/bin/bash
# Script de Inicio Rápido - Sistema Biométrico de Asistencia
# Versión: 1.0.0
# Fecha: 2024
# Plataforma: Linux/Mac

echo ""
echo "========================================"
echo "🏁 INICIANDO SISTEMA BIOMÉTRICO"
echo "========================================"
echo ""

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Función para imprimir mensajes coloreados
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Verificar si Python está instalado
if ! command -v python3 &> /dev/null && ! command -v python &> /dev/null; then
    print_error "Python no está instalado o no está en el PATH"
    print_status "Instala Python desde: https://python.org"
    exit 1
fi

# Usar python3 si está disponible, sino python
if command -v python3 &> /dev/null; then
    PYTHON_CMD="python3"
else
    PYTHON_CMD="python"
fi

print_status "Usando Python: $($PYTHON_CMD --version)"

# Verificar si Node.js está instalado (opcional)
if ! command -v node &> /dev/null; then
    print_warning "Node.js no está instalado"
    print_status "Recomendado para desarrollo frontend"
fi

# Crear directorios necesarios
print_status "Creando directorios necesarios..."
mkdir -p logs
mkdir -p backups

# Instalar dependencias de Python
print_status "Verificando dependencias de Python..."

if [ -f "python_service/requirements.txt" ]; then
    print_status "Instalando dependencias de Python..."
    $PYTHON_CMD -m pip install -r python_service/requirements.txt
    if [ $? -ne 0 ]; then
        print_error "Falló la instalación de dependencias"
        exit 1
    fi
else
    print_warning "No se encontró requirements.txt"
    print_status "Instalando dependencias básicas..."
    $PYTHON_CMD -m pip install fastapi uvicorn python-multipart opencv-python face-recognition mysql-connector-python
fi

print_success "Dependencias instaladas correctamente"

echo ""
print_status "🚀 Iniciando servicios..."
echo ""

# Función para verificar si un puerto está en uso
check_port() {
    if lsof -Pi :$1 -sTCP:LISTEN -t >/dev/null ; then
        return 0
    else
        return 1
    fi
}

# Verificar si el puerto 8000 está en uso
if check_port 8000; then
    print_warning "El puerto 8000 ya está en uso"
    print_status "Deteniendo proceso existente..."
    lsof -ti:8000 | xargs kill -9 2>/dev/null
    sleep 2
fi

# Iniciar servicio Python en background
print_status "Iniciando servicio Python (FastAPI)..."
cd python_service
$PYTHON_CMD -m uvicorn app:app --host 127.0.0.1 --port 8000 --reload &
PYTHON_PID=$!

# Regresar al directorio raíz
cd ..

# Esperar un momento para que el servicio inicie
sleep 3

# Verificar que el servicio esté ejecutándose
if check_port 8000; then
    print_success "Servicio Python iniciado correctamente (PID: $PYTHON_PID)"
else
    print_error "Falló al iniciar el servicio Python"
    exit 1
fi

print_success "Todos los servicios iniciados correctamente"
echo ""
echo "🌐 URLs disponibles:"
echo "    📊 Demo del Sistema: http://localhost/biometric_attendance_demo.html"
echo "    🎯 Sistema Completo: http://localhost/biometric_attendance_verification.html"
echo "    🔧 API Python: http://127.0.0.1:8000/docs"
echo "    📖 Documentación: BIOMETRIC_ATTENDANCE_README.md"
echo ""
echo "🧪 Para probar el sistema:"
echo "    📝 Ejecuta: python3 test_system_quick.py"
echo ""
echo "⏹️ Presiona Ctrl+C para detener todos los servicios"
echo ""

# Función de limpieza al salir
cleanup() {
    echo ""
    print_status "Deteniendo servicios..."
    if kill -0 $PYTHON_PID 2>/dev/null; then
        kill $PYTHON_PID
        print_success "Servicio Python detenido"
    fi
    print_success "Sistema detenido correctamente"
    exit 0
}

# Configurar trap para Ctrl+C
trap cleanup SIGINT SIGTERM

# Mantener el script ejecutándose
print_status "Sistema ejecutándose. Presiona Ctrl+C para detener..."
while true; do
    sleep 1
done
