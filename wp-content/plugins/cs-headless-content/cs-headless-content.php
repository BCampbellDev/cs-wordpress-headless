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
		'graphql_single_name' => 'People',
		'graphql_plural_name' => 'Peoples',
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
	 * Optional taxonomy for Resource only (nice for headless filtering).
	 */
	register_taxonomy('resource_type', ['resource'], [
		'label'         => 'Resource Types',
		'public'        => true,
		'hierarchical'  => false, // tag-like
		'show_in_rest'  => true,
		'show_in_graphql' => true,
		'rewrite'       => ['slug' => 'resource-types'],
	]);
}, 0);

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
