<?php

/**
 * Plugin Name:     CS Headless Content
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     Case study content models (CPTs + taxonomies) for a headless WordPress build.
 * Author:          YOUR NAME HERE
 * Author URI:      YOUR SITE HERE
 * Text Domain:     cs-headless-content
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Cs_Headless_Content
 */


if (! defined('ABSPATH')) {
	exit;
}

if (defined('WP_CLI') && WP_CLI) {
	require_once __DIR__ . '/cli/seed-command.php';
}

add_action('init', function () {

	/**
	 * Shared taxonomy: Topic
	 * Used by Resources + Collections (for filtering on the frontend).
	 */
	register_taxonomy('topic', ['resource', 'collection'], [
		'label'         => 'Topics',
		'public'        => true,
		'hierarchical'  => true,
		'show_in_rest'  => true,
		'show_in_graphql' => true,
		'graphql_single_name' => 'Topic',
		'graphql_plural_name' => 'Topics',
		'rewrite'       => ['slug' => 'topics'],
	]);

	/**
	 * CPT #1: Resource
	 * Think: articles, tools, tutorials, links.
	 */
	register_post_type('resource', [
		'label'               => 'Resources',
		'public'              => true,
		'show_in_rest'        => true,
		'show_in_graphql' => true,
		'graphql_single_name' => 'Resource',
		'graphql_plural_name' => 'Resources',
		'rest_base'           => 'resources',
		'menu_icon'           => 'dashicons-media-document',
		'supports'            => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions'],
		'has_archive'         => true,
		'rewrite'             => ['slug' => 'resources'],
		'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields'],
	]);

	// Resource: external URL
	register_post_meta('resource', 'resource_url', [
		'type'              => 'string',
		'single'            => true,
		'show_in_rest'      => true,
		'show_in_graphql' => true,
		'sanitize_callback' => 'esc_url_raw',
	]);

	// Resource: difficulty enum
	register_post_meta('resource', 'difficulty', [
		'type'         => 'string',
		'single'       => true,
		'show_in_rest' => true,
		'show_in_graphql' => true,
	]);

	register_post_meta('resource', 'cs_featured', [
		'type'              => 'boolean',
		'single'            => true,
		'default'           => false,
		'show_in_rest'      => true,
		'auth_callback'     => function () {
			return current_user_can('edit_posts');
		},
		'sanitize_callback' => function ($value) {
			return (bool) $value;
		},
	]);

	/**
	 * CPT #2: Collection
	 * Think: curated sets of resources (e.g., “Starter Pack”, “Advanced”).
	 */
	register_post_type('collection', [
		'label'               => 'Collections',
		'public'              => true,
		'show_in_rest'        => true,
		'show_in_graphql' => true,
		'graphql_single_name' => 'Collection',
		'graphql_plural_name' => 'Collections',
		'rest_base'           => 'collections',
		'menu_icon'           => 'dashicons-portfolio',
		'supports'            => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions'],
		'has_archive'         => true,
		'rewrite'             => ['slug' => 'collections'],
	]);

	/**
	 * CPT #3: Person
	 * Think: authors/speakers/contributors (separate from WP Users).
	 */
	register_post_type('person', [
		'label'               => 'People',
		'public'              => true,
		'show_in_rest'        => true,
		'show_in_graphql' => true,
		'graphql_single_name' => 'Person',
		'graphql_plural_name' => 'People',
		'rest_base'           => 'people',
		'menu_icon'           => 'dashicons-groups',
		'supports'            => ['title', 'editor', 'thumbnail', 'revisions'],
		'has_archive'         => true,
		'rewrite'             => ['slug' => 'people'],
	]);

	// Person: role
	register_post_meta('person', 'role', [
		'type'         => 'string',
		'single'       => true,
		'show_in_rest' => true,
		'show_in_graphql' => true,
	]);

	/**
	 * CPT: Group
	 * Think: a named bucket that People can belong to (e.g. “Founders”, “Editors”, “Contributors”).
	 */
	register_post_type('group', [
		'label'               => 'Groups',
		'public'              => true,
		'show_in_rest'        => true,
		'rest_base'           => 'groups',
		'menu_icon'           => 'dashicons-groups',
		'supports'            => ['title', 'editor', 'revisions'],
		'has_archive'         => true,
		'rewrite'             => ['slug' => 'groups'],

		// GraphQL
		'show_in_graphql'     => true,
		'graphql_single_name' => 'Group',
		'graphql_plural_name' => 'Groups',
	]);

	/**
	 * Optional taxonomy for Resource only (nice for headless filtering).
	 */
	register_taxonomy('resource_type', ['resource'], [
		'label'         => 'Resource Types',
		'public'        => true,
		'hierarchical'  => false, // tag-like
		'show_in_rest'  => true,
		'show_in_graphql' => true,
		'graphql_single_name' => 'ResourceType',
		'graphql_plural_name' => 'ResourceTypes',
		'rewrite'       => ['slug' => 'resource-types'],
	]);

	/**
	 * CPT: NPS Alert
	 * Ingested from NPS Alerts API, but editable by humans.
	 * Editors can optionally lock title/content/excerpt from being overwritten by the sync job.
	 */
	register_post_type('nps_alert', [
		'label'               => 'NPS Alerts',
		'public'              => true,
		'show_in_rest'        => true,
		'rest_base'           => 'nps-alerts',
		'menu_icon'           => 'dashicons-warning',
		'supports'            => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions'],
		'has_archive'         => true,
		'rewrite'             => ['slug' => 'nps-alerts'],

		// GraphQL
		'show_in_graphql'     => true,
		'graphql_single_name' => 'NpsAlert',
		'graphql_plural_name' => 'NpsAlerts',
	]);

	// NPS: external UUID id (canonical upsert key)
	register_post_meta('nps_alert', 'nps_id', [
		'type'              => 'string',
		'single'            => true,
		'show_in_rest'      => true,
		'show_in_graphql'   => true,
		'sanitize_callback' => 'sanitize_text_field',
	]);

	// NPS: parkCode (e.g. "cane")
	register_post_meta('nps_alert', 'nps_park_code', [
		'type'              => 'string',
		'single'            => true,
		'show_in_rest'      => true,
		'show_in_graphql'   => true,
		'sanitize_callback' => 'sanitize_key',
	]);

	// NPS: category (e.g. "Park Closure")
	register_post_meta('nps_alert', 'nps_category', [
		'type'              => 'string',
		'single'            => true,
		'show_in_rest'      => true,
		'show_in_graphql'   => true,
		'sanitize_callback' => 'sanitize_text_field',
	]);

	// NPS: source url
	register_post_meta('nps_alert', 'nps_url', [
		'type'              => 'string',
		'single'            => true,
		'show_in_rest'      => true,
		'show_in_graphql'   => true,
		'sanitize_callback' => 'esc_url_raw',
	]);

	// NPS: lastIndexedDate as a string (you can convert to datetime later in Laravel)
	register_post_meta('nps_alert', 'nps_last_indexed_date', [
		'type'              => 'string',
		'single'            => true,
		'show_in_rest'      => true,
		'show_in_graphql'   => true,
		'sanitize_callback' => 'sanitize_text_field',
	]);

	// NPS: relatedRoadEvents raw JSON (optional)
	register_post_meta('nps_alert', 'nps_related_road_events_json', [
		'type'              => 'string',
		'single'            => true,
		'show_in_rest'      => true,
		'show_in_graphql'   => true,
		'sanitize_callback' => function ($value) {
			// store as string; Node can send JSON.stringify(...)
			return is_string($value) ? $value : wp_json_encode($value);
		},
	]);

	// Editorial lock: if true, your sync job should not overwrite title/content/excerpt
	register_post_meta('nps_alert', 'editor_lock', [
		'type'              => 'boolean',
		'single'            => true,
		'default'           => false,
		'show_in_rest'      => true,
		'show_in_graphql'   => true,
		'auth_callback'     => function () {
			return current_user_can('edit_posts');
		},
		'sanitize_callback' => function ($value) {
			return (bool) $value;
		},
	]);
}, 0);

