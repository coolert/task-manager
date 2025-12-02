# Message Pipeline (RabbitMQ)

This document describes the **event-driven message pipeline** used in the project.
It showcases a production-style integration pattern built with:

- **Outbox (DB-backed event storage)**
- **RabbitMQ (topic + delayed retries)**
- **Worker Process**
- **Inbox (idempotency + versioning)**

---

## 1. Architecture Overview

```
Task Created (HTTP)
       ↓
Outbox Record (DB)
       ↓
Outbox Dispatcher
       ↓
RabbitMQ Exchange
       ↓
Main Queue
       ↓
Worker Process
       ↓
BaseHandler → Handler Logic
       ↓
Inbox Record (DB)
```

Purpose:

- reliable async delivery
- retry with backoff
- avoid duplicate processing
- handle out-of-order events

---

## 2. RabbitMQ Topology

### Exchanges
- `task.main.exchange` (topic)
- Retry exchanges: `10s`, `60s`, `5m`
- `task.parking.exchange` (final fail)

### Queues
- Main queue: `task.main.queue`
- Retry queues: `task.retry.{10s|60s|5m}.queue`
  Each queue has:
    - `x-message-ttl`
    - `x-dead-letter-exchange = task.main.exchange`
    - `x-dead-letter-routing-key = task.created`
- Parking queue: `task.parking.queue`

Retry flow:

```
Main → 10s → 60s → 5m → Parking
```

---

## 3. Outbox Pattern

- Stored in DB during the same transaction as business logic
- Prevents message loss
- Ensures publish-only-after-commit
- `outbox:dispatch` sends pending records to RabbitMQ

Why used:

- safe integration with external systems
- avoids publishing during failed transactions
- full traceability (attempts, errors)

---

## 4. Worker & Consumer Discovery

### Worker Process (`mq:work`)
- Long-running consumer
- Auto-reconnect
- Prefetch = 1
- Dispatches messages by routing key

### Consumer Discovery
Handlers are registered via:

```php
#[Consumes('task.created')]
class TaskCreatedHandler extends BaseHandler { ... }
```

Framework automatically maps:
`task.created → TaskCreatedHandler`.

---

## 5. Handler Pipeline (BaseHandler)

Each message goes through:

1. **Idempotency (Inbox)**
    - Skip if same `message_id` already succeeded/skipped

2. **Version ordering**
    - Ignore older versions of the same `business_key`

3. **process()** (userland logic)

4. **Persistence**
    - Mark as SUCCESS / SKIPPED / FAILED

5. **Retry logic**
    - Determine retry stage via `x-death`
    - Route to next retry exchange
    - Or send to parking

This pattern ensures:
- exactly-once effect (within handler logic)
- safe retries
- controlled backoff

---

## 6. Inbox

Stores:
- `message_id`
- `business_key`
- `version`
- `status`
- `payload`
- `processed_at`

Used for:
- deduplication
- preventing stale updates
- auditing

---

## 7. Example Event Flow (task.created)

1. User creates a Task
2. Controller writes Task + Outbox record
3. Dispatcher publishes to RabbitMQ
4. Worker consumes message
5. `TaskCreatedHandler` posts webhook
6. Inbox records SUCCESS
7. Message ACKed

If webhook fails → retries → parking.

---

## 8. Why This Matters (Portfolio Value)

This pipeline demonstrates competencies commonly required in real systems:

- distributed system reliability patterns
- async integration (webhooks, external APIs)
- message retries with backoff
- idempotency & event ordering
- long-running workers with reconnection logic
- clean handler abstraction + auto-discovery
- high testability without requiring real RabbitMQ

This goes **beyond CRUD APIs**, showing backend engineering maturity.

---
