<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define('GRANTEE_LAST_IMPORT_META_KEY', '_grantee_last_import_batch');

/**
 * Get the defined plugin fields for mapping.
 * @return array Associative array of field_key => Field Name
 */
function grantee_listing_get_mappable_fields() {
    return [
        'grantee_name'       => __('Grantee Name (Title)', 'grantee-listing'),
        'description'        => __('Description (Main Content)', 'grantee-listing'),
        'project_name'       => __('Project Name', 'grantee-listing'),
        'logo_url'           => __('Logo URL', 'grantee-listing'),
        'grantee_type'       => __('Grantee Type (Taxonomy, comma-sep)', 'grantee-listing'),
        'countries_involved' => __('Countries Involved (Taxonomy, comma-sep)', 'grantee-listing'),
        'start_date'         => __('Start Date (YYYY-MM-DD)', 'grantee-listing'),
        'end_date'           => __('End Date (YYYY-MM-DD)', 'grantee-listing'),
        'address'            => __('Address', 'grantee-listing'),
        'website'            => __('Website (URL)', 'grantee-listing'),
        'socials'            => __('Socials (Comma-sep URLs)', 'grantee-listing'),
        'funding_amount'     => __('Funding Amount (Numeric)', 'grantee-listing'),
    ];
}


/**
 * Add Importer submenu page under Grantees.
 */
function grantee_listing_add_importer_page() {
	add_submenu_page(
		'edit.php?post_type=grantee',
		__( 'Import Grantees', 'grantee-listing' ),
		__( 'Import Grantees', 'grantee-listing' ),
		'manage_options',
		'grantee-importer',
		'grantee_listing_render_importer_page'
	);
}
add_action( 'admin_menu', 'grantee_listing_add_importer_page' );

/**
 * Render the Importer Page.
 */
function grantee_listing_render_importer_page() {
	?>
	<div class="wrap">
		<h1><?php _e( 'Import Grantees from CSV', 'grantee-listing' ); ?></h1>

		<?php
        if (isset($_POST['grantee_undo_nonce']) && wp_verify_nonce($_POST['grantee_undo_nonce'], 'grantee_undo_last_import')) {
            grantee_listing_undo_last_import();
        }

		$current_step = isset($_REQUEST['import_step']) ? intval($_REQUEST['import_step']) : 1;

		if ( $current_step === 1 && isset( $_POST['grantee_import_nonce_step1'] ) && wp_verify_nonce( $_POST['grantee_import_nonce_step1'], 'grantee_csv_import_step1' ) ) {
			if ( isset( $_FILES['grantee_csv_file'] ) && ! empty( $_FILES['grantee_csv_file']['tmp_name'] ) ) {
				grantee_listing_render_mapping_preview_step( $_FILES['grantee_csv_file'] );
			} else {
				echo '<div class="notice notice-error"><p>' . __( 'Please upload a CSV file.', 'grantee-listing' ) . '</p></div>';
				grantee_listing_render_upload_step();
			}
		} elseif ( $current_step === 2 && isset( $_POST['grantee_import_nonce_step2'] ) && wp_verify_nonce( $_POST['grantee_import_nonce_step2'], 'grantee_csv_import_step2' ) ) {
			$file_path = sanitize_text_field($_POST['csv_file_path']);
            $update_existing = isset($_POST['update_existing']) && $_POST['update_existing'] == '1';
            $column_map = isset($_POST['column_map']) ? $_POST['column_map'] : []; // Array of plugin_field_key => csv_header_name

			if (file_exists($file_path) && !empty($column_map)) {
				grantee_listing_process_csv_import_action($file_path, $update_existing, $column_map);
			} else {
				echo '<div class="notice notice-error"><p>' . __( 'Error: CSV file path not found or column mappings missing.', 'grantee-listing' ) . '</p></div>';
			}
			grantee_listing_render_upload_step(); // Show upload form again after processing
		} else {
			grantee_listing_render_upload_step();
		}
		?>
	</div>
	<?php
}

/**
 * Render Step 1: Upload Form.
 */
