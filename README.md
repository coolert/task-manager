# 🗂️ Task Manager (Laravel 12)

A **clean, fully tested task management backend** built with **Laravel 12**.
Designed as a **portfolio project** to showcase strong backend engineering:
**auth, policies, middleware, DTOs, resources, Redis JWT blacklist, and CI pipeline.**

---

## 🚀 Features Overview

| Category | Highlights                                                                    |
|-----------|-------------------------------------------------------------------------------|
| **Framework** | Laravel 12 + Pest PHP + PHPStan + Pint                                        |
| **Auth** | JWT-based (via `php-open-source-saver/jwt-auth`), with Redis-backed blacklist |
| **Access Control** | Policy-based RBAC: Owner / Admin / Member / Viewer                            |
| **Validation Layer** | Form Requests + DTO pattern                                                   |
| **Serialization** | Resource classes (type-safe via `@mixin` hints)                               |
| **Testing** | Comprehensive feature coverage across Projects, Tasks, Members, Labels, Comments       |
| **CI/CD** | GitHub Actions: Lint → PHPStan → Migrate → Seed → Parallel Tests              |
| **Database** | MySQL / SQLite (in CI) + Redis (for JWT cache)                                |
| **Containerization** | Laravel Sail (PHP + Redis + MySQL stack)                                      |

---

## ⚙️ Setup & Quick Start

### Environment

```bash
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed --class=DatabaseSeeder
```

Default stack includes:
- PHP 8.2
- Redis (for JWT blacklist & caching)
- MySQL
- Laravel Sail runtime

To reset DB:
```bash
./vendor/bin/sail artisan migrate:fresh --seed
```

---

### Authentication

```bash
POST /api/auth/login
# Request
{ "email": "demo@example.com", "password": "password" }

# Response
{
  "token": "eyJ0eXAiOiJKV1QiLCJh...",
  "token_type": "Bearer",
  "expires_in": 3600
}
```

Other endpoints:

| Method | Route | Description |
|---------|--------|-------------|
| `GET` | `/api/auth/me` | Get current user info |
| `POST` | `/api/auth/refresh` | Refresh JWT |
| `POST` | `/api/auth/logout` | Invalidate token (Redis blacklist) |

---

## 🔐 Auth & Permissions

### Role Model
Each **project** defines access for 4 roles:

| Role | Description | Can Manage Members | Can Edit Tasks | Can Delete Project |
|------|--------------|-------------------|----------------|--------------------|
| **Owner** | Project creator | ✅ | ✅ | ✅ |
| **Admin** | Delegated manager | ✅ | ✅ | ❌ |
| **Member** | Contributor | ❌ | ✅ (own tasks) | ❌ |
| **Viewer** | Read-only | ❌ | ❌ | ❌ |

### Policy Enforcement
- **`ProjectPolicy`**
  - `view`, `update`, `delete`, `manageMembers`, `transferOwnership`, `viewLabels`, `manageLabels`
- **`TaskPolicy`**
  - `view`, `create`, `update`, `delete`, `assign`, `claim`, `comment`, `label`

Policies are automatically resolved via Laravel’s `authorizeResource()` or manual `$this->authorize()` calls.


### Middleware
`EnsureProjectMember`
Ensures the authenticated user belongs to the target project;
resolves project from either route `{project}` or `{task}`.

---

## 🧱 Domain Models

| Model | Description |
|--------|-------------|
| **User** | Implements `JWTSubject`, provides login + token refresh |
| **Project** | Owner, Name, Description, Soft Deletes |
| **ProjectMember** | Maps users to projects with role |
| **Task** | Belongs to Project, has Assignee, Creator, Labels, Comments |
| **Label** | Project-scoped tags with unique (name + project_id) |
| **TaskComment** | User-authored comment per task |
| **TaskLabel** | Pivot table between Tasks and Labels |

---

## 🧩 Seeding Profiles

| Profile | Purpose |
|----------|----------|
| `mini` | Minimal dataset for CI & quick local testing |
| `demo` | Larger dataset for screenshots or perf testing |

Configure via `.env`:
```bash
SEED_PROFILE=mini
```

---

## 🧪 Testing

All feature tests use Pest and run in parallel for faster CI.

### Run All Tests
```bash
composer test:parallel
```

### What’s Covered
- Auth & JWT (login / refresh / logout)
- Project CRUD, transfer, soft delete
- Member add/remove/update roles
- Label CRUD, conflict checks, task association
- Task CRUD, assign, claim, comment
- Validation & Policy boundaries (403, 404, 409, 422, etc.)
- Redis-backed JWT blacklist persistence
- CI-safe SQLite testing

