/* global HEAE, acf, jQuery */
(function ($) {
    'use strict';

    function initializePanels(root) {
        var $panels = $(root).find('.heae-native-panel').addBack('.heae-native-panel');
        $panels.each(function () {
            var panel = this;
            if (panel.dataset.heaeInitialized === '1') {
                return;
            }
            // Elementor can re-render document controls when switching tabs.
            // ACF's native media, select2 and repeater controls need an append
            // notification for each newly attached DOM subtree.
            panel.dataset.heaeInitialized = '1';
            if (window.acf && typeof window.acf.doAction === 'function') {
                try {
                    window.acf.doAction('append', $(panel));
                } catch (error) {
                    panel.dataset.heaeInitialized = '';
                    console.warn('[Hashe ACF] Could not initialize ACF controls', error);
                }
            }
        });
    }

    function showStatus($panel, text, error) {
        var $status = $panel.find('.heae-form-status').first();
        $status.text(text).toggleClass('heae-error', !!error);
    }

    function handleSave(event) {
        event.preventDefault();
        event.stopPropagation();
        var $button = $(this);
        var $panel = $button.closest('.heae-native-panel');
        var $form = $panel.find('.heae-acf-form').first();
        var postId = parseInt($panel.attr('data-post-id'), 10) || 0;
        var nonce = $panel.attr('data-heae-nonce');
        if (!postId || !nonce || !$form.length) {
            showStatus($panel, HEAE.error, true);
            return;
        }
        if ($button.prop('disabled')) {
            return;
        }

        // ACF's own input names are serialized exactly, including nested
        // repeater subfields and the native hidden values for checkboxes.
        var serialized = $form.serializeArray().filter(function (item) {
            return /^acf\[/.test(item.name);
        });
        if (!serialized.length) {
            showStatus($panel, HEAE.error, true);
            return;
        }

        $button.prop('disabled', true).addClass('heae-is-saving');
        showStatus($panel, HEAE.saving, false);

        $.ajax({
            url: HEAE.ajax,
            type: 'POST',
            dataType: 'json',
            data: $.param(serialized) + '&' + $.param({
                action: HEAE.action,
                post_id: postId,
                nonce: nonce
            })
        }).done(function (response) {
            if (response && response.success) {
                showStatus($panel, (response.data && response.data.message) || HEAE.saved, false);
                // Do not trigger Elementor's document-save workflow; ACF
                // fields have already been saved through their own API.
                return;
            }
            showStatus($panel, (response && response.data && response.data.message) || HEAE.error, true);
        }).fail(function (xhr) {
            var data = xhr && xhr.responseJSON && xhr.responseJSON.data;
            if (data && data.errors && data.errors.length) {
                showStatus($panel, data.errors.map(function (e) { return e.message; }).join(' '), true);
            } else {
                showStatus($panel, (data && data.message) || HEAE.error, true);
            }
        }).always(function () {
            $button.prop('disabled', false).removeClass('heae-is-saving');
        });
    }

    $(document).on('click', '.heae-native-panel .heae-save', handleSave);

    $(function () {
        initializePanels(document.body);
        var container = document.getElementById('elementor-panel') || document.body;
        if (window.MutationObserver) {
            var pending = false;
            var observer = new MutationObserver(function () {
                if (pending) { return; }
                pending = true;
                window.requestAnimationFrame(function () {
                    pending = false;
                    initializePanels(container);
                });
            });
            observer.observe(container, {childList: true, subtree: true});
        }
        // The editor may switch between panel views without inserting nodes.
        $(document).on('click', '[data-tab="heae_acf"], .elementor-panel-navigation-tab', function () {
            window.setTimeout(function () { initializePanels(container); }, 60);
        });
    });
})(jQuery);
