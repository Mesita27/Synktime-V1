#!/bin/bash
# SNKTIME - Script de Despliegue para Ubuntu 24.04
# Despliega el sistema completo (PHP + Python) en servidor LAMP

set -e

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

echo "🚀 SNKTIME - Despliegue en Ubuntu 24.04"
echo "========================================"

# Verificar que estamos en Ubuntu
if ! grep -q "Ubuntu" /etc/os-release; then
    print_error "Este script está diseñado para Ubuntu"
    exit 1
fi

UBUNTU_VERSION=$(grep "VERSION_ID" /etc/os-release | cut -d'"' -f2)
print_status "Ubuntu versión detectada: $UBUNTU_VERSION"

# Verificar componentes LAMP
print_status "Verificando componentes LAMP instalados..."

# Verificar Apache
if systemctl is-active --quiet apache2; then
    print_success "Apache está ejecutándose"
else
    print_warning "Apache no está ejecutándose"
fi

# Verificar MySQL
if systemctl is-active --quiet mysql; then
    print_success "MySQL está ejecutándose"
else
    print_warning "MySQL no está ejecutándose"
fi

# Verificar PHP
if command -v php &> /dev/null; then
    PHP_VERSION=$(php --version | head -n 1 | cut -d' ' -f2)
    print_success "PHP instalado: $PHP_VERSION"
else
    print_error "PHP no está instalado"
    exit 1
fi

# Verificar Python
if command -v python3 &> /dev/null; then
    PYTHON_VERSION=$(python3 --version | cut -d' ' -f2)
    print_success "Python instalado: $PYTHON_VERSION"
else
    print_error "Python3 no está instalado"
    exit 1
fi

# Verificar pip
if command -v pip3 &> /dev/null; then
    print_success "pip3 está disponible"
else
    print_error "pip3 no está instalado"
    exit 1
fi

# Obtener directorio del proyecto
PROJECT_DIR="$(pwd)"
PYTHON_SERVICE_DIR="$PROJECT_DIR/python_service"

print_status "Directorio del proyecto: $PROJECT_DIR"
print_status "Directorio del servicio Python: $PYTHON_SERVICE_DIR"

# Cambiar a directorio del servicio Python
cd "$PYTHON_SERVICE_DIR"

# Instalar dependencias del sistema para InsightFace y OpenCV
print_status "Instalando dependencias del sistema..."

sudo apt update

# Dependencias básicas
sudo apt install -y \
    python3-dev \
    python3-pip \
    python3-venv \
    build-essential \
    cmake \
    git \
    libgl1-mesa-glx \
    libglib2.0-0 \
    libsm6 \
    libxext6 \
    libxrender-dev \
    libgomp1 \
    libgthread-2.0-0 \
    libgtk2.0-dev \
    pkg-config

# Dependencias para InsightFace
sudo apt install -y \
    libopenblas-dev \
    liblapack-dev \
    libjpeg-dev \
    libpng-dev \
    libtiff-dev \
    libavcodec-dev \
    libavformat-dev \
    libswscale-dev \
    libv4l-dev \
    libxvidcore-dev \
    libx264-dev \
    libgtk-3-dev \
    libatlas-base-dev \
    gfortran

print_success "Dependencias del sistema instaladas"

# Crear entorno virtual
print_status "Configurando entorno virtual Python..."

if [ ! -d "venv" ]; then
    python3 -m venv venv
    print_success "Entorno virtual creado"
else
    print_warning "Entorno virtual ya existe"
fi

# Activar entorno virtual
source venv/bin/activate

# Actualizar pip
pip install --upgrade pip

# Instalar dependencias Python
print_status "Instalando dependencias Python..."

# Instalar numpy primero (dependencia crítica)
pip install numpy==1.24.3

# Instalar opencv sin GUI
pip install opencv-python-headless==4.8.0.76

# Instalar otras dependencias
pip install -r requirements.txt

print_success "Dependencias Python instaladas"

# Verificar instalación crítica
print_status "Verificando instalación crítica..."

python3 -c "import cv2; print('OpenCV:', cv2.__version__)" || print_error "Error con OpenCV"
python3 -c "import numpy; print('NumPy:', numpy.__version__)" || print_error "Error con NumPy"

