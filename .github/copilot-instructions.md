# Copilot Instructions - Sales Management (API + Vue 3)

## Project Overview

This project is a **store management system rewritten from an existing PHP MVC system**.

- **Backend:** PHP (`Slim 4`) REST API
- **Frontend:** Vue 3 with the Composition API
- **UI:** TailwindCSS with a flat, minimal style
- **Architecture:** API-first SPA on the same domain

## Core Principles

- PHP is **API only** and should not render HTML in the new architecture.
- Vue is responsible for rendering the UI.
- Keep a strict separation between backend and frontend.
- Favor clean architecture and clear service boundaries.

## Legacy System Alignment (Critical)

This is **not** a greenfield product.

- Preserve all existing business logic.
- Keep the same features, workflows, and calculations.
- Do **not** invent new features unless explicitly requested.
- Treat the old MVC implementation as the **source of truth**.
- Refactor and modernize the system, but do **not** redesign business behavior.

### Migration workflow

When implementing or migrating functionality:

1. Analyze the old PHP MVC logic first.
2. Move business rules into the Service layer.
3. Expose that behavior through REST API endpoints.
4. Render the resulting workflow in Vue.

## Data and Compatibility Rules

- Keep the existing database structure when possible.
- Do not rename fields unnecessarily.
- Maintain data compatibility with the legacy system.
- UI may be modernized, but the workflow must remain the same.

## Backend Rules (PHP / Slim 4)

### Architecture

Use this flow:

`Route -> Controller -> Service -> Repository`

### Controller responsibilities

- Validate the request.
- Call the appropriate service.
- Return JSON only.

### Service responsibilities

- Keep business logic here.
- Do not handle HTTP concerns in services.

### Repository responsibilities

- Keep database queries here.
- Do not move business logic into repositories.

### API response format

Use a consistent JSON shape:

```json
{
  "success": true,
  "data": {},
  "message": ""
}
```

### API conventions

- Prefix API routes with `/api`.
- Follow RESTful design.
- Use the proper HTTP methods for each action.

## Frontend Rules (Vue 3)

- Use the Composition API only.
- Prefer `setup()` patterns and composables.
- Do not use the Options API for new code.
- Avoid global state unless it is clearly necessary.

## Naming Conventions

- `camelCase` for JavaScript variables and functions
- `PascalCase` for Vue components
- `snake_case` for database fields

## Performance and UX

- Lazy-load pages when appropriate.
- Prefer `computed` values over unnecessary methods or watchers.
- Avoid unnecessary watchers.
- Keep interactions fast and non-blocking.
- Use optimistic UI only when it preserves existing business behavior.

## Copilot Working Expectations

When suggesting changes:

- Inspect the legacy PHP MVC implementation before changing behavior.
- Preserve feature parity with the existing system.
- Prefer extraction and refactoring over rewriting logic from scratch.
- Keep backend/frontend separation strict in all new architecture proposals.
