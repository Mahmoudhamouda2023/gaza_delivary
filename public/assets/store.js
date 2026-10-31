'use strict';
document.querySelectorAll('[data-zone-select]').forEach(zone => {
  const target = document.querySelector(`[data-neighborhood-select="${zone.dataset.zoneSelect}"]`);
  if (!target) return;
  const refresh = () => {
    [...target.options].forEach(option => {
      if (!option.value) return;
      const visible = option.dataset.zone === zone.value;
      option.hidden = !visible;
      option.disabled = !visible;
      if (!visible && option.selected) target.value = '';
    });
  };
  zone.addEventListener('change', refresh);
  refresh();
});
const schedule = document.querySelector('[data-schedule-field]');
if (schedule) {
  const refresh = () => {
    const enabled = document.querySelector('[name="delivery_mode"]:checked')?.value === 'scheduled';
    schedule.hidden = !enabled;
    const input = schedule.querySelector('input');
    input.disabled = !enabled;
    input.required = enabled;
  };
  document.querySelectorAll('[name="delivery_mode"]').forEach(radio => radio.addEventListener('change', refresh));
  refresh();
}
const transition = document.querySelector('[data-transition-select]');

if (transition) {
  const form = transition.closest('form');
  const payment = form.querySelector('[data-delivery-payment]');
  const note = form.querySelector('[data-transition-note]');

  const refresh = () => {
    const delivered = transition.value === 'delivered';
    const needsReason = ['failed', 'cancelled', 'returned']
      .includes(transition.value);

    if (payment) {
      payment.hidden = !delivered;
      payment.querySelectorAll('input,select').forEach(input => {
        input.disabled = !delivered;
      });
    }

    if (note) {
      note.hidden = !needsReason;

      const input = note.querySelector('[name="note"]');
      input.disabled = !needsReason;
      input.required = needsReason;
    }
  };

  transition.addEventListener('change', refresh);
  refresh();
}
if (transition) {
  const payment = document.querySelector('[data-delivery-payment]');
  const refresh = () => {
    if (!payment) return;
    const visible = transition.value === 'delivered';
    payment.hidden = !visible;
    payment.querySelectorAll('input,select').forEach(input => input.disabled = !visible);
  };
  transition.addEventListener('change', refresh);
  refresh();
}
document.querySelectorAll('.payment-fields').forEach(fields => {
  const method = fields.querySelector('[name="payment_method"]');
  const recipient = fields.querySelector('[name="payment_recipient"]');
  const refresh = () => {
    const digital = method.value !== 'cash';
    if (!digital) recipient.value = 'captain';
    recipient.querySelector('option[value="merchant"]').disabled = !digital;
    fields.querySelectorAll('[name="payment_account"],[name="payment_reference"]').forEach(input => {
      input.required = digital;
      input.closest('.field').hidden = !digital;
    });
  };
  method.addEventListener('change', refresh);
  refresh();
});
