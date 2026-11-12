(( $ ) => {
    'use strict';
    $(function () {
        const $toggles = $('.currency-toggle');
        if (!$toggles.length) return;
        $toggles.on('change', function () {
            const $toggle = $(this);
            const previous = !$toggle.prop('checked');
            $toggle.prop('disabled', true);
            $.ajax({
                url: $toggle.data('url'), method: 'PATCH', dataType: 'json',
                data: { field: $toggle.data('field'), value: $toggle.prop('checked') ? 1 : 0 },
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            }).done((data) => {
                $toggles.filter('[data-field="is_main"]').prop('checked', Boolean(data.currency.is_main));
                $toggles.filter('[data-field="is_active"]').filter(`[data-url="${$toggle.data('url')}"]`).prop('checked', Boolean(data.currency.is_active));
            }).fail((xhr) => {
                $toggle.prop('checked', previous);
                const message = xhr.responseJSON?.errors ? Object.values(xhr.responseJSON.errors).flat()[0] : window.SupplyFlowCurrencies.messages.error;
                $('<div>', { class: 'alert alert-danger', text: message }).prependTo('.card').delay(5000).fadeOut(200, function () { $(this).remove(); });
            }).always(() => $toggle.prop('disabled', false));
        });
    });
})(window.jQuery);
