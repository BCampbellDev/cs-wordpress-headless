# cs-headless-graphql

Custom WPGraphQL extension plugin for the cs-wordpress-headless case study.

This plugin extends the WPGraphQL schema with:
- Custom root queries
- Custom mutations
- Bidirectional relationships
- Real-world resolver logic and validation

---

## What this plugin demonstrates

### Advanced WPGraphQL usage
- Schema extension via `graphql_register_types`
- Custom root queries
- Custom mutations with authorization
- Data hydration using WPGraphQL DataSource

### Real-world GraphQL modeling
- Bidirectional relationships
- Handling WordPress serialized meta
- Avoiding nullability errors in GraphQL execution
- Correct use of `databaseId` vs global `id`

---

## GraphQL schema additions

### Custom fields

#### Person.groups
Returns groups a person belongs to.

#### Group.people
Returns people belonging to a group.
Resolved by querying Person meta (`cs_group_ids`).

---

## Custom root queries

### featuredResources
Returns a curated list of Resource posts.

```graphql
query {
  featuredResources(first: 5) {
    nodes {
      databaseId
      title
    }
  }
}
```

---

## Custom mutations

### addPersonToGroup

Adds an existing Person to an existing Group.

- Validates post types
- Requires authentication
- Prevents invalid relationships

```graphql
mutation {
  addPersonToGroup(input: {
    personId: 12
    groupId: 16
  }) {
    added
    groupIds
    person {
      databaseId
      title
      groups {
        databaseId
        title
      }
    }
  }
}
```

> `personId` and `groupId` are WordPress `databaseId` values, not global GraphQL IDs.

---

## Query examples

### List people and their groups
```graphql
query {
  people(first: 20) {
    nodes {
      databaseId
      title
      groups {
        databaseId
        title
      }
    }
  }
}
```

### List groups and their people
```graphql
query {
  groups(first: 20) {
    nodes {
      databaseId
      title
      people {
        databaseId
        title
      }
    }
  }
}
```

---

## Authentication notes

Mutations require an authenticated WordPress user.

Local development typically uses:
- WordPress Application Passwords
- HTTP Basic Auth

---

## Purpose in the case study

This plugin exists to show:
- Deep understanding of WPGraphQL internals
- Practical GraphQL API design
- Mutation safety and validation
- Real-world relationship modeling in headless WordPress

It is intentionally verbose and explicit for educational and portfolio purposes.