# Configurar archivo .env
print_status "Configurando archivo de entorno..."

if [ ! -f ".env" ]; then
    cp .env.example .env
    print_success "Archivo .env creado desde .env.example"
else
    print_warning "Archivo .env ya existe"
fi

# Verificar que el archivo .env tenga las credenciales correctas
if grep -q "DB_PASSWORD=Miau\$210718" .env; then
    print_success "Credenciales de base de datos configuradas correctamente"
else
    print_warning "Verifica las credenciales en el archivo .env"
fi

# Crear directorios necesarios
print_status "Creando directorios necesarios..."

mkdir -p logs
mkdir -p models
mkdir -p ../uploads

print_success "Directorios creados"

# Configurar permisos
print_status "Configurando permisos..."

chmod +x *.sh
chmod +x *.bat

# Configurar firewall para DigitalOcean
print_status "Configurando firewall para DigitalOcean..."

# Habilitar UFW si no está habilitado
sudo ufw --force enable

# Permitir SSH (importante para no perder conexión)
sudo ufw allow ssh
sudo ufw allow 22

# Permitir HTTP y HTTPS
sudo ufw allow 80
sudo ufw allow 443

# Permitir puerto del servicio Python
sudo ufw allow 8000

# Recargar firewall
sudo ufw reload

print_success "Firewall configurado"

# Configurar Apache para el proyecto PHP
print_status "Configurando Apache..."

# Crear configuración de sitio
cat > /tmp/synktime.conf << EOF
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot $PROJECT_DIR

    <Directory $PROJECT_DIR>
        AllowOverride All
        Require all granted
        Options Indexes FollowSymLinks
        php_value upload_max_filesize 50M
        php_value post_max_size 50M
        php_value memory_limit 256M
        php_value max_execution_time 300
    </Directory>

    # Configuración para API
    Alias /api $PROJECT_DIR/api
    <Directory $PROJECT_DIR/api>
        AllowOverride All
        Require all granted
    </Directory>

    # Configuración para uploads
    Alias /uploads $PROJECT_DIR/uploads
    <Directory $PROJECT_DIR/uploads>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/synktime_error.log
    CustomLog \${APACHE_LOG_DIR}/synktime_access.log combined
</VirtualHost>
EOF

sudo mv /tmp/synktime.conf /etc/apache2/sites-available/synktime.conf
sudo a2ensite synktime.conf
sudo a2enmod rewrite
sudo systemctl reload apache2

print_success "Apache configurado"

# Crear script de inicio del servicio Python
print_status "Creando script de inicio del servicio Python..."

cat > start_synktime_service.sh << 'EOF'
#!/bin/bash
# Script para iniciar el servicio Python de SNKTIME

SERVICE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PYTHON_SERVICE_DIR="$SERVICE_DIR/python_service"

cd "$PYTHON_SERVICE_DIR"

# Verificar si systemd está disponible (DigitalOcean)
if command -v systemctl &> /dev/null; then
    echo "Usando systemd para iniciar el servicio..."
    sudo systemctl start synktime-python
    sudo systemctl status synktime-python --no-pager
    echo ""
    echo "Comandos útiles:"
    echo "  sudo systemctl status synktime-python"
    echo "  sudo systemctl stop synktime-python"
    echo "  sudo systemctl restart synktime-python"
    echo "  journalctl -u synktime-python -f"
else
    # Fallback para sistemas sin systemd
    echo "Systemd no disponible, iniciando manualmente..."
    source venv/bin/activate
    nohup uvicorn app:app --host 0.0.0.0 --port 8000 --reload > logs/service.log 2>&1 &
    SERVICE_PID=$!
    echo "Servicio iniciado con PID: $SERVICE_PID"
    echo $SERVICE_PID > service.pid
fi

echo "API disponible en: http://localhost:8000"
echo "Documentación: http://localhost:8000/docs"
EOF

chmod +x start_synktime_service.sh

# Crear script para detener el servicio
cat > stop_synktime_service.sh << 'EOF'
#!/bin/bash
# Script para detener el servicio Python de SNKTIME

SERVICE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PID_FILE="$SERVICE_DIR/python_service/service.pid"

