# Auth & Permissions

A deeper explanation of authentication, project roles, and access control.

---

## Roles

| Role       | Permissions Summary                                  |
|------------|------------------------------------------------------|
| **Owner**  | Full access, can delete project & transfer ownership |
| **Admin**  | Manage labels/members, edit tasks                    |
| **Member** | Work on tasks (assign, claim, comment)               |
| **Viewer** | Read-only                                            |

---

## Policies

### ProjectPolicy
- view
- update
- delete
- manageMembers
- transferOwnership
- manageLabels

### TaskPolicy
- view
- create
- update
- delete
- assign
- claim
- comment
- label

Policies ensure consistent access control across the system.

---

## Middleware: `EnsureProjectMember`

- Validates that the authenticated user belongs to the project.
- Supports nested bindings like: `/projects/{project}/labels/{label}` Prevents accessing labels/tasks of a different project.

---

This keeps controllers clean and avoids repeated membership checks.
