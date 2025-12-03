# 🗂️ Task Manager (Laravel 12)

A production-grade **Laravel 12 backend** showcasing clean architecture, strict typing,
fully tested APIs, and modern engineering practices.

Includes JWT auth (Redis blacklist), policy-based RBAC, DTOs + Services,
OpenAPI 3.1 documentation, RabbitMQ message pipeline, and a complete CI workflow.

---

## 🚀 Features Overview

| Category           | Highlights                                                                           |
|--------------------|--------------------------------------------------------------------------------------|
| **Framework**      | Laravel 12 · Pest · PHPStan · Pint                                                   |
| **Auth**           | JWT with Redis blacklist (secure logout & token invalidation)                        |
| **Access Control** | Policy-based RBAC (Owner/Admin/Member/Viewer)                                        |
| **Validation**     | Form Requests + typed DTOs                                                           |
| **Serialization**  | Consistent API responses via Resource classes                                        |
| **Testing**        | Full endpoint coverage (projects, tasks, labels, members, comments)                  |
| **Messaging**      | RabbitMQ message pipeline (Outbox → Retry → Inbox)                                   |
| **Docs**           | OpenAPI 3.1 + Postman Collection (auto-validated in CI)                              |
| **CI/CD**          | GitHub Actions: lint → static analysis → migrate/seed → parallel tests → docs checks |
| **Runtime**        | Laravel Sail (MySQL, Redis, RabbitMQ)                                                |

---

## ⚙️ Setup & Quick Start

```bash
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
```

Default stack includes:
- PHP 8.2
- MySQL
- Redis
- RabbitMQ
- Laravel Sail runtime

Reset DB:
```bash
./vendor/bin/sail artisan migrate:fresh --seed
```

---

## 🔐 Auth & Permissions (Overview)

The system uses **JWT authentication + policy-based RBAC**:

| Role       | Permission Summary                  |
|------------|-------------------------------------|
| **Owner**  | Full access; can transfer ownership |
| **Admin**  | Manage members, tasks, labels       |
| **Member** | Work on tasks (CRUD own tasks)      |
| **Viewer** | Read-only                           |

Access control is enforced through:

- **Policies**: ProjectPolicy, TaskPolicy
- **Middleware**: EnsureProjectMember
- **Scoped model binding**: preventing cross-project access

➡️ Full details: [`docs/auth-permissions.md`](docs/auth-permissions.md)

---

## 🧱 Domain Models

User, Project, ProjectMember, Task, Label, TaskComment, TaskLabel
with clean separation between validation (Form Requests), transformation (DTOs),
and serialization (Resources).

➡️ See [`docs/domain-models.md`](docs/domain-models.md) (optional)

---

## 📘 API Documentation

Two documentation formats are provided:

### OpenAPI 3.1

Location: [`docs/openapi/openapi.yaml`](docs/openapi/openapi.yaml)
Visual UI available locally:
- Swagger UI → http://localhost/swagger.html
- Redoc → http://localhost/redoc.html

### Postman Collection

Location: [`docs/postman/task_manager_api.postman_collection.json`](docs/postman/task_manager_api.postman_collection.json)

Includes:
- Auth injection
- Dynamic variables (project_id, task_id, label_id…)
- All endpoint workflows

### Example Workflows

➡️ Moved to: [`docs/api-examples.md`](docs/api-examples.md)

---

## 🧪 Testing

Run all tests (parallel):
```bash
composer test:parallel
```

Covers:
- Auth
- Project / Task / Label / Member flows
- Policy boundaries
- JWT blacklist logic
- RabbitMQ Outbox/Inbox pipeline (mocked channel + message)

---

## ⚡ CI/CD

GitHub Actions runs:
- Pint (code style)
- PHPStan (static analysis)
- Migrations + seed (SQLite)
- Redis service
- Pest (parallel tests)
- OpenAPI validation (Redocly + Spectral)
- Postman collection checks

---

## 🔄 Message Pipeline (RabbitMQ)

This project implements a production-grade message pipeline:

Core components:
- Outbox Pattern → durable event storage
- Dispatcher → batched delivery to RabbitMQ
- Retry Exchanges → 10s → 60s → 5m
- Consumer lifecycle（idempotency + version ordering）
- Inbox Pattern → deduplication
- Parking Queue (final DLQ)

➡️ Full architecture: [`docs/message-pipeline.md`](docs/message-pipeline.md)

---

## 🧠 Design Decisions

Focused on testability, strict typing, and maintainability:
- Policies + middleware for clean authorization boundaries
- DTOs for typed input to service layer
- Thin controllers; business logic moved to services
- Resources for consistent JSON output
- Static analysis with PHPStan (Level 6)
- Comprehensive boundary tests

➡️ Full write-up: [`docs/design-decisions.md`](docs/design-decisions.md)

---

## License

MIT License © 2025 [Lv Hui]
For educational and portfolio demonstration purposes only.