if [ -f "$PID_FILE" ]; then
    PID=$(cat "$PID_FILE")
    if kill -0 $PID 2>/dev/null; then
        echo "Deteniendo servicio con PID: $PID"
        kill $PID
        sleep 2
        if kill -0 $PID 2>/dev/null; then
            echo "Forzando terminación..."
            kill -9 $PID
        fi
    else
        echo "Servicio no está ejecutándose"
    fi
    rm -f "$PID_FILE"
else
    echo "Archivo PID no encontrado. Buscando procesos..."
    pkill -f "uvicorn.*app:app" || echo "No se encontraron procesos del servicio"
fi

echo "Servicio detenido"
EOF

chmod +x stop_synktime_service.sh

print_success "Scripts de control del servicio creados"

# Configurar MySQL para DigitalOcean
print_status "Configurando MySQL para entorno de nube..."

# Asegurar que MySQL esté configurado para conexiones locales seguras
sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'Miau\$210718';"
sudo mysql -e "FLUSH PRIVILEGES;"

# Crear base de datos si no existe
sudo mysql -u root -p'Miau$210718' -e "CREATE DATABASE IF NOT EXISTS synktime CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

print_success "MySQL configurado"

# Crear servicio systemd para el servicio Python
print_status "Creando servicio systemd para Python..."

cat > /tmp/synktime-python.service << EOF
[Unit]
Description=SNKTIME Python Biometric Service
After=network.target mysql.service
Requires=mysql.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=$PYTHON_SERVICE_DIR
Environment=PATH=$PYTHON_SERVICE_DIR/venv/bin
ExecStart=$PYTHON_SERVICE_DIR/venv/bin/uvicorn app:app --host 0.0.0.0 --port 8000
Restart=always
RestartSec=5
StandardOutput=journal
StandardError=journal
SyslogIdentifier=synktime-python

[Install]
WantedBy=multi-user.target
EOF

sudo mv /tmp/synktime-python.service /etc/systemd/system/synktime-python.service
sudo systemctl daemon-reload
sudo systemctl enable synktime-python

print_success "Servicio systemd creado"

# Configurar logrotate para logs del servicio
print_status "Configurando rotación de logs..."

