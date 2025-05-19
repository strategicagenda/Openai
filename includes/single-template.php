<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load single-grantee.php template from our plugin.
 *
 * This function is hooked into the 'single_template' filter.
 * It checks if the current query is for a single 'grantee' post type.
 * If so, it attempts to load 'single-grantee.php' from the plugin's 'templates' directory.
 *
 * @param string $single_template The current single template path.
 * @return string The modified single template path if our conditions are met.
 */
function grantee_listing_load_single_template( $single_template ) {
    global $post;

    // Check if this is a single post and the post type is 'grantee'
    if ( is_singular( 'grantee' ) ) {
        // Look for template override in the active theme's root directory
        $theme_template_direct = get_stylesheet_directory() . '/single-grantee.php';
         if ( file_exists( $theme_template_direct ) ) {
             // If theme has single-grantee.php, use it
             return $theme_template_direct;
         }

        // Look for template override in a 'grantee-listing' subfolder within the active theme
        $theme_template_subdir = get_stylesheet_directory() . '/grantee-listing/single-grantee.php';
        if ( file_exists( $theme_template_subdir ) ) {
            // If theme has single-grantee.php in a subdirectory, use it
            return $theme_template_subdir;
        }

        // If no theme override found, use the plugin's template
        $plugin_template = GRANTEE_LISTING_PATH . 'templates/single-grantee.php';
        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }
    }

    // Fallback: If not a single 'grantee' post or template not found, return the original template
    return $single_template;
}
add_filter( 'single_template', 'grantee_listing_load_single_template' );