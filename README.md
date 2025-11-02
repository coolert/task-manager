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
| **Testing** | Comprehensive endpoint coverage across Projects, Tasks, Members, Labels, Comments       |
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

All API endpoints return JSON. Clients are encouraged to send `Accept: application/json` to ensure consistent JSON error responses across auth and non-auth flows.

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

Route model binding uses `scopeBindings` for nested resources (e.g., `/projects/{project}/labels/{label}`), ensuring the nested model belongs to the parent (prevents cross-project access).

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

## 🧩 Example API Usage

A quick look at the core API endpoints — simple, predictable, and RESTful.

| Method | Endpoint | Description |
|---------|-----------|-------------|
| `POST` | `/api/auth/login` | Login and obtain JWT token |
| `GET` | `/api/auth/me` | Get authenticated user info |
| `GET` | `/api/projects` | List owned or joined projects |
| `POST` | `/api/projects` | Create a new project |
| `POST` | `/api/projects/{project}/tasks` | Create a task within a project |
| `POST` | `/api/tasks/{task}/assign` | Assign a task to another member |
| `POST` | `/api/tasks/{task}/claim` | Claim an unassigned task |
| `POST` | `/api/tasks/{task}/comments` | Add a comment to a task |
| `POST` | `/api/tasks/{task}/labels/{label}` | Attach a label to a task |

### Example Workflow

```bash
# 1. Login to get a token
curl -X POST https://example.com/api/auth/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email": "demo@example.com", "password": "password"}'

# 2. Create a new project
curl -X POST https://example.com/api/projects \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{"name": "My Demo Project"}'

# 3. Create a task within the project
curl -X POST https://example.com/api/projects/1/tasks \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{"title": "Implement Redis blacklist", "status": "todo", "priority": "normal"}'

# 4. Assign or claim the task
curl -X POST https://example.com/api/tasks/1/claim \
  -H "Authorization: Bearer <TOKEN>"
```

All responses follow consistent JSON formatting, using typed Laravel Resource classes for serialization.

Example success:

```json
{
  "data": {
    "id": 1,
    "title": "Implement Redis blacklist",
    "status": "todo",
    "priority": "normal",
    "assignee": { "id": 3, "name": "Jim" },
    "creator": { "id": 1, "name": "Mary" }
  }
}
```

Example validation error:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email field is required."
    ]
  }
}
```

---

## 🔧 Postman Collection (API Testing)

A complete **Postman Collection** is included under [`docs/postman/task_manager_api.postman_collection.json`](docs/postman/task_manager_api.postman_collection.json).

It covers all endpoints:
- Auth (login / refresh / logout)
- Projects (CRUD + transfer)
- Members (CRUD)
- Labels (CRUD)
- Tasks (CRUD, assign, claim, comments, labels)

Each request includes:
- Pre-request token injection
- Response-based variable setting (`project_id`, `task_id`, etc.)
- Consistent JSON headers (`Accept: application/json`)

To use it:
1. Open Postman → **Import** → Select the JSON file
2. (Optional) Set base URL variable: base_url = http://localhost
3. Run requests manually or via **Collection Runner**

This collection mirrors all Feature Tests, allowing you to manually validate each API flow.

---

## 📘 OpenAPI Specification & Validation

The project also provides a **full OpenAPI 3.1 specification** to describe every API endpoint with precise request and response schemas.
It complements the Postman collection and allows visual exploration or automated validation.

| File | Description |
|------|--------------|
| [`docs/openapi/openapi.yaml`](docs/openapi/openapi.yaml) | Complete OpenAPI 3.1 definition |
| [`docs/postman/task_manager_api.postman_collection.json`](docs/postman/task_manager_api.postman_collection.json) | Postman collection for manual testing |

### Why It Matters
- Guarantees consistency between code and documentation
- Enables Swagger UI or Redoc-based visualization
- Supports auto-generated clients and mock servers
- Validated automatically in pre-commit and CI

### Local Validation

```bash
# Validate OpenAPI syntax
npm run openapi:validate

# Lint OpenAPI rules (Spectral)
npm run openapi:lint

# Check Postman collection
npm run postman:check-json
npm run postman:check-vars
```

These checks are also part of the CI pipeline — ensuring the documentation always stays in sync with the codebase.

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

*(The full pipeline also includes OpenAPI and Postman validation steps — see package.json for details.)*

Runs full pipeline:
- Lint (Pint)
- Static analysis (PHPStan)
- Migrate + Seed (SQLite)
- Redis service
- Parallel feature tests (Pest)
- Documentation validation (OpenAPI + Postman)

Each PR runs the same suite to ensure consistent code quality and up-to-date API documentation.

---

## 🧰 Tech Highlights

| Area | Stack |
|------|--------|
| **Framework** | Laravel 12 |
| **Auth** | JWT via `php-open-source-saver/jwt-auth` |
| **Cache** | Redis |
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

### 7️⃣ Thorough Boundary Tests
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
