---
trigger: always_on
---

# TypeScript Migration Safe Mode

## Project Context

- Existing project (NOT new)
- Backend: PHP Slim4
- Frontend: Vue3
- Goal: migrate JavaScript to TypeScript gradually

## Core Principle

- DO NOT rewrite code
- DO NOT refactor large structures
- ONLY make incremental, safe changes

## TypeScript Rules

- Add types for function parameters and return values
- Avoid `any` unless unavoidable
- Prefer simple types

## Migration Rules

- Convert file-by-file
- Keep logic unchanged
- Ensure code still works after change

## Frontend (Vue3)

- Use `<script setup lang="ts">`
- Use Composition API only
- No logic in template

## Architecture

- No API calls inside components
- Use service layer
- Backend controllers must be thin

## Error Handling

- Always check null/undefined
- Use optional chaining or guards

## Output Requirements

- Code must be production-safe
- No breaking changes
- Minimal modifications only
