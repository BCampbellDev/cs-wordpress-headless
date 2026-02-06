# CS Headless WordPress (Case Study)

This WordPress project exists as part of a **larger headless, multi-service case study** demonstrating how WordPress can act as an **editorial CMS** inside a modern architecture.

It is **not** a traditional theme-driven WordPress site.

Instead, WordPress here is used for:

- Content modeling (CPTs, taxonomies, meta)
- Editorial workflows
- GraphQL APIs
- Webhooks into external systems
- Acting as one node in a distributed system

The frontend is intentionally disabled.

---

## Project Goals

This WordPress instance demonstrates:

- Headless content modeling using CPTs + meta
- WPGraphQL customization
- Custom GraphQL mutations
- Controlled editorial overrides
- Webhook-based communication with external services
- Separation of content, data, and orchestration concerns

It is designed to work alongside:

- A **Node.js API gateway**
- A **Laravel admin/data service**
- A future **frontend application** (Next.js)

---

## Project Structure

Most of the meaningful logic lives in **two custom plugins**:

```
wp-content/plugins/
├── cs-headless-content/
├── cs-headless-graphql/
```

Each plugin serves a distinct purpose.

---

## Plugin: CS Headless Content

**Path**
```
wp-content/plugins/cs-headless-content
```

**Purpose**

This plugin defines the **content model** for the headless system.

It registers:

- Custom Post Types (CPTs)
- Taxonomies
- Post meta fields
- Editorial UI elements
- Headless-only behavior (no frontend rendering)

### Key Content Types

- `resource` – articles, links, tutorials
- `collection` – curated groupings
- `person` – authors/speakers/contributors
- `group` – logical groupings of people
- `nps_alert` – National Park Service alerts

### Notable Features

- Shared taxonomies for filtering
- Meta fields exposed to REST and GraphQL
- Editor-controlled overrides
- Frontend requests intentionally return 404

This plugin answers the question:

> **“What content exists, and how is it structured?”**

---

## Plugin: CS Headless GraphQL

**Path**
```
wp-content/plugins/cs-headless-graphql
```

**Purpose**

This plugin extends **WPGraphQL** with custom queries, mutations, and webhooks.

It handles:

- Programmatic data ingestion
- Controlled upserts
- Editorial locks
- System-to-system communication

### Key Features

#### Custom GraphQL Mutations

- `upsertNpsAlert`
  - Creates or updates `nps_alert` posts
  - Uses external IDs (`nps_id`) for idempotency
  - Respects editor locks
  - Separates machine-managed fields from editorial content

#### Webhooks

- Listens for WordPress editorial transitions
- Sends publish/update events to the **Node.js API gateway**
- Enables downstream systems (Laravel) to track public visibility

This plugin answers the question:

> **“How does data move in and out of WordPress safely?”**

---

## Headless by Design

This WordPress instance does **not** serve a frontend.

- Theme output is disabled
- Non-API routes return 404
- REST and GraphQL are the primary interfaces

WordPress is treated as a **content service**, not a website.

---

## How This Fits Into the Larger System

High-level flow:

```
NPS API
   ↓
Node.js Gateway
   ↓
WordPress (editorial control)
   ↓
Node.js Webhook
   ↓
Laravel (admin + data dashboard)
```

Each system has a clearly defined responsibility.

---

## What Reviewers Should Look At

If you are reviewing this project:

1. Start with **cs-headless-content**
   - CPT registration
   - Meta configuration
   - Headless setup

2. Then review **cs-headless-graphql**
   - Custom GraphQL mutations
   - Auth checks
   - Upsert logic
   - Webhook dispatching

3. Note how WordPress:
   - Does *not* own orchestration
   - Does *not* own dashboards
   - Does *not* own frontend rendering

This is intentional.

---

## Summary

This WordPress project demonstrates how WordPress can be:

- A powerful editorial CMS
- A GraphQL data provider
- A participant in a distributed system
- Safely decoupled from frontend concerns

It is one piece of a broader headless architecture case study.
