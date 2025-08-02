/**
 * Vernissaria QR Print JavaScript
 * Handles PDF generation and download functionality
 */

jQuery(document).ready(function ($) {

    // Handle PDF generation
    $('#vernissaria-generate-pdf').on('click', function (e) {
        e.preventDefault();

        const button = $(this);
        const buttonText = button.find('.button-text');
        const spinner = button.find('.spinner');
        const messageContainer = $('#vernissaria-print-messages');
        const messageText = $('#vernissaria-print-message-text');
        const resultContainer = $('#vernissaria-pdf-result');

        // Get form values
        const qrSize = $('#vernissaria-qr-size').val();
        const paperSize = $('#vernissaria-paper-size').val();

        // Show loading state
        button.prop('disabled', true);
        buttonText.text('Generating PDF...');
        spinner.show();
        messageContainer.hide();
        resultContainer.hide();

        // Make AJAX request
        $.ajax({
            url: vernissaria_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'generate_qr_pdf',
                nonce: vernissaria_ajax.nonce,
                qr_size: qrSize,
                paper_size: paperSize
            },
            success: function (response) {
                if (response.success) {
                    showSuccess(response.data);
                } else {
                    showError(response.data || 'Unknown error occurred');
                }
            },
            error: function (xhr, status, error) {
                let errorMessage = 'Connection error occurred';

                if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage = xhr.responseJSON.data;
                } else if (error) {
                    errorMessage = 'Error: ' + error;
                }

                showError(errorMessage);
            },
            complete: function () {
                // Reset button state
                button.prop('disabled', false);
                buttonText.text('Generate PDF');
                spinner.hide();
            }
        });

        function showError(message) {
            messageContainer.removeClass('notice-success').addClass('notice-error');
            messageText.text(message);
            messageContainer.show();
        }

        function showSuccess(data) {
            // Hide any previous messages
            messageContainer.hide();

            // Populate result data
            $('#pdf-filename').text(data.filename || 'QR Codes PDF');
            $('#pdf-qr-count').text(data.qr_codes_count || 'Unknown');

            // Format expiration date
            if (data.expires_at) {
                const expiresDate = new Date(data.expires_at);
                $('#pdf-expires').text(expiresDate.toLocaleString());
            } else {
                $('#pdf-expires').text('Not specified');
            }

            // Set download links
            if (data.local_file_url) {
                $('#pdf-download-link').attr('href', data.local_file_url);
            }

            if (data.media_library_url) {
                $('#pdf-media-link').attr('href', data.media_library_url);
            }

            // Show success message
            messageContainer.removeClass('notice-error').addClass('notice-success');
            messageText.text('PDF generated successfully! File saved to media library.');
            messageContainer.show();

            // Show result container
            resultContainer.show();

            // Scroll to result
            $('html, body').animate({
                scrollTop: resultContainer.offset().top - 50
            }, 500);
        }
    });

    // Handle form validation
    function validateForm() {
        const qrSize = $('#vernissaria-qr-size').val();
        const paperSize = $('#vernissaria-paper-size').val();

        if (!qrSize || !paperSize) {
            return false;
        }

        return true;
    }

    // Real-time validation
    $('#vernissaria-qr-size, #vernissaria-paper-size').on('change', function () {
        const isValid = validateForm();
        $('#vernissaria-generate-pdf').prop('disabled', !isValid);
    });

    // Initialize form validation
    if (!validateForm()) {
        $('#vernissaria-generate-pdf').prop('disabled', true);
    }
});