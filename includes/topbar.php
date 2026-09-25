<?php
/**
 * PESO - Dashboard topbar
 * Expects $topbarTitle, $topbarSubtitle, optional $topbarIcon (bootstrap icon class)
 */
$topbarIcon = $topbarIcon ?? 'bi-grid-1x2-fill';
?>
<div class="dash-topbar">
  <div class="d-flex align-items-start gap-3">
    <button type="button" class="dash-menu-toggle" id="dashMenuToggle" aria-label="Open menu">
      <i class="bi bi-list"></i>
    </button>
    <div class="dash-topbar-title">
      <h1><i class="bi <?php echo $topbarIcon; ?>"></i> <?php echo htmlspecialchars($topbarTitle ?? ''); ?></h1>
      <?php if (!empty($topbarSubtitle)): ?>
        <p><?php echo htmlspecialchars($topbarSubtitle); ?></p>
      <?php endif; ?>
    </div>
  </div>
</div>
