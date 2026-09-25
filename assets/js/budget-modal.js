document.addEventListener('DOMContentLoaded', function () {
  var modalEl = document.getElementById('budgetModal');
  if (!modalEl) return;

  var form = document.getElementById('budgetForm');
  var formError = document.getElementById('budgetFormError');
  var submitBtn = document.getElementById('budgetSubmitBtn');
  var categorySelect = document.getElementById('budgetCategory');
  var amountInput = document.getElementById('budgetAmount');

  modalEl.addEventListener('show.bs.modal', function (event) {
    var trigger = event.relatedTarget;
    formError.hidden = true;

    if (trigger && trigger.hasAttribute('data-category')) {
      categorySelect.value = trigger.getAttribute('data-category') || '';
      amountInput.value = trigger.getAttribute('data-amount') || '';
    } else {
      form.reset();
    }
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    formError.hidden = true;
    pesoButtonLoading(submitBtn, true, 'Saving...');

    fetch('api/save_budget.php', { method: 'POST', body: new FormData(form) })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        pesoButtonLoading(submitBtn, false);

        if (!data.success) {
          formError.textContent = data.message || 'Something went wrong. Please try again.';
          formError.hidden = false;
          return;
        }

        bootstrap.Modal.getOrCreateInstance(modalEl).hide();
        pesoSuccess('Your budget has been saved.', 'Budget Saved!', function () {
          window.location.reload();
        });
      })
      .catch(function () {
        pesoButtonLoading(submitBtn, false);
        formError.textContent = 'Network error. Please try again.';
        formError.hidden = false;
      });
  });
});