add_action('add_meta_boxes', function () {
	add_meta_box(
		'cs_resource_featured',
		'Featured',
		'cs_resource_featured_metabox',
		'resource',
		'side',
		'high'
	);

	// NEW: NPS Alert source + lock
	add_meta_box(
		'cs_nps_alert_source',
		'NPS Source',
		'cs_nps_alert_source_metabox',
		'nps_alert',
		'side',
		'high'
	);
});

function cs_resource_featured_metabox($post)
{
	$value = (bool) get_post_meta($post->ID, 'cs_featured', true);

	wp_nonce_field(
		'cs_resource_featured_save',
		'cs_resource_featured_nonce'
	);
?>
	<label style="display:flex;align-items:center;gap:8px;">
		<input
			type="checkbox"
			name="cs_featured"
			value="1"
			<?php checked($value); ?> />
		Mark this Resource as featured
	</label>
<?php
}

function cs_nps_alert_source_metabox($post)
{
	$editor_lock = (bool) get_post_meta($post->ID, 'editor_lock', true);

	$nps_id   = (string) get_post_meta($post->ID, 'nps_id', true);
	$park     = (string) get_post_meta($post->ID, 'nps_park_code', true);
	$category = (string) get_post_meta($post->ID, 'nps_category', true);
	$url      = (string) get_post_meta($post->ID, 'nps_url', true);
	$indexed  = (string) get_post_meta($post->ID, 'nps_last_indexed_date', true);

	wp_nonce_field(
		'cs_nps_alert_save',
		'cs_nps_alert_nonce'
	);
?>
	<label style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
		<input
			type="checkbox"
			name="editor_lock"
			value="1"
			<?php checked($editor_lock); ?> />
		Lock editor fields (sync won’t overwrite title/content/excerpt)
	</label>

	<div style="font-size:12px;line-height:1.4;">
		<div><strong>NPS ID:</strong> <?php echo esc_html($nps_id ?: '—'); ?></div>
		<div><strong>Park:</strong> <?php echo esc_html($park ?: '—'); ?></div>
		<div><strong>Category:</strong> <?php echo esc_html($category ?: '—'); ?></div>
		<div><strong>Last Indexed:</strong> <?php echo esc_html($indexed ?: '—'); ?></div>
		<div><strong>Source:</strong>
			<?php if ($url) : ?>
				<a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer">Open</a>
			<?php else : ?>
				—
			<?php endif; ?>
		</div>
	</div>
<?php
}

