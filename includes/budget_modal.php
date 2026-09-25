<?php
/**
 * PESO - Set Budget modal (overall or per-category)
 * Requires $CATEGORIES from includes/categories.php and
 * $periodType / $periodStart / $periodEnd / $periodLabel from the including page.
 */
?>
<div class="modal fade auth-modal" id="budgetModal" tabindex="-1" aria-labelledby="budgetModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content auth-card auth-modal-content">
      <button type="button" class="btn-close auth-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-body">

        <h1 id="budgetModalLabel">Set Budget</h1>
        <p class="auth-subtitle">Set your spending limit for <?php echo htmlspecialchars($periodLabel ?? 'this period'); ?>.</p>

        <div class="auth-alert auth-alert-danger mb-3" id="budgetFormError" hidden></div>

        <form class="auth-form" id="budgetForm" novalidate>
          <input type="hidden" name="period_type" value="<?php echo htmlspecialchars($periodType ?? 'month'); ?>">
          <input type="hidden" name="period_start" value="<?php echo htmlspecialchars($periodStart ?? ''); ?>">
          <input type="hidden" name="period_end" value="<?php echo htmlspecialchars($periodEnd ?? ''); ?>">

          <div class="mb-3">
            <label class="form-label" for="budgetCategory">Applies To</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-tag"></i></span>
              <select class="form-control" id="budgetCategory" name="category">
                <option value="">Overall Budget</option>
                <?php foreach ($CATEGORIES as $cat): ?>
                  <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label" for="budgetAmount">Budget Amount</label>
            <div class="input-group">
              <span class="input-group-text">&#8369;</span>
              <input type="number" step="0.01" min="0.01" class="form-control" id="budgetAmount" name="amount" placeholder="0.00" required>
            </div>
          </div>

          <button type="submit" class="btn-forest-block" id="budgetSubmitBtn">Save Budget</button>
        </form>

      </div>
    </div>
  </div>
</div>
