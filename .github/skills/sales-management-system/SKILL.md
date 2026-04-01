---
name: sales-management-system
description: 'Use when creating or migrating sales management features, API endpoints, CRUD flows, Vue 3 pages, composables, pagination, search/filter, validation, optimistic UI, and legacy-PHP parity tasks in this workspace.'
argument-hint: 'Describe the feature or workflow to implement in the sales management system'
user-invocable: true
disable-model-invocation: false
---

# Sales Management System Skill

## When to Use

Use this skill when working on:

- Slim 4 **API endpoint creation**
- Standard **CRUD flows**
- Request **validation** and JSON error handling
- Vue 3 **Composition API** pages and components
- API integration with frontend services or composables
- Pagination, filtering, searching, and toasts
- Optimistic UI updates with rollback on failure
- Any feature migrated from the **legacy PHP MVC** system

## Critical Legacy Rule

Before implementing anything:

1. Read the old MVC code first.
2. Preserve existing logic exactly.
3. Do not simplify unknown calculations or conditions.
4. Keep legacy edge-case behavior unless explicitly asked to change it.

> The old MVC system is the source of truth for behavior.

## Backend Workflow

### Create API Endpoint

Follow this order:

1. Define the route.
2. Create or update the Controller.
3. Validate request input in the Controller.
4. Move business logic into the Service.
5. Keep database access in the Repository.
6. Return JSON in the standard response format.

### CRUD Pattern

Use these conventions:

- `index` → list
- `store` → create
- `show` → detail
- `update` → update
- `delete` → remove

### Validation Rules

- Validate in the **Controller**.
- Return structured error JSON.
- Do not move HTTP validation concerns into Services.

### API Response Shape

```json
{
  "success": true,
  "data": {},
  "message": ""
}
```

## Frontend Workflow

### API Call Pattern

- Use `/services/api.js` conventions when available.
- Use `axios` or `fetch` consistently with the existing frontend style.
- Keep API calls isolated from presentation components when possible.

### Composables Pattern

Prefer reusable composables such as:

- `useFetch`
- `useForm`
- `usePagination`
- `useToast`

### Component Pattern

Prefer small, reusable UI pieces:

- `Button`
- `Input`
- `Card`
- `List`
- Bottom Navigation
- `Sheet` / modal

### Form Handling

1. Use controlled inputs.
2. Validate before submit.
3. Show inline errors clearly.
4. Keep the UI responsive while submitting.

### Optimistic UI

- Update the UI immediately only when it matches existing business behavior.
- Roll back the state if the API call fails.
- Show a toast or visible error message on failure.

## Shared Patterns

### Pagination Response

```json
{
  "data": [],
  "meta": {
    "page": 1,
    "total": 100
  }
}
```

### Search and Filter

- Debounce search input.
- Query the API with filter parameters.
- Keep URL/query behavior consistent with the current app pattern.

### Error Handling

- Always catch API errors.
- Show user-facing feedback such as a toast.
- Do not fail silently.

### Auth

If authentication is part of the task:

- Use token-based auth when the app already expects it.
- Store tokens in `localStorage` only if that matches the existing implementation.

## Completion Checklist

Before finishing, confirm:

1. Legacy behavior was checked first.
2. Backend flow remains `Route -> Controller -> Service -> Repository`.
3. Controller handles validation and response formatting.
4. Service contains business logic only.
5. Frontend uses Vue 3 Composition API patterns.
6. Errors and edge cases are handled.
7. The result matches the existing workflow, not a redesigned one.
