# API Examples

This document contains practical examples showing how to interact with the Task Manager API.

---

## Authentication

### Login
```bash
POST /api/auth/login
{
  "email": "demo@example.com",
  "password": "password"
}
```

### Refresh Token

```bash
POST /api/auth/refresh
Authorization: Bearer <token>
```

### Logout

```bash
POST /api/auth/logout
Authorization: Bearer <token>
```

---

## Project

### List Projects

```bash
GET /api/projects
Authorization: Bearer <token>
```

### Create Project

```bash
POST /api/projects
{
  "name": "My Project",
  "description": "Demo"
}
```

### Transfer Ownership

```bash
POST /api/projects/1/owner
{
  "new_owner_id": 5
}
```

---

## Tasks

### Create Task

```bash
POST /api/projects/1/tasks
{
  "title": "Implement blacklist",
  "priority": "normal"
}
```

### Claim Task

```bash
POST /api/tasks/5/claim
```

### Assign Task

```bash
POST /api/tasks/5/assign
{
  "assignee_id": 3
}
```

### Add Comment

```bash
POST /api/tasks/5/comments
{
  "body": "This needs review"
}
```

---

## Members & Labels

### Add Member

```bash
POST /api/projects/1/members
{
  "user_id": 10,
  "role": "member"
}
```

### Create Label

```bash
POST /api/projects/1/labels
{
  "name": "backend"
}
```

---

For more structured definitions, see the OpenAPI spec.
