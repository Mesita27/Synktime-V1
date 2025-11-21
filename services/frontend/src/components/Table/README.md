# Reusable Table Component

## Purpose

This directory is a placeholder for a future reusable table component that will be used across the Synktime application.

## Current State

Currently, table rendering is scattered across multiple PHP files with duplicated logic. This component will consolidate:

- Attendance tables
- Employee lists
- Schedule displays
- Report tables
- Justification lists

## Migration Plan

### Phase 1: Analysis
- [ ] Identify all table implementations
- [ ] Document common patterns
- [ ] Define required features (sorting, filtering, pagination)

### Phase 2: Design
- [ ] Create component API design
- [ ] Define props and callbacks
- [ ] Design responsive behavior

### Phase 3: Implementation
- [ ] Implement React table component
- [ ] Add sorting capability
- [ ] Add filtering capability
- [ ] Add pagination
- [ ] Add column customization
- [ ] Add export functionality

### Phase 4: Migration
- [ ] Replace attendance table
- [ ] Replace employee table
- [ ] Replace schedule table
- [ ] Replace report tables

## Technology Options

Consider these options for implementation:

1. **Custom React Component**
   - Full control
   - Lightweight
   - Learning opportunity

2. **TanStack Table (React Table)**
   - Feature-rich
   - Well-maintained
   - Large community

3. **Material-UI DataGrid**
   - Complete solution
   - Professional appearance
   - MIT license (free tier)

## API Contract Example

```jsx
<DataTable
  columns={[
    { field: 'id', header: 'ID', sortable: true },
    { field: 'name', header: 'Name', sortable: true, filterable: true },
    { field: 'date', header: 'Date', sortable: true, type: 'date' }
  ]}
  data={attendanceRecords}
  onRowClick={handleRowClick}
  pageSize={20}
  exportable={true}
/>
```

## Dependencies to Consider

- `react-table` or `@tanstack/react-table`
- `date-fns` for date formatting
- `lodash` for data manipulation

## Testing Requirements

- Unit tests for table logic
- Integration tests for user interactions
- Visual regression tests
- Accessibility tests (WCAG 2.1 AA)

## Notes

This is a placeholder directory. Implementation will occur in a future refactoring phase after the monorepo restructure is complete.
