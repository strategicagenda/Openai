<?php
/**
 * Plugin Name:       Grantee Listing
 * Plugin URI:        https://www.strategicagenda.com
 * Description:       Manages and displays a list of grantees with details, filters, card layout, custom single page template, and CSV importer.
 * Version:           1.6.0
 * Author:            Strategic Agenda
 * Author URI:        https://www.strategicagenda.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       grantee-listing
 * Domain Path:       /languages
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define constants
define( 'GRANTEE_LISTING_VERSION', '1.6.0' ); // Updated version
define( 'GRANTEE_LISTING_PATH', plugin_dir_path( __FILE__ ) );
define( 'GRANTEE_LISTING_URL', plugin_dir_url( __FILE__ ) );

// Include core files
require_once GRANTEE_LISTING_PATH . 'includes/post-types.php';
require_once GRANTEE_LISTING_PATH . 'includes/meta-boxes.php';
require_once GRANTEE_LISTING_PATH . 'includes/shortcode.php';
require_once GRANTEE_LISTING_PATH . 'includes/enqueue.php';
require_once GRANTEE_LISTING_PATH . 'includes/single-template.php';
require_once GRANTEE_LISTING_PATH . 'includes/importer.php'; // Added for CSV Importer

/**
 * Flush rewrite rules on activation/deactivation.
 * Necessary when registering custom post types or taxonomies.
 */
function grantee_listing_activate() {
	// Ensure CPT and Taxonomies are registered before flushing
    grantee_listing_register_post_type();
    grantee_listing_register_taxonomies();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'grantee_listing_activate' );

function grantee_listing_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'grantee_listing_deactivate' );

?>