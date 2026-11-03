/* Regression for ACF Page Settings opened after the old 60-second timeout.
 * Runs in an isolated fake browser and does not access a WordPress site.
 */
'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname, '../assets/editor-native.js'), 'utf8');

function scenario(withObserver, initialFailure = '') {
    let mounted = false;
    let interval = null;
    let timerActive = true;
    let observerCallback = null;
    let observerDisconnected = false;
    let click = null;
    let status = '';
    let posts = [];
    const field = { key: 'field_heae_qa_text', id: 'heae_acf_field_heae_qa_text', type: 'text', editable: true };
    const values = { [field.id]: 'Existing value' };
    if (initialFailure === 'missing') delete values[field.id];
    if (initialFailure === 'throws') values[field.id] = {
        toJSON() { throw new TypeError('sentinel not user data'); }
    };
    const document = {
        body: {},
        querySelector() { return mounted ? {} : null; },
    };
    function $(selector) {
        if (typeof selector === 'function') { selector(); return; }
        return {
            length: 1,
            first() { return this; },
            find() { return this; },
            append() { return this; },
            text(text) { if (text !== undefined) status = text; return this; },
            toggleClass() { return this; },
            prop() { return this; },
            on(event, selector, fn) {
                if (event === 'click' && selector.includes('heae_acf_save_action')) click = fn;
                return this;
            },
        };
    }
    $.ajax = function (request) {
        posts.push(request.data.acf);
        return {
            done(callback) { callback({ success: true, data: { message: 'Saved by QA' } }); return this; },
            fail() { return this; },
            always(callback) { callback(); return this; },
        };
    };
    class FakeObserver {
        constructor(callback) { observerCallback = callback; }
        observe() {}
        disconnect() { observerDisconnected = true; }
    }
    const elementor = { settings: { page: { model: { get(key) { return values[key]; } } } } };
    const window = {
        HEAE: {
            postId: 654,
            nonce: 'isolated-test',
            ajax: '/wp-admin/admin-ajax.php',
            fields: [field],
            saving: 'Saving',
            saved: 'Saved',
            error: 'Failed',
        },
        elementor,
        setInterval(callback) { interval = callback; return 41; },
        clearInterval(id) { assert.equal(id, 41); timerActive = false; },
    };
    if (withObserver) window.MutationObserver = FakeObserver;
    const sandbox = { window, document, HEAE: window.HEAE, elementor, jQuery: $, console: { warn() {} } };
    vm.runInNewContext(source, sandbox);
    assert.equal(typeof click, 'function', 'Delegated save click must be registered');
    assert.equal(typeof interval, 'function', 'Late-opening baseline callback must remain active');

    // 700 x 100ms exceeded the previous 60-second cutoff. The harness uses
    // no real clock; callbacks simulate a slowly opened Page Settings panel.
    for (let i = 0; i < 700; i++) if (timerActive) interval();
    assert.equal(timerActive, true, 'Baseline monitoring must not time out before panel opens');

    mounted = true;
    assert.equal(typeof window.HEAE.readiness, 'function', 'Privacy-safe runtime diagnostic is available');
    if (withObserver) {
        assert.equal(typeof observerCallback, 'function', 'MutationObserver should be installed');
        observerCallback();
    } else {
        interval();
    }
    if (initialFailure) {
        const state = window.HEAE.readiness();
        assert.equal(state.captured, false, 'Invalid initial value cannot become the save baseline');
        assert.equal(state.phase, initialFailure === 'missing'
            ? 'undefined-value:text'
            : 'conversion-error:text:TypeError');
        assert.equal(timerActive, true, 'Monitor must remain active while model is not ready');
        assert.doesNotMatch(JSON.stringify(state), /Existing value|sentinel not user data/,
            'Diagnostics must not disclose saved user values or errors');
        values[field.id] = 'Existing value';
        interval();
    }
    assert.equal(window.HEAE.readiness().phase, 'ready');
    assert.equal(window.HEAE.readiness().editableFieldCount, 1);
    assert.equal(timerActive, false, 'Baseline polling must stop after capture');
    if (withObserver) assert.equal(observerDisconnected, true, 'Observer must disconnect after capture');

    click({ preventDefault() {}, stopPropagation() {} });
    assert.equal(posts.length, 0, 'Unchanged field must not be sent to ACF');
    assert.match(status, /No ACF changes to save/i);

    values[field.id] = 'Browser edit after late opening';
    click({ preventDefault() {}, stopPropagation() {} });
    assert.equal(posts.length, 1, 'Changed field must be sent once');
    assert.equal(posts[0][field.key], 'Browser edit after late opening');

    click({ preventDefault() {}, stopPropagation() {} });
    assert.equal(posts.length, 1, 'Saved baseline must not resend unchanged field');
    return withObserver ? 'observer' : 'timer-fallback';
}
for (const mode of [false, true]) {
    const test = scenario(mode);
    console.log('PASS=LATE_ACF_PANEL_BASELINE_' + test.toUpperCase());
}
for (const issue of ['missing', 'throws']) {
    scenario(true, issue);
    console.log('PASS=ACF_READINESS_RECOVERS_FROM_' + issue.toUpperCase());
}
console.log('PASS=LATE_OPEN_NOOP_CHANGE_SAVE_NO_DUPLICATE_POST');
