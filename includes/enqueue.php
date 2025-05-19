<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue scripts and styles for the admin area.
 */
function grantee_listing_admin_enqueue_scripts( $hook_suffix ) {
	global $post_type;
	if ( ( $hook_suffix == 'post.php' || $hook_suffix == 'post-new.php' ) && $post_type == 'grantee' ) {
        wp_enqueue_media();
		wp_enqueue_script(
			'grantee-admin-js',
			GRANTEE_LISTING_URL . 'assets/js/grantee-admin.js',
			array( 'jquery' ),
			GRANTEE_LISTING_VERSION,
			true
		);
	}
}
add_action( 'admin_enqueue_scripts', 'grantee_listing_admin_enqueue_scripts' );

/**
 * Enqueue scripts and styles for the frontend.
 */
function grantee_listing_frontend_enqueue_scripts() {
    // Enqueue for shortcode OR if it's a single grantee page OR grantee archive
    if ( is_singular('grantee') || has_shortcode( get_post_field('post_content', get_the_ID()), 'grantee_list' ) || is_post_type_archive('grantee') || is_tax('grantee_type') || is_tax('grantee_country') ) {
        wp_enqueue_style(
            'grantee-style',
            GRANTEE_LISTING_URL . 'assets/css/grantee-style.css',
            array(),
            GRANTEE_LISTING_VERSION
        );
    }
}
add_action( 'wp_enqueue_scripts', 'grantee_listing_frontend_enqueue_scripts' );