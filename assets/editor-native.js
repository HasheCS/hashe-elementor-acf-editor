/* global HEAE, acf, jQuery */
(function ($) {
    'use strict';

    function decorateAcfTab() {
        if (!HEAE.acfLogo) {
            return;
        }
        // Elementor creates and destroys Page Settings tabs dynamically.
        // Decorate the existing tab node, never replace its label or click
        // handlers, and use the official icon from the installed ACF plugin.
        document.querySelectorAll(
            '#elementor-panel [data-tab="heae_acf"], #elementor-panel .elementor-panel-navigation-tab'
        ).forEach(function (tab) {
            if (tab.getAttribute('data-tab') !== 'heae_acf' &&
                (tab.textContent || '').trim() !== 'ACF') {
                return;
            }
            tab.classList.add('heae-acf-navigation-tab');
            if (tab.querySelector('.heae-acf-tab-logo')) {
                return;
            }
            var img = document.createElement('img');
            img.className = 'heae-acf-tab-logo';
            img.src = HEAE.acfLogo;
            img.alt = '';
            img.setAttribute('aria-hidden', 'true');
            img.width = 18;
            img.height = 18;
            tab.insertBefore(img, tab.firstChild);
        });
    }

    function initializePanels(root) {
        var $panels = $(root).find('.heae-native-panel').addBack('.heae-native-panel');
        $panels.each(function () {
            var panel = this;
            if (panel.dataset.heaeInitialized === '1') {
                return;
            }
            var api = window.acf;
            if (!api || typeof api.getFields !== 'function' ||
                typeof api.getField !== 'function' ||
                typeof api.doAction !== 'function') {
                // ACF scripts can finish initializing after Elementor has
                // rendered its first controls stack. Retry while bootstrapping.
                return;
            }
            try {
                var $panel = $(panel);
                // ACF's append action handles fields inserted dynamically.
                api.doAction('append', $panel);
                // CRITICAL: Elementor RAW_HTML bypasses ACF's normal wp-admin
                // form readiness scan. Creating Field objects explicitly binds
                // their native delegated click handlers (image picker,
                // repeater Add Row, switch, Select2, etc).
                var fields = api.getFields({ parent: $panel });
                $panel.find('.acf-field[data-type="image"], .acf-field[data-type="repeater"]')
                    .not('.acf-clone .acf-field')
                    .each(function () {
                        api.getField($(this));
                    });
                if ($panel.find('.acf-field[data-type]').length && !fields.length) {
                    throw new Error('ACF did not initialize the field models');
                }
                panel.dataset.heaeInitialized = '1';
                panel.dataset.heaeFieldCount = String(fields.length);
            } catch (error) {
                panel.dataset.heaeInitialized = '';
                console.warn('[Hashe ACF Editor] ACF field initialization failed', error);
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
                // ACF normally clears its own beforeunload dirty-state when
                // its core editor form submits. Our AJAX save must do that
                // explicitly, without touching Elementor's unsaved state.
                if (window.acf && window.acf.unload &&
                    typeof window.acf.unload.reset === 'function') {
                    window.acf.unload.reset();
                }
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
        decorateAcfTab();
        initializePanels(document.body);
        var container = document.getElementById('elementor-panel') || document.body;
        // First editor paint can precede ACF's registration. Ensure both
        // field initialization and tab decoration eventually run even if
        // Elementor doesn't mutate the tab DOM again.
        var initialAttempts = 0;
        var bootstrap = window.setInterval(function () {
            initialAttempts++;
            decorateAcfTab();
            initializePanels(container);
            if (initialAttempts >= 24 ||
                ($(container).find('.heae-native-panel').length &&
                 $(container).find('.heae-native-panel').first().attr('data-heae-initialized') === '1')) {
                window.clearInterval(bootstrap);
            }
        }, 250);
        if (window.MutationObserver) {
            var pending = false;
            var observer = new MutationObserver(function () {
                if (pending) { return; }
                pending = true;
                window.requestAnimationFrame(function () {
                    pending = false;
                    decorateAcfTab();
                    initializePanels(container);
                });
            });
            observer.observe(container, {childList: true, subtree: true});
        }
        // The editor may switch between panel views without inserting nodes.
        $(document).on('click', '[data-tab="heae_acf"], .elementor-panel-navigation-tab', function () {
            window.setTimeout(function () { decorateAcfTab(); initializePanels(container); }, 60);
        });
    });
})(jQuery);
