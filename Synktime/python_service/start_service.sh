#!/bin/bash
# SNKTIME - Script para lanzar el servicio Python
# Asume que las dependencias ya están instaladas

set -e

# Colores
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

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

echo "🚀 SNKTIME - Lanzamiento del Servicio Python"
echo "==========================================="

# Verificar que estamos en el directorio del servicio Python
if [ ! -f "app.py" ]; then
    print_error "Ejecuta este script desde el directorio python_service"
    exit 1
fi

# Verificar entorno virtual
if [ ! -d "venv" ]; then
    print_error "Entorno virtual no encontrado. Ejecuta primero install_python_service.sh"
    exit 1
fi

# Verificar archivo .env
if [ ! -f ".env" ]; then
    print_error "Archivo .env no encontrado. Copia .env.example a .env y configura las credenciales"
    exit 1
fi

# Activar entorno virtual
print_status "Activando entorno virtual..."
source venv/bin/activate

# Verificar que el puerto 8000 esté disponible
if lsof -Pi :8000 -sTCP:LISTEN -t >/dev/null; then
    print_warning "Puerto 8000 ocupado, deteniendo servicio anterior..."
    pkill -f "uvicorn.*app:app" || true
    sleep 2
fi

# Crear directorio de logs si no existe
mkdir -p logs

# Iniciar servicio
print_status "Iniciando servicio Python..."
print_status "Host: 0.0.0.0"
print_status "Puerto: 8000"
print_status "Modo: reload (desarrollo)"

uvicorn app:app --host 0.0.0.0 --port 8000 --reload &
SERVICE_PID=$!

# Guardar PID
echo $SERVICE_PID > service.pid

print_success "Servicio iniciado con PID: $SERVICE_PID"
print_success "API disponible en: http://localhost:8000"
print_success "Documentación API: http://localhost:8000/docs"
print_success "Logs en tiempo real: tail -f logs/service.log"

# Verificar que el servicio esté funcionando
sleep 3
if curl -s http://localhost:8000/docs > /dev/null 2>&1; then
    print_success "✅ Servicio verificado y funcionando correctamente"
else
    print_warning "⚠️ Servicio iniciado pero aún no responde. Revisa los logs."
fi

echo ""
print_status "Para detener el servicio:"
print_status "  pkill -f uvicorn"
print_status "  # o usa: kill $SERVICE_PID"

# Mantener el script ejecutándose para ver logs en tiempo real
echo ""
print_status "Presiona Ctrl+C para detener el servicio"
wait $SERVICE_PID
