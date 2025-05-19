<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Calculate summary statistics for ALL grantees.
 *
 * @return array An associative array with counts and totals.
 */
function grantee_listing_get_total_summary() {
    // Query args for ALL published grantee posts
    $args = array(
        'post_type'      => 'grantee',
        'post_status'    => 'publish',
        'posts_per_page' => -1, // Get all posts
        'fields'         => 'ids', // Only get post IDs for efficiency
    );

    $all_grantees_query = new WP_Query($args);
    $grantee_ids_all = $all_grantees_query->posts; // Get just the array of IDs

    $total_grantees_all = count($grantee_ids_all);
    $total_funding_all = 0;
    $countries_involved_all = array(); // Use term IDs to count unique countries

    if ($total_grantees_all > 0) {
        // Loop through IDs and get meta individually
        foreach ($grantee_ids_all as $post_id) {
            // Sum funding amount
            $funding_amount = get_post_meta($post_id, '_grantee_funding_amount', true);
            if (is_numeric($funding_amount)) {
                $total_funding_all += floatval($funding_amount);
            }

            // Collect country term IDs
            $countries = get_the_terms($post_id, 'grantee_country');
            if ($countries && !is_wp_error($countries)) {
                foreach ($countries as $country_term) {
                    $countries_involved_all[] = $country_term->term_id;
                }
            }
        }
    }

    // Count unique countries by unique term IDs
    $unique_countries_count = count(array_unique($countries_involved_all));

    return array(
        'total_grantees'    => $total_grantees_all,
        'total_funding'     => $total_funding_all,
        'unique_countries'  => $unique_countries_count,
    );
}


/**
 * Display the summary boxes.
 */
function grantee_listing_display_summary_boxes() {
    $summary = grantee_listing_get_total_summary(); // Use the function to get total summary
    $currency_symbol = '$'; // Default currency symbol

    ob_start();
    ?>
    <div class="grantee-summary-boxes">
        <div class="summary-box">
            <span class="summary-value"><?php echo esc_html($summary['total_grantees']); ?></span>
            <span class="summary-label"><?php _e('Total Grantees', 'grantee-listing'); ?></span>
        </div>
        <div class="summary-box">
             <span class="summary-value"><?php echo esc_html($currency_symbol) . number_format_i18n($summary['total_funding'], 0); ?></span>
             <span class="summary-label"><?php _e('Total Funding Amount', 'grantee-listing'); ?></span>
        </div>
         <div class="summary-box">
             <span class="summary-value"><?php echo esc_html($summary['unique_countries']); ?></span>
             <span class="summary-label"><?php _e('Total Countries Involved', 'grantee-listing'); ?></span>
        </div>
    </div>
    <?php
    return ob_get_clean();
}


/**
 * Display the filter form for grantees.
 */
