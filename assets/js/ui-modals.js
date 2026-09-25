/**
 * PESO - Shared confirm / success modal helpers
 * Requires includes/ui_modals.php to be present on the page.
 */

function pesoConfirm(message, onConfirm, title, options) {
  var modalEl = document.getElementById('confirmModal');
  if (!modalEl) {
    if (window.confirm(message)) onConfirm();
    return;
  }

  document.getElementById('confirmModalTitle').textContent = title || 'Are you sure?';
  document.getElementById('confirmModalMessage').textContent = message;

  var oldBtn = document.getElementById('confirmModalConfirmBtn');
  var btn = oldBtn.cloneNode(true);
  oldBtn.parentNode.replaceChild(btn, oldBtn);

  var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  var keepOpen = options && options.keepOpen;

  btn.addEventListener('click', function () {
    if (!keepOpen) modal.hide();
    onConfirm();
  });

  modal.show();
}

function pesoSuccess(message, title, onClose) {
  var modalEl = document.getElementById('successModal');
  if (!modalEl) {
    if (onClose) onClose();
    return;
  }

  document.getElementById('successModalTitle').textContent = title || 'Success!';
  document.getElementById('successModalMessage').textContent = message;

  var modal = bootstrap.Modal.getOrCreateInstance(modalEl);

  if (onClose) {
    modalEl.addEventListener('hidden.bs.modal', onClose, { once: true });
  }

  modal.show();
}
