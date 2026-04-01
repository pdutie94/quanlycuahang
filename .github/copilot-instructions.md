# Copilot Instructions - Sales Management (API + Vue3)

## Project Overview

This project is a **store management system rewritten from an existing PHP MVC system**.

* Backend: PHP (Slim 4 - REST API)
* Frontend: Vue 3 (Composition API)
* UI: TailwindCSS (flat, minimal)
* Architecture: API-first (SPA)
* Deployment: Same domain

---

## Core Principles

* PHP = API only (no HTML)
* Vue = render UI
* Strict separation backend/frontend
* Clean architecture

---

## ⚠️ Legacy System Alignment (CRITICAL)

This is NOT a new system.

### Rules

* MUST preserve all existing business logic
* MUST keep same features and workflows
* MUST NOT invent new features
* MUST NOT change calculations

---

### Migration Principles

* Old MVC = source of truth
* New system = refactor, NOT redesign

---

### Implementation Flow

1. Analyze old PHP MVC logic
2. Move logic into Service layer
3. Expose via API
4. Render with Vue

---

### Data Rules

* Keep DB structure if possible
* Do not rename fields unnecessarily
* Maintain data compatibility

---

### UI Rules

* UI can be modernized
* BUT workflows must stay the same

---

## Backend Rules (PHP - Slim 4)

### Structure

Route → Controller → Service → Repository

---

### Controller

* Validate request
* Call service
* Return JSON

---

### Service

* Business logic only
* No HTTP handling

---

### Repository

* Database queries only

---

## API Response Format

```json
{
  "success": true,
  "data": {},
  "message": ""
}
```

---

## Frontend Rules (Vue 3)

* Use Composition API only
* Use `setup()`
* No Options API

---

## Folder Structure

/project-root
│
├── /backend
│   ├── /app
│   │   ├── /Modules
│   │   │   ├── /Order
│   │   │   │   ├── OrderController.php
│   │   │   │   ├── OrderService.php
│   │   │   │   ├── OrderRepository.php
│   │   │   │   ├── OrderValidator.php
│   │   │   │   ├── OrderResource.php
│   │   │   │
│   │   │   ├── /Product
│   │   │   ├── /Customer
│   │   │   ├── /Payment
│   │   │
│   │   ├── /Shared
│   │   │   ├── /Database
│   │   │   │   └── Connection.php
│   │   │   │
│   │   │   ├── /Http
│   │   │   │   ├── Request.php
│   │   │   │   └── Response.php
│   │   │   │
│   │   │   ├── /Middleware
│   │   │   │   ├── AuthMiddleware.php
│   │   │   │   └── JsonMiddleware.php
│   │   │   │
│   │   │   ├── /Exceptions
│   │   │   │   ├── AppException.php
│   │   │   │   └── Handler.php
│   │   │   │
│   │   │   ├── /Helpers
│   │   │   │   └── helpers.php
│   │   │   │
│   │   │   ├── /Base
│   │   │   │   ├── BaseController.php
│   │   │   │   ├── BaseService.php
│   │   │   │   └── BaseRepository.php
│   │   │   │
│   │   │   └── /Response
│   │   │       └── ApiResponse.php
│   │
│   ├── /routes
│   │   └── api.php
│   │
│   ├── /config
│   │   ├── app.php
│   │   └── database.php
│   │
│   ├── /bootstrap
│   │   └── app.php
│   │
│   └── /storage
│       └── logs/
│
├── /frontend
│   ├── /src
│   │   ├── /modules
│   │   │   ├── /order
│   │   │   │   ├── /pages
│   │   │   │   │   ├── OrderList.vue
│   │   │   │   │   └── OrderDetail.vue
│   │   │   │   │
│   │   │   │   ├── /components
│   │   │   │   │   ├── OrderCard.vue
│   │   │   │   │   └── OrderForm.vue
│   │   │   │   │
│   │   │   │   ├── /services
│   │   │   │   │   └── order.api.js
│   │   │   │   │
│   │   │   │   └── /composables
│   │   │   │       └── useOrder.js
│   │   │   │
│   │   │   ├── /product
│   │   │   ├── /customer
│   │   │
│   │   ├── /shared
│   │   │   ├── /components
│   │   │   │   ├── BaseButton.vue
│   │   │   │   ├── BaseInput.vue
│   │   │   │   └── BaseCard.vue
│   │   │   │
│   │   │   ├── /composables
│   │   │   │   ├── useFetch.js
│   │   │   │   ├── useForm.js
│   │   │   │   └── useToast.js
│   │   │   │
│   │   │   ├── /services
│   │   │   │   └── api.js
│   │   │   │
│   │   │   └── /utils
│   │   │       └── format.js
│   │   │
│   │   ├── /layouts
│   │   │   └── MainLayout.vue
│   │   │
│   │   ├── /router
│   │   │   └── index.js
│   │   │
│   │   ├── App.vue
│   │   └── main.js
│   │
│   └── index.html
│
├── /public
│   ├── index.php        ← entry backend (Slim)
│   ├── index.html       ← build Vue
│   └── /assets          ← js/css build từ Vue
│
├── db-structure.sql
├── composer.json
├── package.json
├── .env

---

## API Rules

* Prefix: `/api`
* RESTful
* Use proper HTTP methods

---

## State Management

* Use composables
* Avoid global state unless necessary

---

## Naming

* camelCase: JS
* snake_case: DB
* PascalCase: Components

---

## Performance

* Lazy load pages
* Prefer computed over methods
* Avoid unnecessary watchers

---

## UX Rules

* Fast interaction
* No blocking UI
* Optimistic UI when possible
