# cs-headless-content

Headless WordPress **content model** plugin for the cs-wordpress-headless case study.

This plugin defines the core data layer used by both REST and GraphQL consumers.
It intentionally focuses on content modeling, relationships, and developer tooling
rather than front-end rendering.

---

## What this plugin demonstrates

### Custom Post Types (CPTs)
The plugin registers multiple CPTs intended for headless consumption:

- **Person**
- **Group**
- **Resource**
- **Collection**

Each CPT is configured with:
- `show_in_rest` for REST API usage
- `show_in_graphql` with explicit GraphQL names
- Meaningful supports (title, editor, excerpt, etc.)

---

## Relationship model

### Person → Groups
Membership is stored on the **Person** post as post meta:

- Meta key: `cs_group_ids`
- Value: array of Group `databaseId`s

This mirrors a real-world WordPress pattern where relationships are owned by one side
and resolved dynamically by the API layer.

The GraphQL plugin builds bidirectional relationships on top of this structure.

---

## Developer tooling (WP-CLI)

This plugin exposes WP-CLI commands for seeding development data.

### Example
```bash
lando wp cs seed-groups
```

Commands are idempotent and safe to re-run.

List all custom commands:
```bash
lando wp help cs
```

---

## REST API examples

### List People
```bash
GET /wp-json/wp/v2/people
```

### List Groups
```bash
GET /wp-json/wp/v2/groups
```

---

## Purpose in the case study

This plugin intentionally contains **no UI logic**.

It exists to show:
- Clean CPT modeling
- Relationship storage strategies
- Readiness for headless consumption
- Separation of concerns between content and API layers
