# SynkTime

Sistema Web de control de asistencia laboral que centraliza y automatiza el registro de entradas y salidas, escalable para múltiples sedes y establecimientos.

## Arquitectura

Este proyecto utiliza una arquitectura de monorepo basada en servicios:

```
/services/
  /php-api/        # Backend PHP con API REST
  /frontend/       # Assets y componentes frontend
  /ml-service/     # Servicio Python de ML y biometría
/infra/           # Docker y configuración de infraestructura
/scripts/         # Scripts de utilidad y smoke tests
/ADRs/            # Architecture Decision Records
```

## Servicios

### PHP API Service (`services/php-api/`)

Backend principal con endpoints REST, autenticación y lógica de negocio.

**Tecnologías**: PHP 8.2+, PDO, Composer, Apache

**Estructura**:
- `public/` - Puntos de entrada y endpoints públicos
- `src/` - Código fuente con autoload PSR-4
  - `Controller/` - Controladores HTTP
  - `Service/` - Lógica de negocio
  - `Repository/` - Acceso a datos
  - `Utils/` - Utilidades y helpers
  - `Config/` - Configuración
  - `Auth/` - Autenticación
- `tests/` - Tests PHPUnit
- `uploads/` - Archivos subidos por usuarios

**Setup**:
```bash
cd services/php-api
composer install
php -S localhost:8080 -t public/
```

Ver `services/php-api/README.md` para más detalles.

### Frontend Service (`services/frontend/`)

Assets estáticos, componentes PHP y archivos frontend.

**Tecnologías**: HTML, CSS, JavaScript, PHP components

**Estructura**:
- `assets/` - CSS, JS, imágenes
- `src/` - HTML y componentes PHP

**Setup**:
```bash
cd services/frontend
npm install
npm run lint
```

Ver `services/frontend/README.md` para más detalles.

### ML Service (`services/ml-service/`)

Servicio de Machine Learning para reconocimiento biométrico (facial, huella, RFID).

**Tecnologías**: Python 3.10+, FastAPI, InsightFace

**Estructura**:
- `python_service/` - Implementación del servicio
  - `app.py` - Aplicación FastAPI
  - `services/` - Servicios de reconocimiento
  - `models/` - Modelos de datos
  - `config/` - Configuración
- `model/` - Archivos de modelos ML (no en git)

**Setup**:
```bash
cd services/ml-service
pip install -r requirements.txt
python python_service/app.py
```

Ver `services/ml-service/README.md` para más detalles.

## Inicio Rápido

### Usando Docker Compose (Recomendado)

```bash
# Iniciar todos los servicios
cd infra
docker-compose up -d

# Ver logs
docker-compose logs -f

# Detener servicios
docker-compose down
```

Servicios disponibles:
- **Web (PHP API)**: http://localhost:8080
- **Python ML Service**: http://localhost:8000
- **phpMyAdmin**: http://localhost:8081
- **MariaDB**: localhost:3306

### Desarrollo Local

#### PHP API
```bash
cd services/php-api
composer install
php -S localhost:8080 -t public/
```

#### ML Service
```bash
cd services/ml-service
pip install -r requirements.txt
python python_service/app.py
```

## Configuración

1. Copiar `.env.example` a `.env` en cada servicio
2. Configurar variables de entorno (base de datos, URLs, etc.)
3. Ejecutar migraciones si es necesario

## Estructura de Base de Datos

La base de datos MariaDB contiene las siguientes tablas principales:
- `EMPRESA` - Empresas del sistema
- `SEDE` - Sedes por empresa
- `ESTABLECIMIENTO` - Establecimientos por sede
- `EMPLEADO` - Empleados
- `ASISTENCIA` - Registros de asistencia
- `HORARIO` - Horarios de trabajo
- `HORARIO_EMPLEADO` - Asignación de horarios
- `JUSTIFICACION` - Justificaciones de ausencias
- `USUARIO` - Usuarios del sistema
- `BIOMETRIC_DATA` - Datos biométricos

## Testing

### PHP
```bash
cd services/php-api
composer test
vendor/bin/phpstan analyze
```

### Frontend
```bash
cd services/frontend
npm test
npm run lint
```

### Python
```bash
cd services/ml-service
pytest
```

### Smoke Tests
```bash
./scripts/smoke/check-asistencia.sh
```

## CI/CD

El proyecto utiliza GitHub Actions para CI/CD. Ver `.github/workflows/refactor-ci.yml`.

## Documentación

- **ADRs**: Decisiones arquitectónicas en `ADRs/`
- **API Docs**: Ver `services/php-api/README.md`
- **ML Service**: Ver `services/ml-service/README.md`

## Migraciones Futuras

El proyecto incluye placeholders para futuras migraciones tecnológicas:
- **React**: `services/frontend/react-app-placeholder/`
- **Node.js/NestJS**: `services/api-node-nest/`

Ver los READMEs respectivos para planes de migración.

## Archivo de Scripts de Desarrollo

Scripts de desarrollo, testing y migración históricos se encuentran en `archive/test-debug-scripts/` para referencia.

## Contribuir

1. Crear una rama desde `main`
2. Hacer cambios siguiendo las guías de estilo
3. Ejecutar tests y linters
4. Crear Pull Request

## Licencia

Propietario: Kromez / Mesita27

## Soporte

Para dudas o problemas, contactar a: cm417196@gmail.com