function grantee_listing_display_filters() {
    ob_start();
    $current_page_url = esc_url(get_permalink());

    // Get current filter values from URL parameters
    $current_keyword = isset($_GET['s_keyword']) ? sanitize_text_field($_GET['s_keyword']) : '';
    $current_type = isset($_GET['grantee_type_filter']) ? sanitize_title($_GET['grantee_type_filter']) : '';
    $current_country = isset($_GET['grantee_country_filter']) ? sanitize_title($_GET['grantee_country_filter']) : '';

    ?>
    <form method="get" action="<?php echo $current_page_url; ?>" class="grantee-filters-form">
        <div class="filter-group keyword-filter">
            <label for="s_keyword"><?php _e('Search Keyword:', 'grantee-listing'); ?></label>
            <input type="search" id="s_keyword" name="s_keyword" value="<?php echo esc_attr($current_keyword); ?>" placeholder="<?php _e('Name, Project, Description...', 'grantee-listing'); ?>">
        </div>

        <?php
        // Get Grantee Type terms for the dropdown
        $grantee_types = get_terms(array('taxonomy' => 'grantee_type', 'hide_empty' => true));
        if (!empty($grantee_types) && !is_wp_error($grantee_types)):
        ?>
        <div class="filter-group type-filter">
            <label for="grantee_type_filter"><?php _e('Filter by Type:', 'grantee-listing'); ?></label>
            <select name="grantee_type_filter" id="grantee_type_filter">
                <option value=""><?php _e('All Types', 'grantee-listing'); ?></option>
                <?php foreach ($grantee_types as $type): ?>
                    <option value="<?php echo esc_attr($type->slug); ?>" <?php selected($current_type, $type->slug); ?>>
                        <?php echo esc_html($type->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <?php
        // Get Countries Involved terms for the dropdown
        $grantee_countries = get_terms(array('taxonomy' => 'grantee_country', 'hide_empty' => true));
        if (!empty($grantee_countries) && !is_wp_error($grantee_countries)):
        ?>
        <div class="filter-group country-filter">
            <label for="grantee_country_filter"><?php _e('Filter by Country:', 'grantee-listing'); ?></label>
            <select name="grantee_country_filter" id="grantee_country_filter">
                <option value=""><?php _e('All Countries', 'grantee-listing'); ?></option>
                <?php foreach ($grantee_countries as $country): ?>
                    <option value="<?php echo esc_attr($country->slug); ?>" <?php selected($current_country, $country->slug); ?>>
                        <?php echo esc_html($country->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <div class="filter-group submit-filter">
            <button type="submit"><?php _e('Apply Filters', 'grantee-listing'); ?></button>
            <?php if ($current_keyword || $current_type || $current_country): ?>
                 <a href="<?php echo $current_page_url; ?>" class="clear-filters"><?php _e('Clear Filters', 'grantee-listing'); ?></a>
            <?php endif; ?>
        </div>
    </form>
    <?php
    return ob_get_clean();
}


/**
 * Register the [grantee_list] shortcode.
 */
function grantee_listing_shortcode( $atts ) {
	// Define default attributes and merge with user attributes
	$atts = shortcode_atts(
		array(
			'limit'           => 9, // Number of grantees per page
            'orderby'         => 'date', // How to order the list
            'order'           => 'DESC', // Order direction
            'columns'         => 3, // Default number of columns for card layout
            'currency_symbol' => '$', // Currency symbol for funding amount
		),
		$atts,
		'grantee_list'
	);

    // Sanitize columns attribute - Allow up to 6 columns
    $columns = intval($atts['columns']);
    if ($columns < 1 || $columns > 6) {
        $columns = 3; // Fallback to default if invalid
    }

	// Prepare query arguments
	$paged = (get_query_var('paged')) ? get_query_var('paged') : 1; // Get current page for pagination
	$args = array(
		'post_type'      => 'grantee',
		'post_status'    => 'publish',
		'posts_per_page' => intval( $atts['limit'] ),
        'orderby'        => sanitize_key( $atts['orderby'] ),
        'order'          => $atts['order'] === 'ASC' ? 'ASC' : 'DESC',
        'paged'          => $paged, // Apply pagination

        // --- Filtering Logic (from URL parameters) ---
        's'              => isset( $_GET['s_keyword'] ) ? sanitize_text_field( $_GET['s_keyword'] ) : '', // Keyword search
		'tax_query'      => array( 'relation' => 'AND' ), // Use AND if both type and country are specified
	);

    // Add taxonomy query for Grantee Type if specified in filter form
    if ( isset( $_GET['grantee_type_filter'] ) && ! empty( $_GET['grantee_type_filter'] ) ) {
        $args['tax_query'][] = array(
            'taxonomy' => 'grantee_type',
            'field'    => 'slug',
            'terms'    => sanitize_title( $_GET['grantee_type_filter'] ),
        );
    }

    // Add taxonomy query for Country if specified in filter form
    if ( isset( $_GET['grantee_country_filter'] ) && ! empty( $_GET['grantee_country_filter'] ) ) {
         $args['tax_query'][] = array(
            'taxonomy' => 'grantee_country',
            'field'    => 'slug',
            'terms'    => sanitize_title( $_GET['grantee_country_filter'] ),
        );
    }

    // Clean up empty tax_query if no taxonomy filters are applied
    if ( count( $args['tax_query'] ) <= 1 ) {
        unset( $args['tax_query']['relation'] );
         if(count( $args['tax_query'] ) == 0) unset($args['tax_query']);
    }

    // Remove 's' if empty to prevent incorrect queries
    if ( empty( $args['s'] ) ) {
        unset( $args['s'] );
    }


	$query = new WP_Query( $args );

	// Start output buffering to capture HTML
	ob_start();

    // Display Summary Boxes
    echo grantee_listing_display_summary_boxes();

    // Display Filter Form
    echo grantee_listing_display_filters();

	if ( $query->have_posts() ) {
		echo '<div class="grantee-list-wrapper grantee-columns-' . esc_attr($columns) . '">';

		// Loop through found grantees
		while ( $query->have_posts() ) {
			$query->the_post();
			$post_id = get_the_ID();

            // --- Retrieve & Format Data for Card Display ---
            // Logo: Try custom field first, then featured image
            $logo_id  = get_post_meta( $post_id, '_grantee_logo_id', true );
            $logo_url = '';
            if ($logo_id) {
                // Get the URL for the medium-sized image attachment
                $logo_url = wp_get_attachment_image_url( $logo_id, 'medium' ); // Adjust 'medium' size if needed
            } elseif (has_post_thumbnail($post_id)) {
                // Or get the URL if a standard Featured Image is set
                $logo_url = get_the_post_thumbnail_url($post_id, 'medium');
            }

			$project_name   = get_post_meta( $post_id, '_grantee_project_name', true );
			$start_date_raw = get_post_meta( $post_id, '_grantee_start_date', true );
			$end_date_raw   = get_post_meta( $post_id, '_grantee_end_date', true );
            $address        = get_post_meta( $post_id, '_grantee_address', true ); // Not displayed in card, but available
			$website        = get_post_meta( $post_id, '_grantee_website', true ); // Not displayed in card, but available
			$socials_raw    = get_post_meta( $post_id, '_grantee_socials', true ); // Not displayed in card, but available
			$funding_amount_raw = get_post_meta( $post_id, '_grantee_funding_amount', true );

            $funding_amount_formatted = '';
            if (!empty($funding_amount_raw) && is_numeric($funding_amount_raw)) {
                 $funding_amount_formatted = esc_html($atts['currency_symbol']) . number_format_i18n(floatval($funding_amount_raw), 0); // Format with 0 decimals for example
            }

            // Get taxonomy terms for Type and Countries
            $types     = get_the_terms( $post_id, 'grantee_type' );
            $types_list_card = '';
            if ( $types && ! is_wp_error( $types ) ) {
                 $type_links = array();
                foreach ( $types as $type_term ) {
                    // Link to archive page for this type
                    $type_links[] = '<a href="' . esc_url( get_term_link( $type_term ) ) . '">' . esc_html( $type_term->name ) . '</a>';
                }
                $types_list_card = join( ', ', $type_links );
            }

            $countries = get_the_terms( $post_id, 'grantee_country' );
            $countries_list_card = '';
            if ( $countries && ! is_wp_error( $countries ) ) {
                $country_names = array();
                foreach ( $countries as $country_term ) {
                    $country_names[] = esc_html( $country_term->name );
                }
                $countries_list_card = join( ', ', $country_names );
            }

            // Format Timeline for Card
            $timeline_card = '';
            if ($start_date_raw) {
                 $timeline_card .= date_i18n('M Y', strtotime($start_date_raw)); // E.g., "Oct 2017"
            }
            if ($end_date_raw) {
                $timeline_card .= ($timeline_card ? ' – ' : '') . date_i18n('M Y', strtotime($end_date_raw)); // E.g., "Oct 2019"
            }

			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class('grantee-item-card grantee-list-card'); ?>>

                <div class="grantee-card-body">
                    <h3 class="grantee-card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); // Grantee Name ?></a></h3>

                    <?php if ($project_name): // Project Name ?>
                        <p class="grantee-card-project"><?php echo esc_html($project_name); ?></p>
                    <?php endif; ?>

                     <div class="grantee-card-meta">
                        <?php if ($types_list_card): // Grantee Type(s) ?>
                            <span class="grantee-card-type"><span class="label"><?php _e('Type:', 'grantee-listing'); ?></span> <?php echo $types_list_card; // Already escaped ?></span>
                        <?php endif; ?>
                         <?php if ($countries_list_card): // Countries Involved ?>
                            <span class="grantee-card-countries"><span class="label"><?php _e('Countries:', 'grantee-listing'); ?></span> <?php echo $countries_list_card; // Already escaped ?></span>
                        <?php endif; ?>
                         <?php if ($timeline_card): // Timeline ?>
                            <span class="grantee-card-timeline"><span class="label"><?php _e('Timeline:', 'grantee-listing'); ?></span> <?php echo esc_html($timeline_card); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="grantee-card-description">
                        <?php
                            // Display excerpt of the main content (Description)
                            echo wp_kses_post(wp_trim_words(get_the_content(), 30, '... <a href="'.get_permalink().'" class="read-more">' . __('Read More', 'grantee-listing') . '</a>'));
                        ?>
                    </div>

                    <div class="grantee-card-bottom">
                         <?php if ($funding_amount_formatted): // Funding Amount ?>
                            <span class="grantee-card-funding"><?php echo $funding_amount_formatted; ?></span>
                        <?php endif; ?>

                        <div class="grantee-card-logo">
                             <?php if ($logo_url): ?>
                                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?> Logo">
                            <?php else: ?>
                                <div class="grantee-card-placeholder-logo"><span><?php echo esc_html(strtoupper(mb_substr(get_the_title(), 0, 1))); ?></span></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div><!-- .grantee-card-body -->

			</article>
			<?php
		} // End while have_posts

        echo '</div>'; // .grantee-list-wrapper

        // Pagination
        $big = 999999999; // need an unlikely integer for paginate_links
        echo '<div class="grantee-pagination">';
        echo paginate_links( array(
            'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
            'format' => '?paged=%#%',
            'current' => max( 1, $paged ),
            'total' => $query->max_num_pages,
            'prev_text' => __('« Previous', 'grantee-listing'),
            'next_text' => __('Next »', 'grantee-listing'),
             'add_args' => array_filter(array( // Add current filters back to pagination links
                's_keyword' => isset($_GET['s_keyword']) ? sanitize_text_field($_GET['s_keyword']) : '',
                'grantee_type_filter' => isset($_GET['grantee_type_filter']) ? sanitize_title($_GET['grantee_type_filter']) : '',
                'grantee_country_filter' => isset($_GET['grantee_country_filter']) ? sanitize_title($_GET['grantee_country_filter']) : '',
             ))
        ) );
        echo '</div>';


	} else {
		// No grantees found message
		echo '<p class="no-grantees-found">' . __( 'No grantees found matching your criteria.', 'grantee-listing' ) . '</p>';
	}

	wp_reset_postdata(); // Restore original Post Data
	return ob_get_clean(); // Return the captured HTML
}
add_shortcode( 'grantee_list', 'grantee_listing_shortcode' );

// The grantee_listing_single_content function and its add_filter('the_content', ...) hook
// have been removed from this file as we are now using a custom template.