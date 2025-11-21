# ADR 0001: Restructure to Service-Based Monorepo

## Status

Proposed

## Context

The Synktime codebase currently resides in a single `Synktime/` directory with:
- Mixed concerns (PHP backend, frontend assets, Python ML service)
- No clear separation between application layers
- No PSR-4 autoloading for PHP
- Difficult to scale and maintain
- Hard to onboard new developers
- Testing infrastructure is minimal
- No clear deployment boundaries

The codebase has grown organically and now needs a more structured approach to:
1. Support multiple developers working simultaneously
2. Enable independent deployment of services
3. Facilitate future technology migrations (React, Node.js, NestJS)
4. Improve code quality and maintainability
5. Establish clear architectural boundaries

## Decision

We will restructure the Synktime codebase into a service-based monorepo with the following structure:

```
/services/
  /php-api/        # PHP backend API (existing functionality)
  /frontend/       # Frontend assets and components
  /ml-service/     # Python ML/biometric service
/infra/           # Docker, deployment configs
/scripts/         # CI/CD and utility scripts
/ADRs/            # Architecture Decision Records
```

### Key Principles

1. **No Functional Changes**: This restructure preserves all existing functionality
2. **Atomic Commits**: Changes organized by service area
3. **Backward Compatibility**: API contracts remain unchanged
4. **Golden Tests**: Validate that endpoints return identical responses
5. **PSR-4 for PHP**: Introduce modern PHP standards gradually

### Service Breakdown

#### PHP API Service (`services/php-api/`)
- **Purpose**: Backend API, business logic, database access
- **Technology**: PHP 7.4+, PDO, Composer
- **Structure**:
  - `public/` - Entry points and endpoints
  - `src/` - PSR-4 autoloaded classes (Controller, Service, Repository, Utils, Config, Auth)
  - `tests/` - PHPUnit tests
  - `uploads/` - User-uploaded files
- **Deployment**: Apache/Nginx serving `public/` as document root

#### Frontend Service (`services/frontend/`)
- **Purpose**: Static assets, UI components
- **Technology**: HTML, CSS, JavaScript (current), Future: React
- **Structure**:
  - `assets/` - CSS, JS, images
  - `src/components/` - Reusable components
- **Deployment**: Served by PHP API or CDN

#### ML Service (`services/ml-service/`)
- **Purpose**: Biometric recognition (facial, fingerprint, RFID)
- **Technology**: Python 3.10+, FastAPI, InsightFace
- **Structure**:
  - `app/` - Main application code
  - `python_service/` - Service implementation
  - `model/` - ML model files (not in git)
- **Deployment**: Internal Docker network, not publicly exposed
- **Security**: Network isolation, no direct public access

### Migration Strategy

#### Phase 1: Structure Creation (This PR)
1. Create service directories
2. Add configuration files (composer.json, package.json, Dockerfiles)
3. Move files to new locations
4. Update import paths
5. Implement one example refactor (AsistenciaService)

#### Phase 2: Testing Infrastructure
1. Add golden tests for critical endpoints
2. Setup CI/CD for all services
3. Add linting (PHPStan, ESLint)
4. Smoke tests for deployment validation

#### Phase 3: Gradual Refactoring
1. Extract services and repositories (DI-friendly)
2. Add unit tests
3. Improve error handling
4. Add logging and monitoring

#### Future Phases (Separate ADRs)
- Migrate to React (frontend)
- Migrate to Node.js/NestJS (API alternative)
- Add GraphQL layer
- Implement WebSocket for real-time features

## Consequences

### Positive

1. **Clear Boundaries**: Each service has a defined purpose
2. **Independent Scaling**: Services can scale separately
3. **Better Testing**: Each service can be tested in isolation
4. **Easier Onboarding**: New developers understand structure faster
5. **Technology Flexibility**: Can migrate services independently
6. **Deployment Flexibility**: Can deploy services separately
7. **Code Quality**: Enforced standards per service
8. **Security**: ML service isolated in internal network

### Negative

1. **Increased Complexity**: More directories to navigate
2. **Build Process**: Requires coordination across services
3. **Shared Code**: Need strategy for shared utilities
4. **Learning Curve**: Team needs to adapt to new structure
5. **Migration Effort**: Significant upfront investment

### Neutral

1. **File Paths**: All existing paths need updating
2. **Documentation**: Needs comprehensive update
3. **CI/CD**: Requires workflow adjustments

## Validation Criteria

This restructure is successful if:

1. ✅ All CI/CD tests pass
2. ✅ Endpoint responses match golden tests byte-for-byte
3. ✅ Docker Compose brings up all services
4. ✅ `composer install` works in php-api service
5. ✅ No secrets committed to repository
6. ✅ No functional regressions reported
7. ✅ Development environment works locally
8. ✅ PHPStan and ESLint run successfully

## Future Technology Migration Criteria

### When to migrate to React?
- Team has React expertise
- Need for complex UI interactions
- Performance issues with current approach
- Requirement for mobile app (React Native)

### When to migrate to Node.js/NestJS?
- Need for WebSocket/real-time features
- Team prefers TypeScript
- Requirement for GraphQL
- Better async performance needed

### When to use separate API Gateway?
- Multiple frontend applications
- Need for rate limiting
- Complex authentication flows
- Microservices architecture

## References

- [PSR-4 Autoloading Standard](https://www.php-fig.org/psr/psr-4/)
- [Monorepo Best Practices](https://monorepo.tools/)
- [Service-Oriented Architecture](https://en.wikipedia.org/wiki/Service-oriented_architecture)
- [The Twelve-Factor App](https://12factor.net/)

## Notes

- This ADR covers only the initial restructure
- Functional refactoring will be addressed in future ADRs
- API contracts are considered stable and will not change without explicit design
- Database schema changes are out of scope for this restructure

## Author

Synktime Development Team

## Date

2025-11-21
