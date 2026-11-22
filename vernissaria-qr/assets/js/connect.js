/**
 * Vernissaria QR: "Connect" / "Disconnect" on the settings page.
 */
jQuery(function ($) {
    const status = $('#vernissaria-connect-status');

    function show(text, isError) {
        status
            .text(text)
            .css('color', isError ? '#b32d2e' : '')
            .show();
    }

    $('#vernissaria-connect').on('click', function () {
        const button = $(this).prop('disabled', true);
        show(vernissariaConnect.strings.connecting, false);

        $.post(vernissariaConnect.ajaxUrl, {
            action: 'vernissaria_connect',
            nonce: vernissariaConnect.nonce
        })
            .done(function (response) {
                if (response && response.success) {
                    show(vernissariaConnect.strings.connected, false);
                    window.location.reload();
                    return;
                }
                const message = response && response.data && response.data.message;
                show(vernissariaConnect.strings.failed + ' ' + (message || ''), true);
                button.prop('disabled', false);
            })
            .fail(function (xhr) {
                show(vernissariaConnect.strings.failed + ' HTTP ' + xhr.status, true);
                button.prop('disabled', false);
            });
    });

    $('#vernissaria-disconnect').on('click', function () {
        if (!window.confirm(vernissariaConnect.strings.confirmDisconnect)) {
            return;
        }
        $.post(vernissariaConnect.ajaxUrl, {
            action: 'vernissaria_disconnect',
            nonce: vernissariaConnect.nonce
        }).always(function () {
            window.location.reload();
        });
    });
});
