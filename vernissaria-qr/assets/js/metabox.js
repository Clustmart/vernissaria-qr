/**
 * Vernissaria QR Code Metabox JavaScript
 * 
 * Handles AJAX functionality for the QR code metabox
 */

jQuery(document).ready(function ($) {

    // Handle update QR info button click
    $('#vernissaria_update_qr_now').on('click', function () {
        const button = $(this);
        const status = $('#vernissaria_update_qr_status');

        // Get values from form and data attributes
        const label = $('#vernissaria_qr_label').val();
        const campaign = $('#vernissaria_qr_campaign').val();
        const postId = button.data('post-id');
        const redirectKey = button.data('redirect-key');

        // Disable button and show updating status
        button.prop('disabled', true);
        status.text(vernissariaMetabox.strings.updating).show();

        // Make AJAX request to update QR info
        $.ajax({
            url: vernissariaMetabox.ajaxUrl,
            type: 'POST',
            data: {
                action: 'vernissaria_update_qr_ajax',
                nonce: vernissariaMetabox.nonce,
                post_id: postId,
                redirect_key: redirectKey,
                label: label,
                campaign: campaign
            },
            success: function (response) {
                if (response.success) {
                    status.text(vernissariaMetabox.strings.updated);
                    // Set a timeout to hide the status message
                    setTimeout(function () {
                        status.fadeOut();
                    }, 3000);
                } else {
                    status.text(vernissariaMetabox.strings.error);
                }
                button.prop('disabled', false);
            },
            error: function () {
                status.text(vernissariaMetabox.strings.error);
                button.prop