### Example Assertion
```php
requestAs($user, 'POST', '/api/tasks/1/claim')
    ->assertOk()
    ->assertJsonPath('data.assignee.id', $user->id);
```

---

## ⚡ CI/CD

The complete CI workflow is defined in `.github/workflows/ci.yml`.
It includes:

- Dependency installation (`composer install`)
- `.env.testing` initialization & JWT secret generation
- Database migration & seeding (SQLite)
- Redis service setup (for JWT blacklist)
- Code style check (Pint)
- Static analysis (PHPStan level 6)
- Parallel feature testing (Pest)

Example excerpt:

```yaml
jobs:
  php:
    services:
      redis:
        image: redis:7
        ports: ["6379:6379"]
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
      - run: composer install
      - run: php artisan jwt:secret --env=testing --force
      - run: composer test:parallel
```

Runs full pipeline:
- Lint (Pint)
- Static analysis (PHPStan)
- Migrate + Seed (SQLite)
- Redis service
- Parallel feature tests (Pest)

Each PR runs the same suite to ensure code quality and regression safety.

---

## 🧰 Tech Highlights

| Area | Stack |
|------|--------|
| **Framework** | Laravel 12 |
| **Auth** | JWT via `php-open-source-saver/jwt-auth` |
| **Cache/Queue** | Redis |
| **Tests** | Pest + Parallel + SQLite |
| **Static Analysis** | PHPStan (Level 6, planned upgrade to 7–8) |
| **Style** | Pint |
| **CI/CD** | GitHub Actions (Lint → Stan → Migrate → Test) |
| **Containers** | Laravel Sail |

---

## 🧠 Design Decisions

This project was intentionally designed to follow **clean backend engineering principles**
rather than simple CRUD scaffolding. Each layer has a clear purpose:

### 1️⃣ Policy + Middleware Instead of Hardcoded Role Checks
Instead of writing `if ($user->id !== $project->owner_id)` in controllers,
authorization is centralized in **Policy classes** (`ProjectPolicy`, `TaskPolicy`),
while cross-cutting “membership checks” are handled by a middleware (`EnsureProjectMember`).
- This separation makes **controller actions clean**, and **access control auditable & testable**.

### 2️⃣ Form Requests + DTOs
Each request class (`*Request`) focuses only on validation and sanitization,
then transforms validated data into a **DTO** (`*DTO`) used by services.
- This ensures the service layer receives **typed, intentional inputs**
instead of arbitrary arrays — improving **readability**, **static analysis**, and **testability**.

### 3️⃣ Service Layer (Application Logic)
Controllers are intentionally kept thin:
- Authorization handled by Policies
- Validation handled by FormRequests
- Business logic encapsulated in dedicated Service classes

Example:
```php
public function store(LabelStoreRequest $request, Project $project, LabelService $service): JsonResponse
{
    $this->authorize('manageLabels', $project);
    $dto   = $request->toDTO();
    $label = $service->createLabel($dto);
    return LabelResource::make($label)->response()->setStatusCode(201);
}
```
Each controller method focuses on composition, not logic — typically only a few lines of code.

- Keeps the system **modular**, easy to maintain, and ready for refactoring
(e.g., introducing queues, event broadcasting, or domain events later).

### 4️⃣ Resource Classes for Consistent API Responses
Each model has a corresponding `*Resource` defining **public JSON structure**.
- Guarantees consistency across endpoints,
and lets you control response shape separately from database schema.

### 5️⃣ Redis-Backed JWT Blacklist
JWT logout is handled using a **Redis-based blacklist**.
This allows instant token invalidation and testable token lifecycle behavior
(e.g., tokens are truly rejected after logout).
- Demonstrates understanding of **stateful JWT** handling, not just “token = string”.

### 6️⃣ Strict Static Analysis
Static checks are enforced via **PHPStan (level 6)** and **Pint**.
Generics (`@template`, `@extends`, `@mixin`) are used in DTOs and Resources,
ensuring **type-safe data transformation** even within dynamic Laravel code.

### 7️⃣ Full Boundary Test Coverage
Every controller endpoint has corresponding feature tests.
All **roles**, **validation states**, and **conflict edge cases (409/422)** are asserted.
- Confirms behavior from user perspective, not just happy paths.

---

**In short:**
This project demonstrates how to design maintainable, testable APIs
with clean separation of concerns, consistent validation, and verifiable authorization.

It’s not about using every Laravel feature — it’s about using Laravel effectively to build reliable, scalable backend systems.

---

## License

MIT License © 2025 [Lv Hui]
For educational and portfolio demonstration purposes only — not intended for production use.
