<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Grantee Custom Post Type.
 */
function grantee_listing_register_post_type() {
	$labels = array(
		'name'                  => _x( 'Grantees', 'Post Type General Name', 'grantee-listing' ),
		'singular_name'         => _x( 'Grantee', 'Post Type Singular Name', 'grantee-listing' ),
		'menu_name'             => __( 'Grantees', 'grantee-listing' ),
		'name_admin_bar'        => __( 'Grantee', 'grantee-listing' ),
		'archives'              => __( 'Grantee Archives', 'grantee-listing' ),
		'attributes'            => __( 'Grantee Attributes', 'grantee-listing' ),
		'parent_item_colon'     => __( 'Parent Grantee:', 'grantee-listing' ),
		'all_items'             => __( 'All Grantees', 'grantee-listing' ),
		'add_new_item'          => __( 'Add New Grantee', 'grantee-listing' ),
		'add_new'               => __( 'Add New', 'grantee-listing' ),
		'new_item'              => __( 'New Grantee', 'grantee-listing' ),
		'edit_item'             => __( 'Edit Grantee', 'grantee-listing' ),
		'update_item'           => __( 'Update Grantee', 'grantee-listing' ),
		'view_item'             => __( 'View Grantee', 'grantee-listing' ),
		'view_items'            => __( 'View Grantees', 'grantee-listing' ),
		'search_items'          => __( 'Search Grantee', 'grantee-listing' ),
		'not_found'             => __( 'Not found', 'grantee-listing' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'grantee-listing' ),
		'featured_image'        => __( 'Grantee Logo', 'grantee-listing' ),
		'set_featured_image'    => __( 'Set grantee logo', 'grantee-listing' ),
		'remove_featured_image' => __( 'Remove grantee logo', 'grantee-listing' ),
		'use_featured_image'    => __( 'Use as grantee logo', 'grantee-listing' ),
		'insert_into_item'      => __( 'Insert into grantee', 'grantee-listing' ),
		'uploaded_to_this_item' => __( 'Uploaded to this grantee', 'grantee-listing' ),
		'items_list'            => __( 'Grantees list', 'grantee-listing' ),
		'items_list_navigation' => __( 'Grantees list navigation', 'grantee-listing' ),
		'filter_items_list'     => __( 'Filter grantees list', 'grantee-listing' ),
	);
	$args = array(
		'label'                 => __( 'Grantee', 'grantee-listing' ),
		'description'           => __( 'Information about grantees', 'grantee-listing' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor', 'revisions', 'thumbnail' ), // 'editor' for description, 'thumbnail' for logo as featured image if preferred
		'taxonomies'            => array( 'grantee_type', 'grantee_country' ),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 20,
		'menu_icon'             => 'dashicons-awards',
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => 'grantees', // Enables archive page at /grantees/
		'exclude_from_search'   => false,
		'publicly_queryable'    => true,
		'capability_type'       => 'post',
        'rewrite'               => array('slug' => 'grantees', 'with_front' => false), // Customize the URL slug
	);
	register_post_type( 'grantee', $args );
}
add_action( 'init', 'grantee_listing_register_post_type', 0 );

/**
 * Register Custom Taxonomies.
 */
function grantee_listing_register_taxonomies() {
	// Grantee Type (e.g., Non-profit, Individual, Research) - Hierarchical
	$type_labels = array(
		'name'              => _x( 'Grantee Types', 'taxonomy general name', 'grantee-listing' ),
		'singular_name'     => _x( 'Grantee Type', 'taxonomy singular name', 'grantee-listing' ),
		'search_items'      => __( 'Search Types', 'grantee-listing' ),
		'all_items'         => __( 'All Types', 'grantee-listing' ),
		'parent_item'       => __( 'Parent Type', 'grantee-listing' ),
		'parent_item_colon' => __( 'Parent Type:', 'grantee-listing' ),
		'edit_item'         => __( 'Edit Type', 'grantee-listing' ),
		'update_item'       => __( 'Update Type', 'grantee-listing' ),
		'add_new_item'      => __( 'Add New Type', 'grantee-listing' ),
		'new_item_name'     => __( 'New Type Name', 'grantee-listing' ),
		'menu_name'         => __( 'Grantee Types', 'grantee-listing' ),
	);
	$type_args = array(
		'hierarchical'      => true,
		'labels'            => $type_labels,
		'show_ui'           => true,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => 'grantee-type', 'with_front' => false ),
	);
	register_taxonomy( 'grantee_type', array( 'grantee' ), $type_args );

	// Countries Involved In (Non-Hierarchical, like tags)
	$country_labels = array(
		'name'                       => _x( 'Countries Involved', 'taxonomy general name', 'grantee-listing' ),
		'singular_name'              => _x( 'Country', 'taxonomy singular name', 'grantee-listing' ),
		'search_items'               => __( 'Search Countries', 'grantee-listing' ),
		'popular_items'              => __( 'Popular Countries', 'grantee-listing' ),
		'all_items'                  => __( 'All Countries', 'grantee-listing' ),
		'parent_item'                => null, // No parent for non-hierarchical
		'parent_item_colon'          => null,
		'edit_item'                  => __( 'Edit Country', 'grantee-listing' ),
		'update_item'                => __( 'Update Country', 'grantee-listing' ),
		'add_new_item'               => __( 'Add New Country', 'grantee-listing' ),
		'new_item_name'              => __( 'New Country Name', 'grantee-listing' ),
        'separate_items_with_commas' => __( 'Separate countries with commas', 'grantee-listing' ),
        'add_or_remove_items'        => __( 'Add or remove countries', 'grantee-listing' ),
        'choose_from_most_used'      => __( 'Choose from the most used countries', 'grantee-listing' ),
        'not_found'                  => __( 'No countries found.', 'grantee-listing' ),
		'menu_name'                  => __( 'Countries', 'grantee-listing' ),
	);
	$country_args = array(
		'hierarchical'          => false,
		'labels'                => $country_labels,
		'show_ui'               => true,
		'show_admin_column'     => true,
		'query_var'             => true,
		'rewrite'               => array( 'slug' => 'grantee-country', 'with_front' => false ),
	);
	register_taxonomy( 'grantee_country', array( 'grantee' ), $country_args );
}
add_action( 'init', 'grantee_listing_register_taxonomies', 0 );