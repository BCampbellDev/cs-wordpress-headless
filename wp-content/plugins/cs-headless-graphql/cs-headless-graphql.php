<?php

/**
 * Plugin Name:     CS Headless GraphQL
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     PLUGIN DESCRIPTION HERE
 * Author:          YOUR NAME HERE
 * Author URI:      YOUR SITE HERE
 * Text Domain:     cs-headless-graphql
 * Domain Path:     /languages
 * Version:         0.1.0
 * Requires Plugins: wp-graphql, cs-headless-content
 *
 * @package         Cs_Headless_Graphql
 */

// Your code starts here.
if (! defined('ABSPATH')) {
	exit;
}

// Only load if WPGraphQL is active.
if (! class_exists('\WPGraphQL')) {
	return;
}

// Optional: ensure content plugin is active / CPTs exist.
// @todo this is hacky - there's gotta be a better way to dynamically work with the cpts
if (! post_type_exists('resource')) {
	return;
}

add_action('graphql_register_types', function () {
	// GraphQL extensions go here:
	// - custom fields
	// - unions
	// - queries
	// - mutations
});
