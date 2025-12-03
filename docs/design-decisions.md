# Design Decisions

This document explains why the system is built in this way.

---

## 1. Policies + Middleware Over Inline Checks

Instead of:

```php
if ($user->id !== $project->owner_id) { ... }
```

All authorization lives in:
- ProjectPolicy
- TaskPolicy
- EnsureProjectMember middleware

This makes the system auditable, testable, and clean.

---

## 2. DTO + FormRequest

Validation rules belong in FormRequest.
Service methods receive validated, typed DTOs instead of raw arrays.

Benefits:
- Static analysis becomes useful
- No hidden fields leak through requests
- Intentional, explicit input to services

---

## 3. Thin Controllers + Service Layer

Controllers only orchestrate:
- authorize
- validate
- pass DTOs to service
- return resource response

Business logic is inside services — easy to test in isolation.

---

## 4. Resource Classes for API Stability

Each response is shaped by a *Resource class.
- Consistency across endpoints
- Separation of DB schema from API response format

---

## 5. Redis-backed JWT Blacklist

Supports secure logout by blacklisting old tokens.
- Tokens become invalid immediately
- Testable token lifecycle behavior

---

## 6. Static Analysis & Type Safety

PHPStan level 6 + generics ensure:
- predictable DTOs
- correct resource structures
- fewer runtime errors

---

## 7. Full Boundary Testing

Every 403/404/422/409 scenario is tested.

Ensures correctness, not just happy-path behavior.
