#!/bin/bash

# Script para configurar SSL con Let's Encrypt en DigitalOcean
# Ejecutar después del despliegue principal

set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_step() {
    echo -e "${BLUE}[PASO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[ÉXITO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[ADVERTENCIA]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Verificar si estamos en Ubuntu
if ! command -v lsb_release &> /dev/null || [[ $(lsb_release -si) != "Ubuntu" ]]; then
    print_error "Este script está diseñado para Ubuntu"
    exit 1
fi

print_step "Configurando SSL con Let's Encrypt para DigitalOcean"

# Instalar Certbot y plugin de Apache
print_step "Instalando Certbot y plugin de Apache..."
sudo apt update
sudo apt install -y certbot python3-certbot-apache

# Verificar que Apache esté corriendo
if ! systemctl is-active --quiet apache2; then
    print_error "Apache no está ejecutándose. Inicia Apache primero."
    exit 1
fi

# Solicitar dominio al usuario
echo ""
echo "Configuración SSL - Let's Encrypt"
echo "=================================="
read -p "Ingresa tu dominio (ej: synktime.com): " DOMAIN

if [[ -z "$DOMAIN" ]]; then
    print_error "Dominio requerido"
    exit 1
fi

# Verificar que el dominio apunte a esta IP
SERVER_IP=$(curl -s ifconfig.me)
DOMAIN_IP=$(dig +short $DOMAIN | head -1)

if [[ "$DOMAIN_IP" != "$SERVER_IP" ]]; then
    print_warning "El dominio $DOMAIN no parece apuntar a esta IP ($SERVER_IP)"
    print_warning "Asegúrate de que el registro A de tu dominio apunte a: $SERVER_IP"
    echo ""
    read -p "¿Continuar de todos modos? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_step "Cancelado por el usuario"
        exit 0
    fi
fi

# Configurar SSL con Certbot
print_step "Obteniendo certificado SSL para $DOMAIN..."
sudo certbot --apache -d $DOMAIN --non-interactive --agree-tos --email admin@$DOMAIN

# Verificar que el certificado se instaló correctamente
if sudo certbot certificates | grep -q "$DOMAIN"; then
    print_success "Certificado SSL instalado correctamente"
else
    print_error "Error al instalar el certificado SSL"
    exit 1
fi

# Configurar renovación automática
print_step "Configurando renovación automática de certificados..."
sudo crontab -l | { cat; echo "0 12 * * * /usr/bin/certbot renew --quiet"; } | sudo crontab -

# Reiniciar Apache para aplicar cambios
print_step "Reiniciando Apache..."
sudo systemctl restart apache2

# Verificar configuración SSL
print_step "Verificando configuración SSL..."
if curl -s -I https://$DOMAIN | grep -q "HTTP/2 200"; then
    print_success "SSL configurado correctamente en https://$DOMAIN"
else
    print_warning "Verifica manualmente la configuración SSL"
fi

# Configurar redirección HTTP a HTTPS en Apache
print_step "Configurando redirección HTTP a HTTPS..."
sudo a2enmod rewrite
sudo systemctl restart apache2

# Crear archivo de configuración para redirección HTTPS
cat > /tmp/ssl_redirect.conf << EOF
<VirtualHost *:80>
    ServerName $DOMAIN
    Redirect permanent / https://$DOMAIN/
</VirtualHost>
EOF

sudo mv /tmp/ssl_redirect.conf /etc/apache2/sites-available/ssl_redirect.conf
sudo a2ensite ssl_redirect.conf
sudo systemctl reload apache2

print_success "Redirección HTTP a HTTPS configurada"

echo ""
echo "🎉 CONFIGURACIÓN SSL COMPLETADA"
echo "==============================="
echo ""
echo "Tu sitio está ahora disponible en:"
echo "• HTTPS: https://$DOMAIN"
echo "• HTTP redirige automáticamente a HTTPS"
echo ""
echo "🔒 Certificado SSL:"
echo "• Emitido por Let's Encrypt"
echo "• Renovación automática configurada"
echo "• Válido por 90 días"
echo ""
echo "📋 Próximos pasos:"
echo "1. Actualiza tus marcadores con https://$DOMAIN"
echo "2. Configura tu dominio en DigitalOcean DNS si no lo has hecho"
echo "3. Verifica que todas las URLs funcionen correctamente"
echo ""
print_success "¡SSL configurado exitosamente!"
