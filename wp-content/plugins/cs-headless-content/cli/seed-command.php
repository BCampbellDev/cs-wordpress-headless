<?php

if ( ! defined( 'WP_CLI' ) ) {
	return;
}

WP_CLI::add_command( 'cs seed', function ( $args ) {

	if ( empty( $args[0] ) ) {
		WP_CLI::error( 'Usage: wp cs seed <resource|collection|person>' );
	}

	$type = $args[0];

	switch ( $type ) {

		case 'resource':
			seed_resources();
			break;

		case 'collection':
			seed_collections();
			break;

		case 'person':
			seed_people();
			break;

		default:
			WP_CLI::error( "Unknown seed type: {$type}" );
	}

	WP_CLI::success( ucfirst( $type ) . ' seeding complete.' );
} );

function seed_resources() {

	$titles = [
		'Getting Started with Headless WordPress',
		'Using the WordPress REST API',
		'Next.js + WordPress Architecture',
	];

	foreach ( $titles as $title ) {

		if ( post_exists( $title ) ) {
			WP_CLI::log( "Skipping existing resource: {$title}" );
			continue;
		}

		$post_id = wp_insert_post( [
			'post_type'    => 'resource',
			'post_title'   => $title,
			'post_status'  => 'publish',
			'post_content' => 'Seeded content for headless WordPress case study.',
		] );

		if ( is_wp_error( $post_id ) ) {
			WP_CLI::warning( "Failed to create resource: {$title}" );
			continue;
		}

		update_post_meta( $post_id, 'difficulty', 'beginner' );
		update_post_meta( $post_id, 'resource_url', 'https://example.com' );

		WP_CLI::log( "Created resource: {$title}" );
	}
}

function seed_collections() {

	$titles = [
		'Starter Resources',
		'Advanced Patterns',
	];

	foreach ( $titles as $title ) {

		if ( post_exists( $title ) ) {
			WP_CLI::log( "Skipping existing collection: {$title}" );
			continue;
		}

		wp_insert_post( [
			'post_type'   => 'collection',
			'post_title'  => $title,
			'post_status' => 'publish',
		] );

		WP_CLI::log( "Created collection: {$title}" );
	}
}

function seed_people() {

	$people = [
		'Alice Johnson' => 'Author',
		'Bob Smith'     => 'Contributor',
	];

	foreach ( $people as $name => $role ) {

		if ( post_exists( $name ) ) {
			WP_CLI::log( "Skipping existing person: {$name}" );
			continue;
		}

		$post_id = wp_insert_post( [
			'post_type'   => 'person',
			'post_title'  => $name,
			'post_status' => 'publish',
		] );

		update_post_meta( $post_id, 'role', $role );

		WP_CLI::log( "Created person: {$name}" );
	}
}
