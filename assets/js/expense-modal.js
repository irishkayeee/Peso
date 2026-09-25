document.addEventListener('DOMContentLoaded', function () {
  var modalEl = document.getElementById('expenseModal');
  if (!modalEl) return;

  var form = document.getElementById('expenseForm');
  var formError = document.getElementById('expenseFormError');
  var titleEl = document.getElementById('expenseModalLabel');
  var submitBtn = document.getElementById('expenseSubmitBtn');

  var idInput = document.getElementById('expenseId');
  var amountInput = document.getElementById('expenseAmount');
  var categoryInput = document.getElementById('expenseCategory');
  var dateInput = document.getElementById('expenseDate');
  var paymentInput = document.getElementById('expensePaymentMethod');
  var periodTypeInput = document.getElementById('expensePeriodType');
  var descriptionInput = document.getElementById('expenseDescription');

  function todayStr() {
    var d = new Date();
    var m = String(d.getMonth() + 1).padStart(2, '0');
    var day = String(d.getDate()).padStart(2, '0');
    return d.getFullYear() + '-' + m + '-' + day;
  }

  // ---- Budget gate: block the form before submit once a set budget
  // (category-specific or overall) is already used up for the selected
  // Applies-To period, using the snapshot the page embedded. ----
  var originalAmount = 0;
  var originalCategory = null;
  var originalPeriodType = null;

  function pesoFmt(v) {
    return '₱' + v.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function budgetGateMessage() {
    var snapshot = window.PESO_BUDGET_SNAPSHOT || {};
    var category = categoryInput.value;
    var periodType = periodTypeInput.value;
    var periodData = snapshot[periodType] || { categories: {}, overall: { budget: 0, spent: 0 } };
    var catData = periodData.categories[category] || { budget: 0, spent: 0 };
    var overallData = periodData.overall || { budget: 0, spent: 0 };

    var isSamePeriod = !!idInput.value && originalPeriodType === periodType;
    var catSpent = catData.spent - (isSamePeriod && originalCategory === category ? originalAmount : 0);
    var overallSpent = overallData.spent - (isSamePeriod ? originalAmount : 0);

    if (catData.budget > 0 && catSpent >= catData.budget) {
      return 'Your ' + category + ' budget is already used up for this period (' + pesoFmt(Math.max(catData.budget - catSpent, 0)) + ' left). Raise the budget or remove an expense before adding more.';
    }
    if (overallData.budget > 0 && overallSpent >= overallData.budget) {
      var periodLabel = { day: 'daily', week: 'weekly', month: 'monthly' }[periodType] || periodType;
      return 'Your overall ' + periodLabel + ' budget is already used up for this period (' + pesoFmt(Math.max(overallData.budget - overallSpent, 0)) + ' left). Raise the budget or remove an expense before adding more.';
    }
    return null;
  }

  function refreshBudgetGate() {
    var message = budgetGateMessage();

    if (message) {
      formError.textContent = message;
      formError.hidden = false;
      amountInput.setAttribute('disabled', 'disabled');
      submitBtn.setAttribute('disabled', 'disabled');
    } else {
      formError.hidden = true;
      amountInput.removeAttribute('disabled');
      submitBtn.removeAttribute('disabled');
    }
  }

  [categoryInput, periodTypeInput].forEach(function (el) {
    el.addEventListener('change', refreshBudgetGate);
  });

  modalEl.addEventListener('show.bs.modal', function (event) {
    var trigger = event.relatedTarget;
    formError.hidden = true;
    amountInput.removeAttribute('disabled');
    submitBtn.removeAttribute('disabled');

    if (trigger && trigger.classList.contains('js-edit-expense')) {
      titleEl.textContent = 'Edit Expense';
      idInput.value = trigger.getAttribute('data-id') || '';
      amountInput.value = trigger.getAttribute('data-amount') || '';
      categoryInput.value = trigger.getAttribute('data-category') || '';
      dateInput.value = trigger.getAttribute('data-date') || todayStr();
      paymentInput.value = trigger.getAttribute('data-payment_method') || 'Cash';
      periodTypeInput.value = trigger.getAttribute('data-period_type') || 'month';
      descriptionInput.value = trigger.getAttribute('data-description') || '';

      originalAmount = parseFloat(amountInput.value) || 0;
      originalCategory = categoryInput.value;
      originalPeriodType = periodTypeInput.value;
    } else {
      titleEl.textContent = 'Add Expense';
      form.reset();
      idInput.value = '';
      dateInput.value = todayStr();

      originalAmount = 0;
      originalCategory = null;
      originalPeriodType = null;
    }

    refreshBudgetGate();
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    var gateMessage = budgetGateMessage();
    if (gateMessage) {
      formError.textContent = gateMessage;
      formError.hidden = false;
      return;
    }

    formError.hidden = true;
    pesoButtonLoading(submitBtn, true, 'Saving...');

    fetch('api/save_expense.php', { method: 'POST', body: new FormData(form) })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        pesoButtonLoading(submitBtn, false);

        if (!data.success) {
          formError.textContent = data.message || 'Something went wrong. Please try again.';
          formError.hidden = false;
          return;
        }

        var wasEdit = !!idInput.value;
        bootstrap.Modal.getOrCreateInstance(modalEl).hide();
        pesoSuccess(
          wasEdit ? 'Your expense has been updated.' : 'Your expense has been added.',
          wasEdit ? 'Expense Updated!' : 'Expense Added!',
          function () { window.location.reload(); }
        );
      })
      .catch(function () {
        pesoButtonLoading(submitBtn, false);
        formError.textContent = 'Network error. Please try again.';
        formError.hidden = false;
      });
  });

  document.querySelectorAll('.js-delete-expense').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = btn.getAttribute('data-id');
      if (!id) return;

      pesoConfirm('Delete this expense? This cannot be undone.', function () {
        var formData = new FormData();
        formData.append('id', id);

        fetch('api/delete_expense.php', { method: 'POST', body: formData })
          .then(function (res) { return res.json(); })
          .then(function (data) {
            if (data.success) {
              pesoSuccess('The expense has been deleted.', 'Deleted!', function () {
                window.location.reload();
              });
            } else {
              pesoSuccess(data.message || 'Could not delete this expense.', 'Something went wrong');
            }
          })
          .catch(function () {
            pesoSuccess('Network error. Please try again.', 'Something went wrong');
          });
      }, 'Delete Expense');
    });
  });
});
