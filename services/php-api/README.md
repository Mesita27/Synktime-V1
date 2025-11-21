# Synktime PHP API Service

This service contains the PHP backend API for the Synktime attendance system.

## Structure

```
/public/          # Entry points and public-facing endpoints
  /api/          # API endpoints
  index.php     # Main entry point
  
/src/            # PSR-4 autoloaded source code
  /Controller/  # HTTP request handlers
  /Service/     # Business logic
  /Repository/  # Data access layer
  /Utils/       # Utility functions and helpers
  /Config/      # Configuration classes
  /Auth/        # Authentication logic
  
/tests/          # PHPUnit tests
/uploads/        # User uploads (photos, documents)
```

## Setup

### Install Dependencies

```bash
composer install
```

### Development Server

```bash
php -S localhost:8080 -t public/
```

### Run Tests

```bash
composer test
```

### Run Linter

```bash
composer lint
```

## Environment Variables

See `.env.example` in the root directory for required environment variables.

## API Endpoints

- `GET /api/get-attendance-details.php` - Get attendance details by type
- `POST /api/attendance/register-unified.php` - Register attendance
- `GET /api/horario/list.php` - List schedules
- And many more...

## Architecture

This service follows a layered architecture:

1. **Controllers/Entry Points** (`public/`) - Handle HTTP requests, validation
2. **Services** (`src/Service/`) - Business logic, orchestration
3. **Repositories** (`src/Repository/`) - Database access
4. **Utils** (`src/Utils/`) - Shared utilities and helpers

## Migration Notes

This service was refactored from the monolithic `Synktime/` directory structure to follow PSR-4 standards and modern PHP practices.