add_action('save_post_resource', function ($post_id) {
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
	if (! isset($_POST['cs_resource_featured_nonce'])) return;
	if (! wp_verify_nonce($_POST['cs_resource_featured_nonce'], 'cs_resource_featured_save')) return;
	if (! current_user_can('edit_post', $post_id)) return;

	$featured = isset($_POST['cs_featured']) ? true : false;

	update_post_meta($post_id, 'cs_featured', $featured);
});

add_action('save_post_nps_alert', function ($post_id) {
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
	if (! isset($_POST['cs_nps_alert_nonce'])) return;
	if (! wp_verify_nonce($_POST['cs_nps_alert_nonce'], 'cs_nps_alert_save')) return;
	if (! current_user_can('edit_post', $post_id)) return;

	$editor_lock = isset($_POST['editor_lock']) ? true : false;
	update_post_meta($post_id, 'editor_lock', $editor_lock);
});

add_action('template_redirect', function () {
	// Allow wp-admin, login, and REST API
	if (is_admin()) return;

	$rest_prefix = rest_get_url_prefix(); // usually 'wp-json'
	$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

	// Allow REST endpoints
	if (is_string($path) && str_starts_with(ltrim($path, '/'), $rest_prefix)) {
		return;
	}

	// Allow the REST route query-style fallback
	if (isset($_GET['rest_route'])) {
		return;
	}

	// Everything else: pretend the frontend doesn't exist
	global $wp_query;
	$wp_query->set_404();
	status_header(404);
	nocache_headers();
	include get_404_template();
	exit;
}, 0);
