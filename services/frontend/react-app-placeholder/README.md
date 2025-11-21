# React Migration Placeholder

## Purpose

This directory is reserved for future migration of the frontend to React.

## Current State

The current frontend uses:
- Server-side PHP templates
- Vanilla JavaScript
- Bootstrap CSS framework
- jQuery for DOM manipulation

## Migration Goals

1. **Modern UI Framework**: Leverage React for component reusability
2. **Better State Management**: Use React Context or Redux
3. **Improved Developer Experience**: Hot reload, TypeScript support
4. **Better Testing**: Component testing with React Testing Library
5. **Performance**: Code splitting and lazy loading

## Technology Stack (Proposed)

- **Framework**: React 18+
- **Language**: TypeScript
- **Build Tool**: Vite
- **State**: React Context + Hooks (or Redux Toolkit)
- **Routing**: React Router v6
- **Styling**: Tailwind CSS or Material-UI
- **API Client**: Axios + TanStack Query
- **Testing**: Vitest + React Testing Library

## Key Endpoints to Consume

See ADR 0001 for complete API documentation. Key endpoints:
- Authentication: `/login.php`, `/logout.php`
- Attendance: `/api/attendance/*`
- Employees: `/api/employees/*`
- Schedules: `/api/horario/*`
- Reports: `/api/reports/*`

## Migration Timeline

Estimated 3-4 months for full migration. See full README in this directory for detailed phases.

## Decision Criteria

Proceed when:
- Team has React expertise
- Business approves investment
- Clear performance benefits identified
- Resources available

This is a placeholder for future work.
