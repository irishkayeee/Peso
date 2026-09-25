<?php
/**
 * PESO - Shared Confirmation & Success modals
 * Controlled via assets/js/ui-modals.js (pesoConfirm / pesoSuccess)
 */
?>
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content ui-modal-content text-center">
      <div class="ui-modal-icon warn"><i class="bi bi-question-lg"></i></div>
      <h5 id="confirmModalTitle">Are you sure?</h5>
      <p id="confirmModalMessage" class="text-muted small mb-0"></p>
      <div class="d-flex gap-2 justify-content-center mt-3">
        <button type="button" class="btn btn-outline-forest" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-forest" id="confirmModalConfirmBtn">Yes, Continue</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content ui-modal-content text-center">
      <div class="ui-modal-icon success"><i class="bi bi-check-lg"></i></div>
      <h5 id="successModalTitle">Success!</h5>
      <p id="successModalMessage" class="text-muted small mb-0"></p>
      <button type="button" class="btn btn-forest mt-3" data-bs-dismiss="modal">Done</button>
    </div>
  </div>
</div>
