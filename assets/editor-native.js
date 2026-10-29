/* global HEAE, jQuery, elementor */
(function ($) {
    'use strict';

    var busy = false;
    var baseline = null;

    function pageSettingsModel() {
        // Elementor's Page Settings controls are backed by this Backbone model.
        if (window.elementor && elementor.settings && elementor.settings.page &&
            elementor.settings.page.model && typeof elementor.settings.page.model.get === 'function') {
            return elementor.settings.page.model;
        }
        return null;
    }

    function normalizeModelValue(value) {
        if (value && typeof value.toJSON === 'function') {
            return value.toJSON();
        }
        return value;
    }

    function mediaId(value) {
        value = normalizeModelValue(value);
        return value && typeof value === 'object' ? (parseInt(value.id, 10) || 0) : (parseInt(value, 10) || 0);
    }

    function asAcfValue(field, value) {
        value = normalizeModelValue(value);
        var type = field.type;
        if (type === 'gallery') {
            if (!Array.isArray(value)) {
                return [];
            }
            return value.map(function (item) {
                item = normalizeModelValue(item) || {};
                return mediaId(item.heae_gallery_image);
            }).filter(function (id) {
                return id > 0;
            });
        }
        if (type === 'repeater') {
            if (!Array.isArray(value) || !value.length) {
                return '';
            }
            var rows = {};
            value.forEach(function (row, index) {
                row = normalizeModelValue(row) || {};
                var out = {};
                (field.sub_fields || []).forEach(function (sub) {
                    var entry = normalizeModelValue(row[sub.id]);
                    if (entry === undefined) {
                        entry = '';
                    }
                    if (sub.type === 'image' || sub.type === 'file') {
                        entry = mediaId(entry);
                    } else if (sub.type === 'true_false') {
                        entry = entry && entry !== '0' ? 1 : 0;
                    }
                    out[sub.key] = entry;
                });
                rows['row-' + index] = out;
            });
            return rows;
        }
        if (type === 'relationship') {
            if (!Array.isArray(value)) return [];
            return value.map(function (row) {
                row = normalizeModelValue(row) || {};
                return parseInt(row.heae_relationship_post, 10) || 0;
            }).filter(Boolean);
        }
        if (type === 'post_object' || type === 'taxonomy' || type === 'user') {
            if (Array.isArray(value)) return value.map(function (v) {return parseInt(v, 10) || 0;}).filter(Boolean);
            return parseInt(value, 10) || '';
        }
        if (type === 'page_link') {
            return value == null ? '' : value;
        }
        if (type === 'link') {
            var link = Array.isArray(value) && value.length ? normalizeModelValue(value[0]) : {};
            link = link || {};
            return {
                url: link.heae_link_url || '',
                title: link.heae_link_title || '',
                target: link.heae_link_target === 'yes' ? '_blank' : ''
            };
        }
        if (type === 'google_map') {
            var map = Array.isArray(value) && value.length ? normalizeModelValue(value[0]) : {};
            map = map || {};
            var original = field.original && typeof field.original === 'object' ? Object.assign({}, field.original) : {};
            var address = String(map.heae_map_address || '');
            var latitude = Number(map.heae_map_lat) || 0;
            var longitude = Number(map.heae_map_lng) || 0;
            if (!address && !latitude && !longitude) return '';
            var changed = original.address !== address ||
                Number(original.lat) !== latitude || Number(original.lng) !== longitude;
            if (changed) {
                // Old Google Place details no longer correspond to edited
                // coordinates/address; drop them, not the standard map keys.
                ['place_id', 'name', 'street_number', 'street_name', 'street_name_short',
                'city', 'state', 'state_short', 'post_code', 'country', 'country_short']
                    .forEach(function (key) { delete original[key]; });
            }
            return Object.assign(original, {
                address: address, lat: latitude, lng: longitude,
                zoom: parseInt(map.heae_map_zoom, 10) || 14
            });
        }
        if (type === 'icon_picker') {
            var icon = Array.isArray(value) && value.length ? normalizeModelValue(value[0]) : {};
            icon = icon || {};
            return {type: icon.heae_icon_type || 'dashicons', value: icon.heae_icon_value || ''};
        }
        if (type === 'group' || type === 'clone') {
            var composite = Array.isArray(value) && value.length ? normalizeModelValue(value[0]) : {};
            composite = composite || {};
            var result = {};
            (field.sub_fields || []).forEach(function (sub) {
                var subvalue = normalizeModelValue(composite[sub.id]);
                if (subvalue === undefined) subvalue = '';
                result[sub.key] = asAcfValue(sub, subvalue);
            });
            return result;
        }
        if (type === 'flexible_content') {
            if (!Array.isArray(value) || !value.length) return '';
            var flexible = {};
            value.forEach(function (row, index) {
                row = normalizeModelValue(row) || {};
                var chosen = row.heae_fc_layout;
                var layoutFields = (field.layouts || {})[chosen];
                if (!layoutFields) return;
                var entry = {acf_fc_layout: chosen};
                layoutFields.forEach(function (sub) {
                    var v = normalizeModelValue(row[sub.id]);
                    if (v === undefined) v = '';
                    entry[sub.key] = asAcfValue(sub, v);
                });
                flexible['row-' + index] = entry;
            });
            return flexible;
        }
        if (type === 'time_picker' && typeof value === 'string') {
            // Elementor's time picker exposes HH:mm; ACF stores HH:mm:ss.
            return /^\d{1,2}:\d{2}$/.test(value) ? value + ':00' : value;
        }
        if (type === 'date_picker' && typeof value === 'string') {
            // ACF Date Picker stores Ymd, although Elementor displays YYYY-MM-DD.
            return /^\d{4}-\d{2}-\d{2}$/.test(value) ? value.replace(/-/g, '') : value;
        }
        if (type === 'date_time_picker' && typeof value === 'string') {
            return /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/.test(value) ? value + ':00' : value;
        }
        if (type === 'image' || type === 'file') {
            return mediaId(value);
        }
        if (type === 'true_false') {
            return value && value !== '0' ? 1 : 0;
        }
        if ((type === 'checkbox' || type === 'select') && Array.isArray(value)) {
            return value;
        }
        return value == null ? '' : value;
    }

    // Capture Elementor's initial form model exactly as rendered. Comparing
    // current values to this baseline prevents a Save click from rewriting
    // unrelated ACF fields (notably Password, Time, Google Map and Clone).
    function readSnapshot() {
        var model = pageSettingsModel();
        if (!model || !window.HEAE || !Array.isArray(HEAE.fields)) return null;
        var snapshot = {};
        for (var i = 0; i < HEAE.fields.length; i++) {
            var field = HEAE.fields[i];
            if (!field.editable) continue;
            var value = normalizeModelValue(model.get(field.id));
            if (value === undefined) return null;
            snapshot[field.key] = JSON.stringify(asAcfValue(field, value));
        }
        return snapshot;
    }

    function captureBaseline() {
        if (baseline) return true;
        // Wait for actual Page Settings controls to mount; Elementor builds
        // its Backbone model asynchronously before the ACF tab is visible.
        if (!document.querySelector('#elementor-panel .elementor-control-heae_acf_save_action button')) return false;
        var snapshot = readSnapshot();
        if (!snapshot) return false;
        baseline = snapshot;
        return true;
    }

    $(function () {
        var tries = 0;
        var timer = window.setInterval(function () {
            tries++;
            if (captureBaseline() || tries >= 600) {
                window.clearInterval(timer);
            }
        }, 100);
    });

    function status(message, isError) {
        var $section = $('.elementor-control-heae_acf_save_action').first();
        if (!$section.length) {
            return;
        }
        var $status = $section.find('.heae-save-status');
        if (!$status.length) {
            $status = $('<div class="heae-save-status" role="status" aria-live="polite"></div>');
            $section.append($status);
        }
        $status.text(message).toggleClass('heae-save-error', !!isError);
    }

    function saveAcf(event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        if (busy) {
            return;
        }
        if (!window.HEAE || !HEAE.postId || !HEAE.nonce || !Array.isArray(HEAE.fields)) {
            status('ACF editing session is not initialized. Reload the Elementor editor.', true);
            return;
        }
        var model = pageSettingsModel();
        if (!model) {
            status('Elementor Page Settings model is unavailable. Nothing was saved.', true);
            return;
        }
        if (!baseline) {
            status('ACF controls are still initializing. Nothing was saved; try again.', true);
            return;
        }
        var payload = {};
        var missing = [];
        HEAE.fields.forEach(function (field) {
            // Read the full Page Settings model. Collapsed Elementor sections
            // unmount their DOM controls, so checking DOM presence loses data.
            // Unsupported types are explicitly marked read-only in the schema.
            if (!field.editable) {
                return;
            }
            var value = normalizeModelValue(model.get(field.id));
            if (value === undefined) {
                missing.push(field.id);
                return;
            }
            if (field.type === 'repeater' && (field.sub_fields || []).some(function (sub) {
                return sub.type === 'repeater';
            })) {
                missing.push(field.id);
                return;
            }
            var normalized = asAcfValue(field, value);
            if (JSON.stringify(normalized) !== baseline[field.key]) {
                payload[field.key] = normalized;
            }
        });
        if (missing.length) {
            status('Could not read ' + missing.length + ' Elementor control value(s). Nothing was saved.', true);
            console.warn('[Hashe ACF Editor] Cannot read values:', missing);
            return;
        }
        if (!Object.keys(payload).length) {
            status('No ACF changes to save.', false);
            return;
        }

        busy = true;
        var $button = $('.elementor-control-heae_acf_save_action button').first();
        $button.prop('disabled', true);
        status(HEAE.saving, false);
        $.ajax({
            url: HEAE.ajax,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'heae_save_acf',
                post_id: HEAE.postId,
                nonce: HEAE.nonce,
                acf: payload
            }
        }).done(function (response) {
            if (response && response.success) {
                Object.keys(payload).forEach(function (key) {
                    baseline[key] = JSON.stringify(payload[key]);
                });
                status(response.data && response.data.message ? response.data.message : HEAE.saved, false);
            } else {
                status(response && response.data && response.data.message ? response.data.message : HEAE.error, true);
            }
        }).fail(function (xhr) {
            var data = xhr && xhr.responseJSON && xhr.responseJSON.data;
            if (data && Array.isArray(data.errors) && data.errors.length) {
                status(data.errors.map(function (error) {
                    return error.message || 'Validation failed';
                }).join(' '), true);
            } else {
                status(data && data.message ? data.message : HEAE.error, true);
            }
        }).always(function () {
            busy = false;
            $button.prop('disabled', false);
        });
    }

    // The button itself is an Elementor BUTTON control, not part of any
    // ACF-rendered form. Delegation handles Elementor's rerendering.
    $(document).on('click', '.elementor-control-heae_acf_save_action button', saveAcf);
})(jQuery);
