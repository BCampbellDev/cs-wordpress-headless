=== CS Headless GraphQL ===
Contributors: bryancampbell
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 0.2.0
License: GPLv2 or later

== Description ==

CS Headless GraphQL extends WPGraphQL with **custom queries and mutations** used by the Case Study Headless stack.

This plugin is intentionally *not generic*. It exists to demonstrate:
- Advanced WPGraphQL schema customization
- Custom root queries
- Custom mutations with authorization and validation
- Cross‑system data syncing (Node.js → WordPress)
- Real‑world editorial + machine‑generated content workflows

It works in tandem with:
- **cs-headless-content** (CPTs + fields)
- **cs-node-headless-api** (Node.js gateway)
- **Next.js frontend (future case study)**

== What This Plugin Adds ==

=== Custom Queries ===

= featuredResources =
Returns a curated list of `Resource` posts flagged as featured.

Example:
```
query {
  featuredResources(first: 5) {
    nodes {
      databaseId
      title
      difficulty
    }
  }
}
```

=== Custom Mutations ===

= addPersonToGroup =
Associates an existing `Person` with a `Group`.

```
mutation {
  addPersonToGroup(input: { personId: 12, groupId: 16 }) {
    added
    groupIds
    person {
      databaseId
      title
    }
  }
}
```

Authorization:
- Requires authenticated user
- Requires `edit_posts` capability

---

= upsertNpsAlert =
Creates or updates an `nps_alert` post using an external NPS alert ID.

Designed for **machine‑driven ingestion** where editors can later modify content.

```
mutation {
  upsertNpsAlert(input: {
    npsId: "6EF2FA11-86EE-47CA-960B-7FDAE78C05ED"
    title: "Park Closure: Visitor Center Closed"
    npsParkCode: "cane"
    npsCategory: "Park Closure"
    npsUrl: "https://www.nps.gov/cane/index.htm"
    npsLastIndexedDate: "2026-01-31 00:00:00.0"
    description: "Imported from NPS API"
  }) {
    created
    alertId
    alert {
      databaseId
      title
      npsParkCode
    }
  }
}
```

Behavior:
- Uses `npsId` as the unique key
- Creates post if missing
- Updates machine fields every sync
- Respects editor lock for content fields

== Architectural Goals ==

This plugin demonstrates:

- Custom GraphQL schema design
- Server‑side validation + sanitization
- Capability‑based authorization
- Safe upsert patterns
- Separation of editorial vs machine data
- Headless‑first thinking (no frontend rendering)

== Installation ==

1. Upload plugin to `/wp-content/plugins/cs-headless-graphql`
2. Activate via WP Admin
3. Ensure WPGraphQL is installed and active
4. Activate `cs-headless-content` first

== Related Case Studies ==

- **Case Study #1** – Headless WordPress content modeling
- **Case Study #2** – WPGraphQL schema customization
- **Case Study #3** – Node.js gateway + data pipelines
- **Case Study #4** – Next.js frontend (planned)

== Changelog ==

= 0.2.0 =
- Added `upsertNpsAlert` mutation
- Added external data ingestion pattern
- Hardened auth + error handling

= 0.1.0 =
- Initial GraphQL schema extensions
- Featured resources query
- Person ↔ Group mutation
