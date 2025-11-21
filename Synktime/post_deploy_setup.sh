#!/bin/bash

# ========================================
# POST-DEPLOYMENT SETUP - SYNKTIME
# Configuración automática después del deployment
# ========================================

SERVER_USER="tu_usuario"
SERVER_IP="tu_servidor_ip"
WEB_ROOT="/var/www/html/synktime"

echo "========================================"
echo " POST-DEPLOYMENT SETUP - SYNKTIME"
echo "========================================"

# Conectar y ejecutar configuraciones
ssh $SERVER_USER@$SERVER_IP << 'ENDSSH'

echo "1. Configurando permisos de archivos..."
cd /var/www/html/synktime

# Permisos básicos
find . -type f -name "*.php" -exec chmod 644 {} \;
find . -type f -name "*.js" -exec chmod 644 {} \;
find . -type f -name "*.css" -exec chmod 644 {} \;
find . -type f -name "*.html" -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# Permisos especiales para directorios de escritura
mkdir -p uploads logs temp
chmod 777 uploads logs temp

echo "2. Configurando Apache (si es necesario)..."
# Crear .htaccess si no existe
if [ ! -f .htaccess ]; then
cat > .htaccess << 'EOF'
RewriteEngine On

# Redireccionar a HTTPS (opcional)
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Proteger archivos sensibles
<Files "*.log">
    Order allow,deny
    Deny from all
</Files>

<Files "config*.php">
    Order allow,deny
    Deny from all
</Files>

# Configurar tipos MIME
AddType application/json .json

# Compresión GZIP
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>

# Cache control
<IfModule mod_expires.c>
    ExpiresActive on
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
</IfModule>
EOF
fi

echo "3. Verificando configuración de PHP..."
# Verificar extensiones PHP requeridas
php -m | grep -E "(pdo|mysqli|gd|json|curl)" || echo "⚠️  Verificar extensiones PHP"

echo "4. Configurando base de datos (recordatorio)..."
echo "📋 RECORDATORIO:"
echo "   - Crear base de datos si no existe"
echo "   - Importar estructura SQL"
echo "   - Verificar usuario y permisos de DB"
echo "   - Actualizar config/database.php con credenciales"

echo "5. Verificando estructura de directorios..."
ls -la | head -10

echo "6. Configurando propiedad de archivos..."
chown -R www-data:www-data .

echo
echo "✅ Post-deployment setup completado"
echo
echo "🔧 PASOS MANUALES PENDIENTES:"
echo "   1. Configurar credenciales de base de datos"
echo "   2. Verificar que Apache esté configurado"
echo "   3. Probar la aplicación en el navegador"
echo "   4. Revisar logs de Apache/PHP por errores"

ENDSSH

echo
echo "=========================================="
echo "URLs para verificar:"
echo "- http://$SERVER_IP/synktime/"
echo "- http://$SERVER_IP/synktime/index.php"
echo "=========================================="