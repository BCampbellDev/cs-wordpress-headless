<?php

/**
 * Plugin Name:       CS Headless GraphQL
 * Description:       WPGraphQL schema extensions for the CS Headless case study.
 * Version:           0.1.0
 *
 * Requires Plugins:  wp-graphql, cs-headless-content
 */

// if (! defined('ABSPATH')) {
// 	exit;
// }

// /**
//  * Hard guards (never fatal).
//  * We also keep these guards even though we declare Requires Plugins,
//  * because not all environments enforce it.
//  */
// function cs_hg_is_wpgraphql_active(): bool
// {
// 	// return class_exists( '\WPGraphQL' );
// 	return is_plugin_active('wp-graphql');
// }

// function cs_hg_is_content_model_ready(): bool
// {
// 	return is_plugin_active('cs-headless-content');
// }

// if (! cs_hg_is_wpgraphql_active()) {
// 	return;
// }

add_action('graphql_register_types', function () {

	// @todo bug - something going on here
	// // If CPTs are not registered yet, don't register schema extensions.
	// if (! cs_hg_is_content_model_ready()) {
	// 	return;
	// }

	/**
	 * -----------------------------
	 * 1) Expose Meta as GraphQL fields
	 * -----------------------------
	 * (These are "nice" typed fields instead of forcing clients to parse raw meta.)
	 */
	register_graphql_field('Resource', 'difficulty', [
		'type'        => 'String',
		'description' => 'Difficulty level (beginner/intermediate/advanced).',
		'resolve'     => function ($post) {
			$val = get_post_meta($post->ID, 'difficulty', true);
			return $val !== '' ? $val : null;
		},
	]);

	register_graphql_field('Resource', 'resourceUrl', [
		'type'        => 'String',
		'description' => 'External URL for the resource.',
		'resolve'     => function ($post) {
			$url = get_post_meta($post->ID, 'resource_url', true);
			return $url ? esc_url_raw($url) : null;
		},
	]);

	register_graphql_field('Person', 'role', [
		'type'        => 'String',
		'description' => 'Role for the person (e.g. Author, Contributor).',
		'resolve'     => function ($post) {
			$val = get_post_meta($post->ID, 'role', true);
			return $val !== '' ? $val : null;
		},
	]);

	register_graphql_field('Person', 'groups', [
		'type'        => ['list_of' => 'Group'],
		'description' => 'Groups this person belongs to.',
		'resolve'     => function ($person_post) {

			$ids = get_post_meta($person_post->ID, 'cs_group_ids', true);
			if (! is_array($ids) || empty($ids)) {
				return [];
			}

			$ids = array_values(array_unique(array_map('intval', $ids)));

			$groups = get_posts([
				'post_type'      => 'group',
				'post__in'       => $ids,
				'orderby'        => 'post__in',
				'posts_per_page' => count($ids),
				'post_status'    => 'publish',
			]);

			return $groups;
		},
	]);

	register_graphql_field('Group', 'people', [
		'type'        => ['list_of' => 'Person'],
		'description' => 'People that belong to this group.',
		'resolve'     => function ($group_post, $args, $context, $info) {

			$group_id = (int) $group_post->ID;

			// 1) Find matching PERSON IDs by meta (serialized array match).
			$q = new WP_Query([
				'post_type'      => 'person',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'no_found_rows'  => true,
				'fields'         => 'ids',
				'meta_query'     => [
					[
						'key'     => 'cs_group_ids',
						'value'   => 'i:' . $group_id . ';',
						'compare' => 'LIKE',
					],
				],
			]);

			$ids = array_values(array_unique(array_map('intval', $q->posts)));

			if (empty($ids)) {
				return [];
			}

			// 2) Re-fetch as PERSON posts explicitly and in a stable order.
			$posts = get_posts([
				'post_type'      => 'person',
				'post_status'    => 'publish',
				'post__in'       => $ids,
				'orderby'        => 'post__in',
				'posts_per_page' => count($ids),
			]);

			// 3) Hydrate through WPGraphQL so id/databaseId never come back null.
			$people = array_map(
				fn($p) => \WPGraphQL\Data\DataSource::resolve_post_object((int) $p->ID, $context),
				$posts
			);

			return array_values(array_filter($people));
		},
	]);

	/**
	 * -----------------------------
	 * 2) Custom Root Query: featuredResources(first)
	 * -----------------------------
	 */
	register_graphql_field('RootQuery', 'featuredResources', [
		'type' => 'RootQueryToResourceConnection',
		'description' => 'Curated list of featured resources.',
		'args'        => [
			'first' => [
				'type'        => 'Int',
				'description' => 'Number of resources to return (1-20).',
			],
		],
		'resolve'     => function ($root, $args, $context, $info) {

			$first = isset($args['first']) ? (int) $args['first'] : 5;
			$first = max(1, min(20, $first));

			// IMPORTANT: pass first into resolver args so it paginates.
			$args['first'] = $first;

			$resolver = new \WPGraphQL\Data\Connection\PostObjectConnectionResolver(
				$root,
				$args,
				$context,
				$info,
				'resource'
			);

			$resolver->set_query_arg('post_type', 'resource');
			$resolver->set_query_arg('post_status', 'publish');
			$resolver->set_query_arg('orderby', 'date');
			$resolver->set_query_arg('order', 'DESC');
			$resolver->set_query_arg('no_found_rows', true);
			$resolver->set_query_arg('meta_query', [
				[
					'key'     => 'cs_featured',
					'value'   => '1',
					'compare' => '=',
				],
			]);

			return $resolver->get_connection();
		},
	]);

	/**
	 * -----------------------------
	 * 3) Union type + search query (fragment-friendly)
	 * -----------------------------
	 */
	register_graphql_union_type('SearchResultUnion', [
		'typeNames'   => ['Resource', 'Person', 'Collection'],
		'description' => 'Search results across Resources, People, and Collections.',
		'resolveType' => function ($value) {
			if ($value instanceof WP_Post) {
				switch ($value->post_type) {
					case 'resource':
						return 'Resource';
					case 'person':
						return 'Person';
					case 'collection':
						return 'Collection';
				}
			}
			return null;
		},
	]);

	register_graphql_field('RootQuery', 'contentSearch', [
		'type'        => ['list_of' => 'SearchResultUnion'],
		'description' => 'Search Resources, People, and Collections by keyword.',
		'args'        => [
			'term' => [
				'type'        => 'String',
				'description' => 'Search term',
			],
			'first' => [
				'type'         => 'Int',
				'description'  => 'Max number of results (1-25).',
				'defaultValue' => 10,
			],
		],
		'resolve'     => function ($root, $args) {
			$term  = isset($args['term']) ? sanitize_text_field($args['term']) : '';
			$first = isset($args['first']) ? (int) $args['first'] : 10;

			$first = max(1, min(25, $first));

			if ($term === '') {
				return [];
			}

			$q = new WP_Query([
				'post_type'      => ['resource', 'person', 'collection'],
				'post_status'    => 'publish',
				's'              => $term,
				'posts_per_page' => $first,
				'no_found_rows'  => true,
			]);

			return $q->posts;
		},
	]);

	/**
	 * Mutation: addPersonToGroup
	 * Adds an existing Person to an existing Group (stores group IDs on the Person meta).
	 */
	register_graphql_mutation('addPersonToGroup', [
		'inputFields' => [
			'personId' => [
				'type'        => 'Int',
				'description' => 'Database ID of the person.',
			],
			'groupId' => [
				'type'        => 'Int',
				'description' => 'Database ID of the group.',
			],
		],
		'outputFields' => [
			'person' => [
				'type'        => 'Person',
				'description' => 'The updated person.',
				'resolve'     => function ($payload, $args, $context, $info) {
					if (empty($payload['personId'])) {
						return null;
					}

					// Let WPGraphQL hydrate the post properly.
					return \WPGraphQL\Data\DataSource::resolve_post_object(
						(int) $payload['personId'],
						$context
					);
				},
			],
			'added' => [
				'type'        => 'Boolean',
				'description' => 'True if the group was newly added; false if it was already present.',
				'resolve'     => fn($payload) => (bool) ($payload['added'] ?? false),
			],
			'groupIds' => [
				'type'        => ['list_of' => 'Int'],
				'description' => 'Updated list of group IDs stored on the person.',
				'resolve'     => fn($payload) => $payload['groupIds'] ?? [],
			],
		],
		'mutateAndGetPayload' => function ($input) {

			if (! is_user_logged_in()) {
				throw new \GraphQL\Error\UserError('Not authorized.');
			}

			$person_id = isset($input['personId']) ? (int) $input['personId'] : 0;
			$group_id  = isset($input['groupId']) ? (int) $input['groupId'] : 0;

			$person = get_post($person_id);
			if (! $person || $person->post_type !== 'person') {
				throw new \GraphQL\Error\UserError('Invalid personId (must be a Person post).');
			}

			$group = get_post($group_id);
			if (! $group || $group->post_type !== 'group') {
				throw new \GraphQL\Error\UserError('Invalid groupId (must be a Group post).');
			}

			if ($person_id <= 0 || $group_id <= 0) {
				throw new \GraphQL\Error\UserError('personId and groupId are required.');
			}

			$person = get_post($person_id);
			if (! $person || $person->post_type !== 'person') {
				throw new \GraphQL\Error\UserError('Invalid personId.');
			}

			$group = get_post($group_id);
			if (! $group || $group->post_type !== 'group') {
				throw new \GraphQL\Error\UserError('Invalid groupId.');
			}

			if (! current_user_can('edit_post', $person_id)) {
				throw new \GraphQL\Error\UserError('Not authorized to modify this person.');
			}

			$existing = get_post_meta($person_id, 'cs_group_ids', true);
			$existing = is_array($existing) ? array_map('intval', $existing) : [];

			$added = ! in_array($group_id, $existing, true);
			if ($added) {
				$existing[] = $group_id;
			}

			$existing = array_values(array_unique(array_filter(array_map('intval', $existing))));
			sort($existing);

			update_post_meta($person_id, 'cs_group_ids', $existing);

			return [
				'personId' => $person_id,
				'added'    => $added,
				'groupIds' => $existing,
			];
		},
	]);
});
