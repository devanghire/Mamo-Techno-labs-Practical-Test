# Event Ingestion Service (Multi-Tenant Architecture)

## Overview

This project implements a **multi-tenant event ingestion service** using **Laravel 12**. It is designed to receive high-volume events, validate and encode payloads, and process them asynchronously using queues to ensure performance, reliability, and scalability.

The architecture follows best practices for SaaS systems, including tenant isolation, asynchronous processing, and separation of core and analytics data.

---

## Key Features

* **Multi-Tenant Support**

  * Every request is associated with a `tenant_id`
  * Ensures strict data isolation between tenants

* **Queue-Based Asynchronous Processing**

  * Events are pushed to a queue for background processing
  * Keeps API responses fast

* **Traffic Spike Handling**

  * Message queue absorbs sudden spikes in incoming events

* **Reliable Processing**

  * Queue workers retry failed jobs automatically
  * Prevents data loss

* **Analytics-Friendly Design**

  * Event data is stored separately from core application data

---

## Tech Stack

* PHP 8+
* Laravel 12
* MySQL (InnoDB, utf8mb4)
* Laravel Queue (Database driver)

---

## Payload Encoding

Before pushing data to the queue, the validated payload is encoded to ensure safe transport.

### Encoding

```php
$data = $validator->validated();
$payload = base64_encode(json_encode($data));
```

### Decoding (Worker Side)

```php
$decoded = json_decode(base64_decode($payload), true);
```

---

## Queue Configuration

The system uses Laravel's **database queue driver**.

### `.env`

```env
QUEUE_CONNECTION=database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=event-ingestion
DB_USERNAME=root
DB_PASSWORD=
```

### Queue Tables

* `jobs`
* `failed_jobs`

---

## Data Flow (API → Queue → Worker → DB)

1. Client sends an event request to the API
2. API validates the request payload and tenant information
3. Payload is encoded and pushed to the queue
4. Queue worker consumes the message
5. Worker decodes the payload and stores event data in the database

---

## Tenant Isolation

Tenant isolation is enforced by:

* Requiring a `tenant_id` in every request
* Attaching `tenant_id` at the ingestion layer
* Using composite unique keys such as `(tenant_id, session_id)`
* Ensuring all queries are scoped by `tenant_id`

This guarantees that each tenant’s data is logically isolated even though the infrastructure is shared.

---

## Idempotency

Idempotency is implemented to prevent duplicate event processing:

* Each event or session is uniquely identified by `(tenant_id, session_id)`
* A database-level unique constraint ensures duplicates are rejected
* Safe retries are supported without creating duplicate records

This approach ensures reliable processing during retries or replays.

---

## Assumptions & Trade-offs

### Assumptions

* Events are immutable once received
* All timestamps are sent in UTC
* Tenants provide valid tenant identifiers

### Trade-offs

* Using a database queue simplifies setup but may not scale as efficiently as Redis or SQS
* Base64 encoding introduces minor overhead but ensures safe payload transport
* Logical tenant isolation is used instead of physical database separation

---

## How to Run Locally

### Prerequisites

* PHP 8+
* Composer
* MySQL

### Steps

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Run migrations:

```bash
php artisan migrate
```

Start the queue worker:

```bash
php artisan queue:work
```

The API is now ready to receive events locally.

---

## API Details

### 1. Generate Encoded Payload

* **Method:** POST
* **URL:** `http://127.0.0.1:8000/api/encode`
* **Request Body:**

```json
{
  "tenant_id": "123",
  "session_id": "assw123",
  "event_type": "page_view",
  "timestamp": "2026-01-01 15:12:11"
}
```

* **Response:**

```json
{
  "payload": "eyJ0ZW5hbnRfaWQiOiIxMjMiLCJzZXNzaW9uX2lkIjoiYXNzdzEyMy4uLiIs"
}
```

### 2. Submit Event Payload

* **Method:** POST
* **URL:** `http://127.0.0.1:8000/api/events`
* **Request Body:**

```json
{
  "payload": "eyJ0ZW5hbnRfaWQiOiIxMjMiLCJzZXNzaW9uX2lkIjoiYXNzdzEyMyIsImV2ZW50X3R5cGUiOiJwYWdlX3ZpZXciLCJ0aW1lc3RhbXAiOiIyMDI2LTAxLTAxIDE1OjEyOjExIn0="
}
```

* **Response:**

```json
{
  "status": "accepted"
}
```

---

## Error Handling

* Invalid payloads are rejected during validation
* Failed jobs are retried automatically by Laravel
* Failed jobs can be inspected via the `failed_jobs` table

---

## Architecture Summary

1. Client sends event data
2. API validates tenant and event payload
3. Payload is encoded and pushed to the queue
4. Queue worker processes and stores analytics data
5. Core application remains fast and unaffected

---

## Notes

* All timestamps are expected in **UTC**
* Column lengths are explicitly defined to avoid MySQL index limitations
* Global string length overrides are avoided in production-ready designs

---

## Author

**Devang Hire**

---

This project demonstrates clean architecture, correct data flow, and a scalable design suitable for multi-tenant SaaS systems.
