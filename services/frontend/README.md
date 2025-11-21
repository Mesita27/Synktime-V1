# Synktime Frontend Service

This service contains frontend assets, components, and static files for the Synktime attendance system.

## Structure

```
/assets/         # Static assets
  /css/         # Stylesheets
  /js/          # JavaScript files
  /img/         # Images
  
/src/           # Source components
  /components/  # Reusable PHP components (modals, tables, etc.)
    /Table/     # Placeholder for future React table component
```

## Setup

### Install Dependencies

```bash
npm install
```

### Development

The frontend assets are served by the PHP API service. For development:

```bash
npm run lint     # Run ESLint
npm run format   # Format code with Prettier
npm run test     # Run Jest tests
```

## Components

### Current PHP Components
- Modals (attendance, biometric, justifications, reports)
- Sidebar navigation
- Header
- Filters and forms

### Future Migration Plan

See `/src/components/Table/README.md` for planned migration to React components.

## Assets Structure

- **CSS**: Modular stylesheets for different features
- **JavaScript**: Client-side logic for biometric verification, forms, etc.
- **Images**: Logos, icons, placeholder images

## Migration Notes

This service was extracted from the monolithic structure to separate frontend concerns from backend logic. Future refactoring may include:

1. Migrating PHP components to React
2. Implementing a proper bundling system (Webpack/Vite)
3. Creating a component library
4. Adding Storybook for component documentation