cat > /tmp/synktime-logs << EOF
$PYTHON_SERVICE_DIR/logs/*.log {
    daily
    missingok
    rotate 7
    compress
    delaycompress
    notifempty
    create 0644 www-data www-data
    postrotate
        systemctl reload synktime-python || true
    endscript
}
EOF

sudo mv /tmp/synktime-logs /etc/logrotate.d/synktime
sudo chmod 644 /etc/logrotate.d/synktime

print_success "Rotación de logs configurada"

# Probar la instalación
print_status "Probando la instalación..."

# Probar importaciones críticas
python3 -c "
try:
    import cv2
    import numpy as np
    import pymysql
    from fastapi import FastAPI
    print('✅ Todas las dependencias críticas instaladas correctamente')
except ImportError as e:
    print(f'❌ Error de importación: {e}')
    exit(1)
"

print_success "Pruebas de instalación completadas"

# Crear script de verificación del sistema
print_status "Creando script de verificación del sistema..."

cat > check_system.sh << 'EOF'
#!/bin/bash
# Script para verificar el estado del sistema SNKTIME en DigitalOcean

echo "🔍 Verificación del Sistema SNKTIME - DigitalOcean"
echo "=================================================="

# Verificar servicios del sistema
echo "📊 Estado de servicios del sistema:"
if systemctl is-active --quiet apache2; then
    echo "✅ Apache: Ejecutándose"
else
    echo "❌ Apache: Detenido"
fi

if systemctl is-active --quiet mysql; then
    echo "✅ MySQL: Ejecutándose"
else
    echo "❌ MySQL: Detenido"
fi

if systemctl is-active --quiet synktime-python; then
    echo "✅ Servicio Python (systemd): Ejecutándose"
else
    echo "❌ Servicio Python (systemd): Detenido"
fi

# Verificar firewall
echo ""
echo "🔥 Estado del Firewall:"
sudo ufw status | grep -E "(Status|80|443|8000|22)" | head -10

# Verificar servicio Python alternativo
if ! systemctl is-active --quiet synktime-python; then
    echo ""
    echo "🔍 Verificando servicio Python alternativo:"
    if lsof -Pi :8000 -sTCP:LISTEN -t >/dev/null; then
        echo "✅ Servicio Python (manual): Ejecutándose en puerto 8000"
    else
        echo "❌ Servicio Python: No ejecutándose"
    fi
fi

# Verificar archivos críticos
echo ""
echo "📁 Archivos críticos:"
if [ -f ".env" ]; then
    echo "✅ Archivo .env: Presente"
else
    echo "❌ Archivo .env: Faltante"
fi

if [ -d "venv" ]; then
    echo "✅ Entorno virtual: Presente"
else
    echo "❌ Entorno virtual: Faltante"
fi

if [ -d "models" ]; then
    echo "✅ Directorio models: Presente"
else
    echo "❌ Directorio models: Faltante"
fi

# Verificar conectividad
echo ""
echo "🌐 Conectividad:"
if curl -s --max-time 10 http://localhost > /dev/null; then
    echo "✅ Sitio web PHP: Accesible"
else
    echo "❌ Sitio web PHP: No accesible"
fi

if curl -s --max-time 10 http://localhost:8000/docs > /dev/null; then
    echo "✅ API Python: Accesible"
else
    echo "❌ API Python: No accesible"
fi

# Verificar base de datos
echo ""
echo "🗄️ Base de datos:"
if mysql -u root -p'Miau$210718' -e "SELECT 1;" 2>/dev/null; then
    echo "✅ Conexión MySQL: Exitosa"
else
    echo "❌ Conexión MySQL: Fallida"
fi

# Información del sistema
echo ""
echo "� Información del sistema:"
echo "   Usuario actual: $(whoami)"
echo "   Directorio actual: $(pwd)"
echo "   Fecha: $(date)"
echo "   Uptime: $(uptime -p)"

echo ""
echo "�📋 URLs importantes:"
echo "   Sitio web: http://localhost o http://TU_IP_DIGITALOCEAN"
echo "   API Python: http://localhost:8000 o http://TU_IP_DIGITALOCEAN:8000"
echo "   Documentación API: http://localhost:8000/docs"
echo "   Panel de administración: http://localhost/login.php"
echo ""
echo "🛠️ Comandos de gestión:"
echo "   Ver logs Python: sudo journalctl -u synktime-python -f"
echo "   Reiniciar Python: sudo systemctl restart synktime-python"
echo "   Ver estado firewall: sudo ufw status"
echo "   Ver logs Apache: sudo tail -f /var/log/apache2/error.log"
EOF

chmod +x check_system.sh

print_success "Script de verificación creado"

# Instrucciones finales
echo ""
echo "🎉 DESPLIEGUE COMPLETADO - DigitalOcean"
echo "======================================"
echo ""
echo "Para iniciar el sistema:"
echo "1. Iniciar el servicio Python:"
echo "   ./start_synktime_service.sh"
echo ""
echo "2. Verificar el estado del sistema:"
echo "   ./check_system.sh"
echo ""
echo "🌐 URLs importantes (reemplaza TU_IP con tu IP de DigitalOcean):"
echo "• Sitio web principal: http://TU_IP"
echo "• API Python: http://TU_IP:8000"
echo "• Documentación API: http://TU_IP:8000/docs"
echo "• Panel de login: http://TU_IP/login.php"
echo ""
echo "🔧 Servicios configurados:"
echo "• Apache (puerto 80) - Sitio web PHP"
echo "• MySQL - Base de datos"
echo "• Servicio Python (puerto 8000) - APIs biométricas"
echo "• Firewall UFW configurado"
echo ""
echo "📊 Scripts disponibles:"
echo "• start_synktime_service.sh - Iniciar servicio Python"
echo "• stop_synktime_service.sh - Detener servicio Python"
echo "• check_system.sh - Verificar estado completo del sistema"
echo ""
echo "🔒 Configuración de seguridad DigitalOcean:"
echo "• Firewall UFW habilitado"
echo "• Puertos 22 (SSH), 80 (HTTP), 443 (HTTPS), 8000 (API) abiertos"
echo "• Servicio Python configurado como servicio systemd"
echo "• Logs configurados con rotación automática"
echo ""
print_success "¡Despliegue completado exitosamente para DigitalOcean!"