function grantee_listing_render_upload_step() {
    ?>
    <div class="grantee-importer-form-wrap">
        <form method="post" enctype="multipart/form-data" action="<?php echo admin_url('edit.php?post_type=grantee&page=grantee-importer'); ?>">
            <?php wp_nonce_field( 'grantee_csv_import_step1', 'grantee_import_nonce_step1' ); ?>
            <input type="hidden" name="import_step" value="1">
            <h2><?php _e( 'Step 1: Upload CSV File', 'grantee-listing' ); ?></h2>
            <p>
                <label for="grantee_csv_file"><?php _e( 'Choose a CSV file to import:', 'grantee-listing' ); ?></label><br>
                <input type="file" id="grantee_csv_file" name="grantee_csv_file" accept=".csv" required>
            </p>
            <?php submit_button( __( 'Upload and Proceed to Mapping', 'grantee-listing' ) ); ?>
        </form>
    </div>
    <?php grantee_listing_display_instructions_and_undo(); ?>
    <?php
}

/**
 * Render Step 2: Column Mapping & Preview Data.
 * @param array $file Uploaded file array from $_FILES.
 */
function grantee_listing_render_mapping_preview_step( $file ) {
    if ( $file['error'] > 0 ) { /* ... error handling ... */ grantee_listing_render_upload_step(); return; }
    $file_mimes = array( 'text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain' );
	if ( ! in_array( strtolower($file['type']), $file_mimes ) ) { /* ... error handling ... */ grantee_listing_render_upload_step(); return; }

    $uploads = wp_upload_dir();
    $temp_filename = wp_unique_filename( $uploads['basedir'], 'grantee_import_temp.csv' );
    $temp_filepath = $uploads['basedir'] . '/' . $temp_filename;

    if ( move_uploaded_file( $file['tmp_name'], $temp_filepath ) ) {
        if ( ( $handle = fopen( $temp_filepath, 'r' ) ) !== false ) {
            $csv_headers = fgetcsv( $handle );
            if (!$csv_headers) { /* ... error handling ... */ fclose($handle); @unlink($temp_filepath); grantee_listing_render_upload_step(); return; }
            $csv_headers = array_map('trim', $csv_headers);

            $plugin_fields = grantee_listing_get_mappable_fields();
            ?>
            <h2><?php _e( 'Step 2: Map CSV Columns to Grantee Fields & Preview', 'grantee-listing' ); ?></h2>
            <p><?php _e( 'Match your CSV columns (right) to the corresponding grantee fields (left). Unmatched fields will not be imported.', 'grantee-listing' ); ?></p>
            <form method="post" action="<?php echo admin_url('edit.php?post_type=grantee&page=grantee-importer'); ?>">
                <?php wp_nonce_field( 'grantee_csv_import_step2', 'grantee_import_nonce_step2' ); ?>
                <input type="hidden" name="import_step" value="2">
                <input type="hidden" name="csv_file_path" value="<?php echo esc_attr( $temp_filepath ); ?>">

                <table class="form-table grantee-column-mapping-table">
                    <thead>
                        <tr>
                            <th><?php _e('Grantee Field', 'grantee-listing'); ?></th>
                            <th><?php _e('CSV Column Header from Your File', 'grantee-listing'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($plugin_fields as $field_key => $field_label) : ?>
                            <tr>
                                <th><label for="map_<?php echo esc_attr($field_key); ?>"><?php echo esc_html($field_label); ?><?php echo ($field_key === 'grantee_name') ? ' <span class="required">*</span>' : ''; ?></label></th>
                                <td>
                                    <select name="column_map[<?php echo esc_attr($field_key); ?>]" id="map_<?php echo esc_attr($field_key); ?>">
                                        <option value=""><?php _e('-- Do Not Import --', 'grantee-listing'); ?></option>
                                        <?php
                                        $selected_header = '';
                                        // Auto-matching attempt
                                        $normalized_field_label = strtolower(str_replace([' (', ')'], '', $field_label)); // Basic normalization
                                        $normalized_field_key = strtolower(str_replace('_', ' ', $field_key));

                                        foreach ($csv_headers as $csv_header) {
                                            $normalized_csv_header = strtolower(trim($csv_header));
                                            $is_selected = false;
                                            if (empty($selected_header)) { // Try to auto-select only once
                                                if ($normalized_csv_header === $normalized_field_label || $normalized_csv_header === $normalized_field_key) {
                                                    $is_selected = true;
                                                    $selected_header = $csv_header;
                                                }
                                            }
                                            echo '<option value="' . esc_attr($csv_header) . '" ' . selected($is_selected, true, false) . '>' . esc_html($csv_header) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h3><?php _e('Data Preview (First 3 Rows)', 'grantee-listing'); ?></h3>
                <p><?php _e('This preview shows how data will be interpreted based on the headers. It does not yet use your mappings above.', 'grantee-listing'); ?></p>
                <table class="wp-list-table widefat striped fixed grantee-preview-table">
                    <thead><tr>
                        <?php foreach ( $csv_headers as $col_name ) { echo '<th>' . esc_html( $col_name ) . '</th>'; } ?>
                    </tr></thead>
                    <tbody>
                        <?php
                        $preview_row_count = 0;
                        // Note: $handle was used for headers, rewind or reopen for preview data
                        fclose($handle); // Close and reopen to reset pointer
                        $handle = fopen( $temp_filepath, 'r' );
                        fgetcsv($handle); // Skip header row again

                        while ( ( $row_data = fgetcsv( $handle ) ) !== false && $preview_row_count < 3 ) {
                            echo '<tr>';
                            foreach ( $row_data as $cell_data ) {
                                echo '<td>' . esc_html( mb_strimwidth($cell_data, 0, 70, "...") ) . '</td>';
                            }
                            echo '</tr>';
                            $preview_row_count++;
                        }
                        ?>
                    </tbody>
                </table>
                <?php fclose( $handle ); ?>
                <p style="margin-top:20px;">
					<input type="checkbox" id="update_existing_preview" name="update_existing" value="1" checked>
					<label for="update_existing_preview"><?php _e( 'Update existing grantees if "Grantee Name" matches (based on your mapping)?', 'grantee-listing' ); ?></label>
				</p>
                <?php submit_button( __( 'Confirm Mapping and Import Grantees', 'grantee-listing' ), 'primary', 'confirm_import' ); ?>
                <a href="<?php echo admin_url('edit.php?post_type=grantee&page=grantee-importer'); ?>" class="button"><?php _e('Cancel and Upload New File', 'grantee-listing'); ?></a>
            </form>
            <?php
        } else { /* ... error opening file ... */ grantee_listing_render_upload_step(); }
    } else { /* ... error moving file ... */ grantee_listing_render_upload_step(); }
}


/**
 * Process the CSV import (Actual Import Logic).
 * @param string $csv_file_path Path to the CSV file.
 * @param bool $update_existing Whether to update existing posts.
 * @param array $column_map User-defined column mappings (plugin_field_key => csv_header_name).
 */
function grantee_listing_process_csv_import_action( $csv_file_path, $update_existing = false, $column_map = [] ) {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( /* ... */ ); }
	@ini_set( 'max_execution_time', 300 ); @ini_set( 'memory_limit', '256M' );

	$imported_count = 0; $updated_count = 0; $skipped_count = 0;
	$error_messages = array(); $current_batch_id = time();
    $plugin_fields = grantee_listing_get_mappable_fields(); // Get our defined fields

    // Reverse map for easier lookup: CSV Header => plugin_field_key
    $csv_header_to_plugin_key = [];
    foreach ($column_map as $plugin_key => $csv_header) {
        if (!empty($csv_header)) {
            $csv_header_to_plugin_key[trim($csv_header)] = $plugin_key;
        }
    }

	if ( ( $handle = fopen( $csv_file_path, 'r' ) ) !== false ) {
		$csv_headers_raw = fgetcsv( $handle ); // Read the actual headers from file
        if (!$csv_headers_raw) { /* ... error handling ... */ echo '<div class="notice notice-error"><p>Could not read CSV header.</p></div>'; return; }
        $csv_headers_raw = array_map('trim', $csv_headers_raw);

        // Ensure the mapped Grantee Name column exists in the CSV
        $grantee_name_csv_col = isset($column_map['grantee_name']) ? $column_map['grantee_name'] : '';
        if (empty($grantee_name_csv_col) || !in_array($grantee_name_csv_col, $csv_headers_raw)) {
            echo '<div class="notice notice-error"><p>' . __('Error: The CSV column mapped to "Grantee Name" was not found in the CSV file or not mapped. This is a required field.', 'grantee-listing') . '</p></div>';
            fclose($handle); @unlink($csv_file_path); return;
        }

		require_once( ABSPATH . 'wp-admin/includes/media.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		$row_number = 1;
		while ( ( $row_data_raw = fgetcsv( $handle ) ) !== false ) {
			$row_number++;
            if (count($csv_headers_raw) !== count($row_data_raw)) { /* ... skip if column count mismatch ... */ $skipped_count++; continue; }
			$row_assoc = array_combine( $csv_headers_raw, $row_data_raw ); // Data from CSV row, keyed by CSV headers

            // Get values based on mapping
            $grantee_name = isset($row_assoc[$column_map['grantee_name']]) ? trim($row_assoc[$column_map['grantee_name']]) : '';
			if ( empty( $grantee_name ) ) { /* ... skip ... */ $skipped_count++; continue; }

            $description_csv_col = isset($column_map['description']) ? $column_map['description'] : '';
            $description_val = !empty($description_csv_col) && isset($row_assoc[$description_csv_col]) ? wp_kses_post(trim($row_assoc[$description_csv_col])) : '';

			$post_args = [ 'post_type' => 'grantee', 'post_status' => 'publish', 'post_title' => sanitize_text_field( $grantee_name ), 'post_content' => $description_val ];
			$existing_post = get_page_by_title( $grantee_name, OBJECT, 'grantee' );
			$post_id = 0; $is_new_post = false;

			if ( $existing_post && $update_existing ) { /* ... update post ... */ $post_args['ID'] = $existing_post->ID; $post_id = wp_update_post($post_args); if(!is_wp_error($post_id)) $updated_count++; else {$skipped_count++; continue;} }
            elseif ( ! $existing_post ) { /* ... insert post ... */ $post_id = wp_insert_post($post_args); if(!is_wp_error($post_id)) {$imported_count++; $is_new_post = true;} else {$skipped_count++; continue;} }
            else { /* ... skip existing, no update ... */ $skipped_count++; continue; }

			if ( $post_id && ! is_wp_error( $post_id ) ) {
                if ($is_new_post) { update_post_meta($post_id, GRANTEE_LAST_IMPORT_META_KEY, $current_batch_id); }

                // Loop through plugin fields and update meta/taxonomies based on mapping
                foreach ($plugin_fields as $field_key => $field_label) {
                    if (isset($column_map[$field_key]) && !empty($column_map[$field_key]) && isset($row_assoc[$column_map[$field_key]])) {
                        $csv_value = trim($row_assoc[$column_map[$field_key]]);
                        $meta_key = '_grantee_' . $field_key; // Assuming meta keys follow this pattern

                        switch ($field_key) {
                            case 'grantee_name': case 'description': break; // Handled by wp_insert/update_post
                            case 'project_name': update_post_meta($post_id, $meta_key, sanitize_text_field($csv_value)); break;
                            case 'start_date': case 'end_date': update_post_meta($post_id, $meta_key, sanitize_text_field($csv_value)); break;
                            case 'address': update_post_meta($post_id, $meta_key, sanitize_textarea_field($csv_value)); break;
                            case 'website': update_post_meta($post_id, $meta_key, esc_url_raw($csv_value)); break;
                            case 'funding_amount': if(is_numeric($csv_value)) update_post_meta($post_id, $meta_key, floatval($csv_value)); break;
                            case 'socials':
                                $social_urls = array_map('trim', explode(',', $csv_value));
                                $sanitized_social_urls = array_map('esc_url_raw', $social_urls);
                                update_post_meta($post_id, $meta_key, implode("\n", array_filter($sanitized_social_urls)));
                                break;
                            case 'grantee_type':
                                $types = array_map('trim', explode(',', $csv_value));
                                wp_set_object_terms($post_id, $types, 'grantee_type', false);
                                break;
                            case 'countries_involved':
                                $countries = array_map('trim', explode(',', $csv_value));
                                wp_set_object_terms($post_id, $countries, 'grantee_country', false);
                                break;
                            case 'logo_url':
                                if (!empty($csv_value)) {
                                    $logo_url_val = esc_url_raw($csv_value);
                                    if ($logo_url_val) {
                                        $attachment_id = grantee_listing_sideload_image($logo_url_val, $post_id, $grantee_name . ' Logo');
                                        if (!is_wp_error($attachment_id)) { update_post_meta($post_id, '_grantee_logo_id', $attachment_id); }
                                        // else { $error_messages[] = ... }
                                    }
                                }
                                break;
                        }
                    }
                }
			}
		}
		fclose( $handle );
        @unlink($csv_file_path); // Delete the temporary CSV file

		echo '<div class="notice notice-success is-dismissible"><p>';
		printf( __( 'Import complete! %d grantees imported, %d grantees updated, %d rows skipped.', 'grantee-listing' ), $imported_count, $updated_count, $skipped_count );
		echo '</p></div>';
        if (!empty($error_messages)) { /* ... display errors ... */ }
        if ($imported_count > 0) { update_option('grantee_last_import_batch_id', $current_batch_id); }

	} else { /* ... error opening file ... */ }
}

/**
 * Display instructions and undo section.
 */
function grantee_listing_display_instructions_and_undo() {
    ?>
    <div class="grantee-importer-instructions">
        <h2><?php _e( 'CSV File Format Instructions', 'grantee-listing' ); ?></h2>
        <p><?php _e( 'Please ensure your CSV file is UTF-8 encoded. The first row should be column headers.', 'grantee-listing' ); ?></p>
        <p><?php _e( 'Recommended (but not strictly required) headers for auto-matching:', 'grantee-listing' ); ?></p>
        <ul>
            <?php foreach (grantee_listing_get_mappable_fields() as $label): ?>
                <li><strong><?php echo esc_html($label); ?></strong></li>
            <?php endforeach; ?>
        </ul>
        <p>
            <a href="<?php echo esc_url(GRANTEE_LISTING_URL . 'sample/sample-grantees.csv'); ?>" download="sample-grantees.csv">
                <?php _e('Download Sample CSV File', 'grantee-listing'); ?>
            </a>
        </p>
    </div>
    <hr>
    <div class="grantee-importer-undo-wrap">
        <h2><?php _e( 'Undo Last Import', 'grantee-listing' ); ?></h2>
        <p><?php _e( 'This will attempt to delete any grantees that were newly created during the most recent import session. It will NOT revert changes to grantees that were updated.', 'grantee-listing' ); ?></p>
        <form method="post" action="<?php echo admin_url('edit.php?post_type=grantee&page=grantee-importer'); ?>">
            <?php wp_nonce_field( 'grantee_undo_last_import', 'grantee_undo_nonce' ); ?>
            <?php submit_button( __( 'Undo Last Import (Delete Newly Created)', 'grantee-listing' ), 'delete', 'undo_last_import', false, ['onclick' => 'return confirm("' . esc_js(__('Are you sure you want to delete grantees from the last import batch? This action cannot be undone.', 'grantee-listing')) . '");'] ); ?>
        </form>
    </div>
    <?php
}

/**
 * Undo Last Import - Deletes posts created in the last batch.
 */
function grantee_listing_undo_last_import() {
    if ( ! current_user_can( 'manage_options' ) ) { wp_die( /* ... */ ); }
    $last_batch_id = get_option('grantee_last_import_batch_id');
    if (!$last_batch_id) { /* ... no batch found ... */ return; }

    $args = [ 'post_type' => 'grantee', 'posts_per_page' => -1, 'meta_query' => [['key' => GRANTEE_LAST_IMPORT_META_KEY, 'value' => $last_batch_id]], 'fields' => 'ids' ];
    $query = new WP_Query($args); $deleted_count = 0;
    if ($query->have_posts()) { foreach ($query->posts as $post_id_to_delete) { if (wp_delete_post($post_id_to_delete, true)) { $deleted_count++; } } }
    if ($deleted_count > 0) { echo '<div class="notice notice-success"><p>' . sprintf(__( '%d grantees from the last import batch have been deleted.', 'grantee-listing'), $deleted_count) . '</p></div>'; }
    else { echo '<div class="notice notice-info"><p>' . __( 'No grantees found from the last import batch to delete (or they were already deleted).', 'grantee-listing' ) . '</p></div>'; }
    delete_option('grantee_last_import_batch_id');
}


// Sideload image function (same as before)
function grantee_listing_sideload_image( $image_url, $post_id = 0, $desc = 'Imported Image' ) {
    if ( ! function_exists( 'media_sideload_image' ) ) { require_once( ABSPATH . 'wp-admin/includes/media.php' ); require_once( ABSPATH . 'wp-admin/includes/file.php' ); require_once( ABSPATH . 'wp-admin/includes/image.php' ); }
    if ( function_exists( 'media_handle_sideload' ) ) {
        $tmp = download_url( $image_url ); if ( is_wp_error( $tmp ) ) { return $tmp; }
        $file_array = array(); preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $image_url, $matches );
        $file_array['name'] = isset($matches[0]) ? basename( $matches[0] ) : uniqid('image_') . '.jpg';
        $file_array['tmp_name'] = $tmp;
        if ( is_wp_error( $tmp ) ) { @unlink( $file_array['tmp_name'] ); return new WP_Error('sideload_failed_tmp', 'Could not download image to temporary location.'); }
        $attachment_id = media_handle_sideload( $file_array, $post_id, $desc );
        if ( is_wp_error( $attachment_id ) ) { @unlink( $file_array['tmp_name'] ); }
        return $attachment_id;
    }
    return new WP_Error('sideload_function_missing', 'media_handle_sideload function not available.');
}