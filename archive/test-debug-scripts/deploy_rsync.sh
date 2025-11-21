#!/bin/bash

# ========================================
# DEPLOYMENT CON RSYNC - SYNKTIME
# ========================================

# Configuración del servidor
SERVER_IP="tu_servidor_ip"
SERVER_USER="tu_usuario"
SERVER_PATH="/var/www/html/synktime"
LOCAL_PATH="/mnt/c/Users/datam/Downloads/Synktime"

echo "========================================"
echo " DEPLOYMENT A SERVIDOR LAMP - SYNKTIME"
echo "========================================"
echo
echo "Configuración actual:"
echo "- Servidor: $SERVER_IP"
echo "- Usuario: $SERVER_USER"
echo "- Ruta remota: $SERVER_PATH"
echo "- Ruta local: $LOCAL_PATH"
echo

# Verificar rsync
if ! command -v rsync &> /dev/null; then
    echo "ERROR: rsync no está instalado"
    echo "En Ubuntu/Debian: sudo apt install rsync"
    echo "En Windows: usar WSL o instalar cwRsync"
    exit 1
fi

# Confirmar deployment
read -p "¿Deseas continuar con el deployment? (s/N): " confirm
if [[ ! $confirm =~ ^[Ss]$ ]]; then
    echo "Deployment cancelado."
    exit 0
fi

echo
echo "=== INICIANDO DEPLOYMENT ==="

# Crear backup en servidor
echo "1. Creando backup en servidor..."
ssh $SERVER_USER@$SERVER_IP "cd /var/www/html && tar -czf synktime_backup_\$(date +%Y%m%d_%H%M%S).tar.gz synktime/ 2>/dev/null || echo 'Backup omitido - directorio no existe'"

# Crear directorio si no existe
echo "2. Preparando directorio en servidor..."
ssh $SERVER_USER@$SERVER_IP "mkdir -p $SERVER_PATH"

# Sincronizar archivos
echo "3. Sincronizando archivos..."
rsync -avz --progress \
    --exclude='*.log' \
    --exclude='*.tmp' \
    --exclude='node_modules/' \
    --exclude='.git/' \
    --exclude='*.bat' \
    --exclude='*.exe' \
    --exclude='test_*' \
    --exclude='debug_*' \
    --delete \
    $LOCAL_PATH/ $SERVER_USER@$SERVER_IP:$SERVER_PATH/

if [ $? -eq 0 ]; then
    echo
    echo "=== DEPLOYMENT COMPLETADO ==="
    echo "Archivos sincronizados exitosamente a: $SERVER_IP:$SERVER_PATH"
    
    # Configurar permisos
    echo "4. Configurando permisos..."
    ssh $SERVER_USER@$SERVER_IP "chmod -R 755 $SERVER_PATH && chown -R www-data:www-data $SERVER_PATH"
    
    # Verificar estado
    echo "5. Verificando deployment..."
    ssh $SERVER_USER@$SERVER_IP "ls -la $SERVER_PATH/ | head -10"
    
    echo
    echo "✅ Deployment finalizado exitosamente"
else
    echo
    echo "❌ Error durante el deployment"
    exit 1
fi