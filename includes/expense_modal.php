<?php
/**
 * PESO - Add / Edit Expense modal
 * Requires $CATEGORIES and $PAYMENT_METHODS from includes/categories.php
 */
?>
<div class="modal fade auth-modal" id="expenseModal" tabindex="-1" aria-labelledby="expenseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content auth-card auth-modal-content">
      <button type="button" class="btn-close auth-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-body">

        <h1 id="expenseModalLabel">Add Expense</h1>
        <p class="auth-subtitle">Record a new transaction.</p>

        <div class="auth-alert auth-alert-danger mb-3" id="expenseFormError" hidden></div>

        <form class="auth-form" id="expenseForm" novalidate>
          <input type="hidden" id="expenseId" name="id" value="">

          <div class="mb-3">
            <label class="form-label" for="expenseAmount">Amount</label>
            <div class="input-group">
              <span class="input-group-text">&#8369;</span>
              <input type="number" step="0.01" min="0.01" class="form-control" id="expenseAmount" name="amount" placeholder="0.00" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label" for="expenseCategory">Category</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-tag"></i></span>
              <select class="form-control" id="expenseCategory" name="category" required>
                <?php foreach ($CATEGORIES as $cat): ?>
                  <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label" for="expenseDate">Date</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
              <input type="date" class="form-control" id="expenseDate" name="expense_date" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label" for="expensePaymentMethod">Payment Method</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-credit-card"></i></span>
              <select class="form-control" id="expensePaymentMethod" name="payment_method" required>
                <?php foreach ($PAYMENT_METHODS as $method): ?>
                  <option value="<?php echo htmlspecialchars($method); ?>"><?php echo htmlspecialchars($method); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label" for="expensePeriodType">Applies To</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-calendar-range"></i></span>
              <select class="form-control" id="expensePeriodType" name="period_type" required>
                <option value="day">Day Budget</option>
                <option value="week">Week Budget</option>
                <option value="month" selected>Month Budget</option>
              </select>
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label" for="expenseDescription">Description <span class="opacity-50">(optional)</span></label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-pencil"></i></span>
              <input type="text" class="form-control" id="expenseDescription" name="description" placeholder="e.g. Jollibee lunch" maxlength="255">
            </div>
          </div>

          <button type="submit" class="btn-forest-block" id="expenseSubmitBtn">Save Expense</button>
        </form>

      </div>
    </div>
  </div>
</div>
