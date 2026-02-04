<?php

/**
 * Plugin Name:       CS Headless GraphQL
 * Description:       WPGraphQL schema extensions for the CS Headless case study.
 * Version:           0.1.0
 *
 * Requires Plugins:  wp-graphql, cs-headless-content
 */

add_action('init', function () {
	$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '';
	$is_graphql = ($path === '/graphql' || str_ends_with($path, '/graphql'));

	if ($is_graphql) {
		$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null);
		error_log('GRAPHQL PATH HIT. AUTH HEADER? ' . ($auth ? 'yes' : 'no'));
		if ($auth) error_log('AUTH PREFIX: ' . substr($auth, 0, 10)); // "Basic ...."
	}
}, 0);

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

function cs_graphql_require_editor_capability()
{
	// If already authenticated via cookie/session:
	if (is_user_logged_in() && current_user_can('edit_posts')) {
		return;
	}

	// If using Basic Auth / Application Passwords, WordPress should set the current user.
	$user = wp_get_current_user();
	if ($user && $user->ID && user_can($user, 'edit_posts')) {
		return;
	}

	throw new \GraphQL\Error\UserError('Not authorized.');
}

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

	// --- Expose NPS Alert meta fields explicitly on the NpsAlert GraphQL type ---
	register_graphql_field('NpsAlert', 'npsId', [
		'type' => 'String',
		'resolve' => fn($post) => get_post_meta($post->ID, 'nps_id', true),
	]);

	register_graphql_field('NpsAlert', 'npsParkCode', [
		'type' => 'String',
		'resolve' => fn($post) => get_post_meta($post->ID, 'nps_park_code', true),
	]);

	register_graphql_field('NpsAlert', 'npsCategory', [
		'type' => 'String',
		'resolve' => fn($post) => get_post_meta($post->ID, 'nps_category', true),
	]);

	register_graphql_field('NpsAlert', 'npsUrl', [
		'type' => 'String',
		'resolve' => fn($post) => get_post_meta($post->ID, 'nps_url', true),
	]);

	register_graphql_field('NpsAlert', 'npsLastIndexedDate', [
		'type' => 'String',
		'resolve' => fn($post) => get_post_meta($post->ID, 'nps_last_indexed_date', true),
	]);

	register_graphql_field('NpsAlert', 'npsRelatedRoadEventsJson', [
		'type' => 'String',
		'resolve' => fn($post) => get_post_meta($post->ID, 'nps_related_road_events_json', true),
	]);

	register_graphql_field('NpsAlert', 'editorLock', [
		'type' => 'Boolean',
		'resolve' => fn($post) => (bool) get_post_meta($post->ID, 'editor_lock', true),
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

			// $user = wp_get_current_user();
			// error_log('GRAPHQL USER: ' . ($user && $user->ID ? $user->user_login : 'none'));

			// if (! is_user_logged_in()) {
			// 	throw new \GraphQL\Error\UserError('Not authorized.');
			// }

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

	register_graphql_input_type('UpsertNpsAlertInput', [
		'description' => 'Input for upserting an NPS Alert.',
		'fields' => [
			'npsId' => ['type' => ['non_null' => 'String']],
			'npsParkCode' => ['type' => 'String'],
			'npsCategory' => ['type' => 'String'],
			'npsUrl' => ['type' => 'String'],
			'npsLastIndexedDate' => ['type' => 'String'],
			'npsRelatedRoadEventsJson' => ['type' => 'String'],
			'description'  => ['type' => 'String'],

			// Editorial-facing defaults (only applied if not editor-locked)
			'title' => ['type' => 'String'],
			'content' => ['type' => 'String'],
			'excerpt' => ['type' => 'String'],

			// Optional: choose publish vs draft on create
			'status' => ['type' => 'String'], // 'publish' | 'draft'
		],
	]);

	register_graphql_mutation('upsertNpsAlert', [
		'inputFields' => [
			'input' => ['type' => ['non_null' => 'UpsertNpsAlertInput']],
		],
		'outputFields' => [
			'created' => [
				'type' => 'Boolean',
			],
			'alertId' => [
				'type' => 'Int',
			],
			'alert' => [
				'type' => 'NpsAlert',
				'resolve' => function ($payload, $args, $context) {
					if (empty($payload['alertId'])) return null;

					$post = get_post((int) $payload['alertId']);
					if (! $post) return null;

					// Wrap so WPGraphQL knows how to resolve fields like databaseId
					return new \WPGraphQL\Model\Post($post);
				},
			],
		],
		'mutateAndGetPayload' => function ($input, $context, $info) {
			cs_graphql_require_editor_capability();

			$nps_id = isset($input['npsId']) ? sanitize_text_field($input['npsId']) : '';
			if ($nps_id === '') {
				throw new \GraphQL\Error\UserError('npsId is required.');
			}

			// 1) Find existing by meta nps_id
			$existing = get_posts([
				'post_type'  => 'nps_alert',
				'post_status' => 'any',
				'meta_key'   => 'nps_id',
				'meta_value' => $nps_id,
				'fields'     => 'ids',
				'numberposts' => 1,
			]);

			$created = false;
			$post_id = !empty($existing) ? (int)$existing[0] : 0;

			// 2) Create if missing
			if (!$post_id) {
				$status = isset($input['status']) ? sanitize_key($input['status']) : 'draft';
				if (!in_array($status, ['draft', 'publish'], true)) {
					$status = 'draft';
				}

				$post_id = wp_insert_post([
					'post_type'   => 'nps_alert',
					'post_status' => $status,
					'post_title'  => isset($input['title']) ? sanitize_text_field($input['title']) : 'NPS Alert',
					'post_content' => isset($input['content']) ? wp_kses_post($input['content']) : '',
					'post_excerpt' => isset($input['excerpt']) ? sanitize_text_field($input['excerpt']) : '',
				], true);

				if (is_wp_error($post_id)) {
					throw new \GraphQL\Error\UserError($post_id->get_error_message());
				}

				$created = true;
			}

			// 3) Always update machine meta
			update_post_meta($post_id, 'nps_id', $nps_id);

			if (isset($input['npsParkCode'])) {
				update_post_meta($post_id, 'nps_park_code', sanitize_key($input['npsParkCode']));
			}
			if (isset($input['npsCategory'])) {
				update_post_meta($post_id, 'nps_category', sanitize_text_field($input['npsCategory']));
			}
			if (isset($input['npsUrl'])) {
				update_post_meta($post_id, 'nps_url', esc_url_raw($input['npsUrl']));
			}
			if (isset($input['npsLastIndexedDate'])) {
				update_post_meta($post_id, 'nps_last_indexed_date', sanitize_text_field($input['npsLastIndexedDate']));
			}
			if (isset($input['npsRelatedRoadEventsJson'])) {
				update_post_meta($post_id, 'nps_related_road_events_json', is_string($input['npsRelatedRoadEventsJson']) ? $input['npsRelatedRoadEventsJson'] : wp_json_encode($input['npsRelatedRoadEventsJson']));
			}

			// 4) Only update title/content/excerpt if not editor-locked OR newly created
			$locked = (bool)get_post_meta($post_id, 'editor_lock', true);

			if ($created || !$locked) {
				$update = [
					'ID' => $post_id,
				];

				if (isset($input['title'])) {
					$update['post_title'] = sanitize_text_field($input['title']);
				}
				if (isset($input['content'])) {
					$update['post_content'] = wp_kses_post($input['content']);
				}
				if (isset($input['excerpt'])) {
					$update['post_excerpt'] = sanitize_text_field($input['excerpt']);
				}

				// Only call wp_update_post if we actually have something to update
				if (count($update) > 1) {
					$r = wp_update_post($update, true);
					if (is_wp_error($r)) {
						throw new \GraphQL\Error\UserError($r->get_error_message());
					}
				}
			}

			return [
				'created' => $created,
				'alertId' => (int)$post_id,
			];
		},
	]);
});
