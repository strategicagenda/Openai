jQuery(document).ready(function($) {
    let mediaUploader;
    $('#upload_logo_button').on('click', function(e) {
        e.preventDefault();
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: 'Choose Grantee Logo',
            button: { text: 'Choose Logo' },
            multiple: false
        });
        mediaUploader.on('select', function() {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#grantee_logo_id').val(attachment.id);
            $('#grantee_logo_preview').html('<img src="' + attachment.sizes.medium.url + '" alt="Logo Preview">');
            $('#remove_logo_button').show();
        });
        mediaUploader.open();
    });
    $('#remove_logo_button').on('click', function(e) {
        e.preventDefault();
        $('#grantee_logo_id').val('');
        $('#grantee_logo_preview').html('');
        $(this).hide();
    });
});