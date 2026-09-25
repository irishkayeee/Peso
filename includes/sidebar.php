<?php
/**
 * PESO - Dashboard sidebar navigation
 * Expects $activePage to be set (dashboard|expenses|budgets|insights|profile)
 */
$activePage = $activePage ?? '';

$sidebarProfilePicture = null;
if (!empty($_SESSION['user_id']) && isset($pdo)) {
    $stmt = $pdo->prepare('SELECT profile_picture FROM users WHERE id = ?');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $sidebarProfilePicture = $stmt->fetchColumn() ?: null;
}

$sidebarInitials = '';
foreach (explode(' ', trim($_SESSION['full_name'] ?? '')) as $part) {
    if ($part !== '') $sidebarInitials .= strtoupper($part[0]);
}
$sidebarInitials = substr($sidebarInitials, 0, 2);

$navItems = [
    'dashboard' => ['label' => 'Dashboard', 'icon' => 'bi-grid-1x2-fill', 'href' => 'dashboard.php'],
    'expenses'  => ['label' => 'Expenses',  'icon' => 'bi-receipt',       'href' => 'expenses.php'],
    'budgets'   => ['label' => 'Budgets',   'icon' => 'bi-piggy-bank',    'href' => 'budgets.php'],
    'profile'   => ['label' => 'Profile',   'icon' => 'bi-person-circle', 'href' => 'profile.php'],
];
?>
<aside class="dash-sidebar">
  <div>
    <a href="dashboard.php" class="dash-sidebar-logo">
      <span class="logo-img-wrap logo-img-wrap-full">
        <img src="assets/img/peso_logo.png" alt="PESO Logo" class="logo-img">
      </span>
      <span class="logo-img-wrap logo-img-wrap-icon">
        <img src="assets/img/peso_icon.png" alt="PESO" class="logo-icon-img">
      </span>
    </a>

    <a href="profile.php" class="dash-sidebar-user">
      <span class="dash-sidebar-user-avatar">
        <?php if ($sidebarProfilePicture): ?>
          <img src="<?php echo htmlspecialchars($sidebarProfilePicture); ?>" alt="">
        <?php else: ?>
          <?php echo htmlspecialchars($sidebarInitials ?: 'U'); ?>
        <?php endif; ?>
      </span>
      <span class="dash-sidebar-user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?></span>
    </a>

    <hr class="dash-sidebar-divider">

    <nav class="dash-nav">
      <?php foreach ($navItems as $key => $item): ?>
        <a href="<?php echo $item['href']; ?>" class="dash-nav-link <?php echo $activePage === $key ? 'active' : ''; ?>" data-tour="nav-<?php echo $key; ?>">
          <i class="bi <?php echo $item['icon']; ?>"></i>
          <span><?php echo $item['label']; ?></span>
        </a>
      <?php endforeach; ?>
    </nav>

    <a href="logout.php" class="dash-sidebar-logout js-logout-link">
      <i class="bi bi-box-arrow-right"></i>
      <span>Log Out</span>
    </a>
  </div>
</aside>
<div class="dash-sidebar-backdrop" id="dashSidebarBackdrop"></div>
