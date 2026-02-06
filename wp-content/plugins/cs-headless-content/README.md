=== CS Headless Content ===
Contributors: bryan-campbell
Donate link: https://example.com/
Tags: headless, graphql, cpt, wordpress, case-study
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Case-study plugin defining structured content models for a headless WordPress architecture.

== Description ==

CS Headless Content is a **case‑study plugin** used to demonstrate how WordPress can act as a
pure content platform in a modern, headless architecture.

This plugin is responsible **only for content modeling**, not API logic.

It defines custom post types, taxonomies, and metadata that are consumed by:

- WPGraphQL
- A Node.js API gateway
- Future Next.js / React frontends

The goal is to showcase **clean separation of concerns**:

- WordPress → editorial UI + content storage
- GraphQL → API schema
- Node.js → orchestration, pipelines, integrations
- Frontends → presentation

== Content Models ==

=== Resource ===
General knowledge objects such as tutorials, tools, or links.

Fields:

- Title
- Content
- Excerpt
- Featured flag (`cs_featured`)
- External URL (`resource_url`)
- Difficulty
- Topics taxonomy
- Resource Type taxonomy

=== Collection ===
Curated groupings of Resources.

Fields:

- Title
- Content
- Topics taxonomy

=== Person ===
People independent of WordPress users.

Fields:

- Title (name)
- Content (bio)
- Role meta field

=== Group ===
Logical groupings of People.

Examples:

- Editors
- Contributors
- Founders

Used in conjunction with custom GraphQL mutations to associate People ↔ Groups.

=== NPS Alert ===
Machine‑generated content synced from the U.S. National Park Service API.

Purpose:

- Demonstrates ingesting third‑party data
- Supports editor overrides
- Acts as a bridge between external APIs and editorial workflows

Fields:

- NPS Alert ID (external unique ID)
- Park code
- Category
- Source URL
- Last indexed date
- Editor lock flag (prevents overwrites)

== Headless Behavior ==

This plugin intentionally disables the traditional WordPress frontend:

- All non‑admin, non‑REST routes return 404
- WordPress is treated strictly as a content service
- Content is accessed via REST or GraphQL only

== CLI Support ==

Includes WP‑CLI seed commands for local development:

- Seed Groups
- Seed People
- Seed sample content

This enables predictable demo data for GraphQL and Node.js pipelines.

== What This Plugin Does NOT Do ==

- No frontend rendering
- No GraphQL resolvers or mutations
- No authentication logic
- No API orchestration

Those concerns are handled by:

- `cs-headless-graphql`
- `cs-node-headless-api`

== WordPress → Node Webhook: NPS Alert Publish Events ==

As part of the headless pipeline case study, this plugin emits a webhook event
whenever an **NPS Alert post transitions to a new status**, most importantly
when it is **published**.

This demonstrates how WordPress can act as an **event source** in a modern
distributed system.

=== Purpose ===

The webhook enables downstream systems to react to editorial actions, such as:

- Notifying a Node.js API gateway that an alert is now public
- Allowing the gateway to forward publish state to Laravel
- Keeping dashboards and external systems in sync with WordPress editorial status

This avoids polling and reinforces an **event-driven architecture**.

=== Triggered Events ===

The webhook is fired on post status transitions for the `nps_alert` post type,
including:

- draft → publish
- scheduled → publish
- publish → draft (unpublish)
- publish → private

The payload includes both the **previous** and **new** status so consumers can
decide how to react.

=== Webhook Destination ===

The webhook URL is configurable via environment or constant and is expected to
point to the Node.js API gateway.

Example (local development):

== Installation ==

1. Copy the plugin into `/wp-content/plugins/cs-headless-content`
2. Activate via WordPress Admin
3. Ensure WPGraphQL is installed and active
4. Use WP‑CLI seed commands or GraphiQL to verify content models

== Changelog ==

= 0.2.0 =

- Added NPS Alert content model
- Added editor‑lock pattern for machine‑synced content
- Refined headless routing behavior

= 0.1.0 =

- Initial release
- Resources, Collections, People, Groups
