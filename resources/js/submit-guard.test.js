import assert from 'node:assert/strict';
import { test } from 'node:test';
import { guardSubmission } from './submit-guard.js';

class FakeEventTarget {
    listeners = {};

    addEventListener(type, listener) {
        (this.listeners[type] ??= []).push(listener);
    }

    dispatch(type, event = {}) {
        const dispatched = { defaultPrevented: false, preventDefault() { this.defaultPrevented = true; }, ...event };
        (this.listeners[type] ?? []).forEach((listener) => listener(dispatched));

        return dispatched;
    }
}

function fakeForm() {
    const button = {
        disabled: false,
        attributes: {},
        setAttribute(name, value) { this.attributes[name] = value; },
        removeAttribute(name) { delete this.attributes[name]; },
    };
    const label = { textContent: 'Demander à être rappelé' };
    const form = new FakeEventTarget();
    form.dataset = {};
    form.querySelector = (selector) => ({ '[type="submit"]': button, '[data-submit-label]': label })[selector] ?? null;

    return { form, button, label, win: new FakeEventTarget() };
}

test('submitting locks the button and announces the busy state', () => {
    const { form, button, label, win } = fakeForm();
    guardSubmission(form, { win });

    const event = form.dispatch('submit');

    assert.equal(event.defaultPrevented, false);
    assert.equal(button.disabled, true);
    assert.equal(button.attributes['aria-busy'], 'true');
    assert.equal(label.textContent, 'Envoi en cours…');
});

test('a second submit while the first is in flight is blocked', () => {
    const { form, win } = fakeForm();
    guardSubmission(form, { win });

    form.dispatch('submit');
    const second = form.dispatch('submit');

    assert.equal(second.defaultPrevented, true);
});

test('the form is untouched until the browser fires submit (native validation passed)', () => {
    const { form, button, label, win } = fakeForm();
    guardSubmission(form, { win });

    // An invalid form makes the browser fire "invalid" on fields, never "submit".
    form.dispatch('invalid');

    assert.equal(button.disabled, false);
    assert.equal(label.textContent, 'Demander à être rappelé');
});

test('returning through the back/forward cache restores the button', () => {
    const { form, button, label, win } = fakeForm();
    guardSubmission(form, { win });
    form.dispatch('submit');

    win.dispatch('pageshow', { persisted: true });

    assert.equal(button.disabled, false);
    assert.equal(button.attributes['aria-busy'], undefined);
    assert.equal(label.textContent, 'Demander à être rappelé');
    assert.equal(form.dispatch('submit').defaultPrevented, false);
});

test('the returned reset restores the idle state', () => {
    const { form, button, win } = fakeForm();
    const reset = guardSubmission(form, { win });
    form.dispatch('submit');

    reset();

    assert.equal(button.disabled, false);
});
