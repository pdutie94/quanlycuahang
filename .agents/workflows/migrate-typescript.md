---
description: Convert a JavaScript file to TypeScript safely without breaking existing behavior
---

# Migrate JavaScript to TypeScript

## Steps

1. Identify the JavaScript file

2. Convert file:
    - rename `.js` → `.ts`
    - or add `lang="ts"` if Vue

3. Add minimal typing:
    - function parameters
    - return types

4. DO NOT:
    - rewrite logic
    - refactor structure
    - optimize code

5. Fix unsafe code:
    - null/undefined access
    - implicit `any`

6. Validate:
    - code still runs
    - no runtime errors

## Output

- updated file
- summary of changes
