
# Script para cambiar entre entornos de desarrollo
# Uso: ./switch_env.sh [local|production|staging]

ENVIRONMENT=${1:-local}

if [ "$ENVIRONMENT" != "local" ] && [ "$ENVIRONMENT" != "production" ] && [ "$ENVIRONMENT" != "staging" ]; then
    echo "Uso: $0 [local|production|staging]"
    echo "Ejemplo: $0 local"
    exit 1
fi

# Crear o actualizar .env con el entorno seleccionado
cat > .env << EOF2
# Configuración de entorno
ENVIRONMENT=$ENVIRONMENT

# URLs del servicio Python
EOF2

if [ "$ENVIRONMENT" = "local" ]; then
    cat >> .env << EOF2
PYTHON_SERVICE_PUBLIC_URL=http://localhost:8000
PYTHON_SERVICE_FORCE_PUBLIC_IP=http://localhost:8000

# Base de datos - localhost para desarrollo
DB_HOST=localhost
DB_PORT=3306
DB_NAME=synktime
DB_USER=root
DB_PASSWORD=Miau$210718

# Otras configuraciones
APP_ENV=local
EOF2
elif [ "$ENVIRONMENT" = "production" ]; then
    cat >> .env << EOF2
PYTHON_SERVICE_PUBLIC_URL=https://kromez.dev/python-service
PYTHON_SERVICE_FORCE_PUBLIC_IP=http://68.183.56.10:8000

# Base de datos
DB_HOST=localhost
DB_PORT=3306
DB_NAME=synktime
DB_USER=root
DB_PASSWORD=Miau$210718

# Otras configuraciones
APP_ENV=production
EOF2
elif [ "$ENVIRONMENT" = "staging" ]; then
    cat >> .env << EOF2
PYTHON_SERVICE_PUBLIC_URL=https://staging.kromez.dev/python-service
PYTHON_SERVICE_FORCE_PUBLIC_IP=http://staging-server:8000

# Base de datos
DB_HOST=localhost
DB_PORT=3306
DB_NAME=synktime
DB_USER=root
DB_PASSWORD=Miau$210718

# Otras configuraciones
APP_ENV=staging
EOF2
fi

echo "Entorno cambiado a: $ENVIRONMENT"
echo "Archivo .env actualizado."
echo ""
echo "Para aplicar los cambios:"
echo "- Si usas Docker: docker-compose down && docker-compose up -d"
echo "- Si es desarrollo local: reinicia tu servidor web"
echo ""
echo "Configuración actual:"
cat .env
