# Domain Models

A detailed look at the core domain objects and their responsibilities.

---

## User
- Implements `JWTSubject`
- Owns projects and tasks
- Authenticated via JWT

---

## Project
- Owned by a single user
- Has many members, labels, tasks
- Supports soft-deletes
- Role-based permissions depend on `ProjectMember`

---

## ProjectMember
- Connects User ↔ Project
- Stores role: owner/admin/member/viewer

---

## Task
- Belongs to a project
- Has:
    - creator
    - optional assignee
    - comments
    - labels
- Supports claim/assign workflow

---

## Label
- Unique per `(project_id, name)`
- Helps categorize tasks

---

## TaskComment
- Authored by a user
- Attached to a task
- Chronologically ordered

---

## TaskLabel
- Pivot table linking tasks and labels
- Many-to-many relationship

---

These models form the core of the system’s domain and are reflected in the OpenAPI spec and feature tests.
