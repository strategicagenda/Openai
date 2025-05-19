<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add Meta Box for Grantee Details.
 */
function grantee_listing_add_meta_box() {
	add_meta_box(
		'grantee_details',                 // ID
		__( 'Grantee Details', 'grantee-listing' ), // Title
		'grantee_listing_render_meta_box', // Callback function
		'grantee',                         // Post type
		'normal',                          // Context (normal, side, advanced)
		'high'                             // Priority (high, core, default, low)
	);
}
add_action( 'add_meta_boxes_grantee', 'grantee_listing_add_meta_box' );

/**
 * Render Meta Box Content.
 * @param WP_Post $post The post object.
 */
function grantee_listing_render_meta_box( $post ) {
	wp_nonce_field( 'grantee_listing_save_meta_box_data', 'grantee_listing_meta_box_nonce' );

    $logo_id        = get_post_meta( $post->ID, '_grantee_logo_id', true );
	$project_name   = get_post_meta( $post->ID, '_grantee_project_name', true );
	$start_date     = get_post_meta( $post->ID, '_grantee_start_date', true );
	$end_date       = get_post_meta( $post->ID, '_grantee_end_date', true );
	$address        = get_post_meta( $post->ID, '_grantee_address', true );
	$website        = get_post_meta( $post->ID, '_grantee_website', true );
	$socials        = get_post_meta( $post->ID, '_grantee_socials', true ); // One per line
	$funding_amount = get_post_meta( $post->ID, '_grantee_funding_amount', true );
    $logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
	?>
    <style>
        .grantee-meta-box-table th { text-align: left; width: 150px; padding-top: 15px;}
        .grantee-meta-box-table td { vertical-align: top; padding-top: 10px;}
        .grantee-meta-box-table input[type="text"],
        .grantee-meta-box-table input[type="url"],
        .grantee-meta-box-table input[type="number"],
        .grantee-meta-box-table input[type="date"],
        .grantee-meta-box-table textarea { width: 98%; max-width: 400px; }
        .grantee-logo-preview img { max-width: 150px; max-height: 100px; border: 1px solid #ccc; margin-top: 5px; display: block; }
        .grantee-logo-preview .remove-logo { margin-top: 5px; }
    </style>

	<table class="form-table grantee-meta-box-table">
        <tr>
            <th><label for="grantee_logo"><?php _e( 'Grantee Logo', 'grantee-listing' ); ?></label></th>
            <td>
                <input type="hidden" name="grantee_logo_id" id="grantee_logo_id" value="<?php echo esc_attr( $logo_id ); ?>" />
                <div id="grantee_logo_preview" class="grantee-logo-preview">
                    <?php if ( $logo_url ) : ?>
                        <img src="<?php echo esc_url( $logo_url ); ?>" alt="Logo Preview">
                    <?php endif; ?>
                </div>
                <button type="button" class="button" id="upload_logo_button"><?php _e( 'Upload/Select Logo', 'grantee-listing' ); ?></button>
                <button type="button" class="button remove-logo" id="remove_logo_button" style="<?php echo $logo_id ? '' : 'display:none;'; ?>"><?php _e( 'Remove Logo', 'grantee-listing' ); ?></button>
                 <p class="description"><?php _e( 'Alternatively, set a "Featured Image" for the logo.', 'grantee-listing' ); ?></p>
            </td>
        </tr>
		<tr>
			<th><label for="grantee_project_name"><?php _e( 'Project Name', 'grantee-listing' ); ?></label></th>
			<td><input type="text" id="grantee_project_name" name="grantee_project_name" value="<?php echo esc_attr( $project_name ); ?>" /></td>
		</tr>
		<tr>
			<th><label><?php _e( 'Grant Timeline', 'grantee-listing' ); ?></label></th>
			<td>
				<label for="grantee_start_date"><?php _e( 'Start Date:', 'grantee-listing' ); ?></label>
				<input type="date" id="grantee_start_date" name="grantee_start_date" value="<?php echo esc_attr( $start_date ); ?>" /><br>
				<label for="grantee_end_date"><?php _e( 'End Date:', 'grantee-listing' ); ?></label>
				<input type="date" id="grantee_end_date" name="grantee_end_date" value="<?php echo esc_attr( $end_date ); ?>" />
			</td>
		</tr>
		<tr>
			<th><label for="grantee_address"><?php _e( 'Address', 'grantee-listing' ); ?></label></th>
			<td><textarea id="grantee_address" name="grantee_address" rows="3"><?php echo esc_textarea( $address ); ?></textarea></td>
		</tr>
		<tr>
			<th><label for="grantee_website"><?php _e( 'Website', 'grantee-listing' ); ?></label></th>
			<td><input type="url" id="grantee_website" name="grantee_website" value="<?php echo esc_url( $website ); ?>" placeholder="https://example.com" /></td>
		</tr>
		<tr>
			<th><label for="grantee_socials"><?php _e( 'Social Media Links', 'grantee-listing' ); ?></label></th>
			<td>
				<textarea id="grantee_socials" name="grantee_socials" rows="3"><?php echo esc_textarea( $socials ); ?></textarea>
				<p class="description"><?php _e( 'Enter one full link per line (e.g., https://twitter.com/user).', 'grantee-listing' ); ?></p>
			</td>
		</tr>
		<tr>
			<th><label for="grantee_funding_amount"><?php _e( 'Funding Amount', 'grantee-listing' ); ?></label></th>
			<td>
                <input type="number" id="grantee_funding_amount" name="grantee_funding_amount" value="<?php echo esc_attr( $funding_amount ); ?>" step="0.01" min="0" />
				<p class="description"><?php _e( 'E.g., 50000.00. Currency symbol displayed on frontend.', 'grantee-listing' ); ?></p>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Save Meta Box Data.
 * @param int $post_id The post ID.
 */
function grantee_listing_save_meta_box_data( $post_id ) {
	if ( ! isset( $_POST['grantee_listing_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['grantee_listing_meta_box_nonce'], 'grantee_listing_save_meta_box_data' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
    if ( get_post_type( $post_id ) !== 'grantee' ) {
        return;
    }

    $fields_to_save = [
        'grantee_logo_id'       => 'int',
        'grantee_project_name'  => 'text',
        'grantee_start_date'    => 'date',
        'grantee_end_date'      => 'date',
        'grantee_address'       => 'textarea',
        'grantee_website'       => 'url',
        'grantee_socials'       => 'textarea',
        'grantee_funding_amount'=> 'float'
    ];

    foreach ( $fields_to_save as $field_key => $type ) {
        if ( isset( $_POST[ $field_key ] ) ) {
            $value = $_POST[ $field_key ];
            switch ( $type ) {
                case 'int': $sanitized_value = intval( $value ); break;
                case 'float': $sanitized_value = floatval( $value ); break;
                case 'url': $sanitized_value = esc_url_raw( $value ); break;
                case 'textarea': $sanitized_value = sanitize_textarea_field( $value ); break;
                case 'date': default: $sanitized_value = sanitize_text_field( $value ); break;
            }
            update_post_meta( $post_id, '_' . $field_key, $sanitized_value );
        } else {
            // delete_post_meta( $post_id, '_' . $field_key ); // Uncomment to delete if not set
        }
    }
}
add_action( 'save_post_grantee', 'grantee_listing_save_meta_box_data' );