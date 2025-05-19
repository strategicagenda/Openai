<?php
get_header();
?>

<div id="primary" class="content-area grantee-single-page">
    <main id="main" class="site-main grantee-single-main-content" role="main">

        <?php
        $grantees_archive_url = get_post_type_archive_link('grantee');
        if ($grantees_archive_url) {
            echo '<div class="grantee-back-button-wrap">';
            echo '<a href="' . esc_url($grantees_archive_url) . '" class="grantee-back-button">← ' . esc_html__('Back to Grantees List', 'grantee-listing') . '</a>';
            echo '</div>';
        }
        ?>

        <?php
        while ( have_posts() ) :
            the_post();
            $post_id = get_the_ID();

            $logo_id        = get_post_meta( $post_id, '_grantee_logo_id', true );
            $logo_url       = '';
            if ($logo_id) {
                $logo_url = wp_get_attachment_image_url( $logo_id, 'medium' );
            } elseif (has_post_thumbnail($post_id)) {
                $logo_url = get_the_post_thumbnail_url($post_id, 'medium');
            }

            $project_name   = get_post_meta( $post_id, '_grantee_project_name', true );
            $start_date_raw = get_post_meta( $post_id, '_grantee_start_date', true );
            $end_date_raw   = get_post_meta( $post_id, '_grantee_end_date', true );
            $address        = get_post_meta( $post_id, '_grantee_address', true );
            $website        = get_post_meta( $post_id, '_grantee_website', true );
            $socials_raw    = get_post_meta( $post_id, '_grantee_socials', true );
            $funding_amount_raw = get_post_meta( $post_id, '_grantee_funding_amount', true );

            $currency_symbol = '$';
            $funding_amount_formatted = '';
            if (!empty($funding_amount_raw) && is_numeric($funding_amount_raw)) {
                $funding_amount_formatted = esc_html($currency_symbol) . number_format_i18n(floatval($funding_amount_raw), 0);
            }

            $timeline_display = '';
            if ($start_date_raw) {
                 $timeline_display .= date_i18n('F Y', strtotime($start_date_raw));
            }
            if ($end_date_raw) {
                $timeline_display .= ($timeline_display ? ' – ' : '') . date_i18n('F Y', strtotime($end_date_raw));
            }

            $countries = get_the_terms( $post_id, 'grantee_country' );
            $countries_list = '';
            if ( $countries && ! is_wp_error( $countries ) ) {
                $country_names = array();
                foreach ( $countries as $country_term ) {
                    $country_names[] = esc_html( $country_term->name );
                }
                $countries_list = join( ', ', $country_names );
            }

            $grantee_types = get_the_terms( $post_id, 'grantee_type' );
            $types_list_sidebar = '';
             if ( $grantee_types && ! is_wp_error( $grantee_types ) ) {
                $type_links = array();
                foreach ( $grantee_types as $type_term ) {
                    $type_links[] = '<a href="' . esc_url( get_term_link( $type_term ) ) . '">' . esc_html( $type_term->name ) . '</a>';
                }
                $types_list_sidebar = join( ', ', $type_links );
            }

            $social_links = [];
            if (!empty($socials_raw)) {
                $lines = explode("\n", $socials_raw);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (filter_var($line, FILTER_VALIDATE_URL)) {
                        $domain = str_ireplace('www.', '', parse_url($line, PHP_URL_HOST));
                        $social_links[] = '<li><a href="' . esc_url($line) . '" target="_blank" rel="noopener noreferrer">' . esc_html(ucfirst($domain)) . '</a></li>';
                    }
                }
            }
            ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class('grantee-single-entry'); ?>>
                <div class="grantee-single-grid-container">

                    <div class="grantee-main-content-area">
                        <header class="entry-header">
                            <?php the_title( '<h1 class="entry-title grantee-name">', '</h1>' ); ?>
                            <?php if ( $project_name ) : ?>
                                <h2 class="grantee-project-name"><?php echo esc_html( $project_name ); ?></h2>
                            <?php endif; ?>
                        </header>

                        <?php if ( $countries_list ) : ?>
                            <div class="grantee-info-block grantee-countries-involved">
                                <strong><?php _e( 'Countries involved:', 'grantee-listing' ); ?></strong>
                                <p><?php echo $countries_list; ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ( $timeline_display ) : ?>
                             <div class="grantee-info-block grantee-project-timeline-main">
                                <p><?php echo $timeline_display; ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="entry-content grantee-description">
                            <?php the_content(); ?>
                            <?php
                                wp_link_pages(
                                    array(
                                        'before'   => '<nav class="page-links" aria-label="' . esc_attr__( 'Page', 'grantee-listing' ) . '">',
                                        'after'    => '</nav>',
                                        'pagelink' => esc_html__( 'Page %', 'grantee-listing' ),
                                    )
                                );
                            ?>
                        </div>
                    </div>

                    <aside class="grantee-sidebar-meta-area">
                         <div class="grantee-key-meta-block">
                            <?php if ( $logo_url ) : ?>
                                <div class="grantee-sidebar-logo">
                                    <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?> Logo">
                                </div>
                            <?php endif; ?>

                            <?php if ( $funding_amount_formatted ) : ?>
                                <div class="grantee-sidebar-block">
                                    <strong><?php _e( 'Funding amount:', 'grantee-listing' ); ?></strong>
                                    <p><?php echo $funding_amount_formatted; ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if ( $timeline_display ) : ?>
                                <div class="grantee-sidebar-block">
                                    <strong><?php _e( 'Grant Timeline:', 'grantee-listing' ); ?></strong>
                                    <p><?php echo $timeline_display; ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if ( $address ) : ?>
                                <div class="grantee-sidebar-block">
                                    <strong><?php _e( 'Address:', 'grantee-listing' ); ?></strong>
                                    <p><?php echo nl2br(esc_html( $address )); ?></p>
                                </div>
                            <?php endif; ?>
                         </div>

                         <?php if ( $types_list_sidebar ) : ?>
                            <div class="grantee-sidebar-block grantee-types-sidebar">
                                <strong><?php _e( 'Grantee Type(s):', 'grantee-listing' ); ?></strong>
                                <p><?php echo $types_list_sidebar; ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ( $website ) : ?>
                            <div class="grantee-sidebar-block grantee-website-sidebar">
                                <strong><?php _e( 'Grantee website:', 'grantee-listing' ); ?></strong>
                                <p><a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( preg_replace( '#^https?://(www\.)?#i', '', $website ) ); ?></a></p>
                            </div>
                        <?php endif; ?>

                        <?php if ( !empty($social_links) ) : ?>
                            <div class="grantee-sidebar-block grantee-socials-sidebar">
                                <strong><?php _e( 'Socials:', 'grantee-listing' ); ?></strong>
                                <ul>
                                    <?php echo implode('', $social_links); ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                    </aside>
                </div>
            </article>

            <?php
            if ( comments_open() || get_comments_number() ) {
                comments_template();
            }

        endwhile;
        ?>

    </main>
</div>

<?php
get_footer();
?>