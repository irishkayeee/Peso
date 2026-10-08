document.addEventListener('DOMContentLoaded', function () {
  var modalEl = document.getElementById('budgetModal');
  if (!modalEl) return;

  var form = document.getElementById('budgetForm');
  var formError = document.getElementById('budgetFormError');
  var submitBtn = document.getElementById('budgetSubmitBtn');
  var categoryInput = document.getElementById('budgetCategory');
  var categoryLabel = document.getElementById('budgetCategoryLabel');
  var amountInput = document.getElementById('budgetAmount');

  modalEl.addEventListener('show.bs.modal', function (event) {
    var trigger = event.relatedTarget;
    formError.hidden = true;

    var category = (trigger && trigger.getAttribute('data-category')) || '';
    categoryInput.value = category;
    categoryLabel.value = category || 'Overall Budget';
    amountInput.value = (trigger && trigger.getAttribute('data-amount')) || '';
  });

  var maxBudget = parseFloat(amountInput.getAttribute('max')) || 1000000;

  // Block characters a number input would otherwise accept (e, +, -).
  amountInput.addEventListener('keydown', function (e) {
    if (['e', 'E', '+', '-'].indexOf(e.key) !== -1) e.preventDefault();
  });

  function validateAmount() {
    var raw = amountInput.value.trim();
    if (raw === '' || !/^\d+(\.\d{1,2})?$/.test(raw)) {
      return 'Please enter a valid budget amount (up to 2 decimal places).';
    }
    var value = parseFloat(raw);
    if (value <= 0) {
      return 'Budget amount must be greater than ₱0.00.';
    }
    if (value > maxBudget) {
      return 'Budget amount can\'t be more than ₱' + maxBudget.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '.';
    }
    return '';
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    formError.hidden = true;

    var amountError = validateAmount();
    if (amountError) {
      formError.textContent = amountError;
      formError.hidden = false;
      amountInput.focus();
      return;
    }

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
