# Refactorización: Plan Inicial (Fase 0)

Este documento contiene el plan inicial y los pasos que hemos aplicado para la Fase 0 (Preparación). Describe cómo ejecutar linters, tests y dónde estarán ubicados los archivos nuevos.

## Objetivo
Proveer infraestructura de calidad (linters, tests, CI) y prepararnos para refactors incrementales.

## Archivos creados
- `package.json`: scripts `lint`, `format`, `test`.
- `.eslintrc.json`: reglas base para ESLint.
- `.prettierrc`: configuración de Prettier.
- `jest.config.cjs`: configuración básica para Jest (jsdom).
- `tests/js/timeline.test.js`: pruebas básicas para `timeline.js`.
- `phpstan.neon`: configuración básica para PHPStan.
- `phpunit.xml`: configuración básica para PHPUnit.
- `.github/workflows/refactor-ci.yml`: pipeline CI para linter y tests.

## Cómo ejecutar locally
1. Instala dependencias de Node (si deseas) con:
```bash
npm ci
```
2. Ejecuta linter:
```bash
npm run lint
```
3. Ejecuta tests:
```bash
npm test
```
4. Para PHP, instala Composer y corra:
```bash
composer install
vendor/bin/phpstan analyze
vendor/bin/phpunit
```

## Notas y siguientes pasos
- Si `node` no está instalado, instalarlo con `sudo apt install nodejs npm` o usar `nvm`.
- Si `php` o `composer` no están instalados, instálalos con apt o instrucción adecuada para WSL.
- Próxima fase: añadir linters en GitHub Actions y crear pruebas para flujo crítico (Login, CRUD de turnos).
