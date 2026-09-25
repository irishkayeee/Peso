<?php
/**
 * PESO - Profile / Settings
 */
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/db.php';

$userId = (int) $_SESSION['user_id'];
$pageTitle = "Profile - PESO";
$activePage = 'profile';

$stmt = $pdo->prepare('SELECT full_name, email, created_at, notify_budget_alerts, profile_picture FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

$initials = '';
foreach (explode(' ', trim($user['full_name'])) as $part) {
    if ($part !== '') $initials .= strtoupper($part[0]);
}
$initials = substr($initials, 0, 2);

$topbarIcon = 'bi-person-circle';
$topbarTitle = 'Profile & Settings';
$topbarSubtitle = 'Manage your account information and preferences.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $pageTitle; ?></title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Caveat:wght@500;600&display=swap" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/auth.css">
<link rel="stylesheet" href="assets/css/dashboard.css">
<style>
  .profile-avatar-lg {
    width: 76px; height: 76px; border-radius: 50%;
    background-color: rgba(127, 175, 155, 0.25); color: var(--forest-green);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.6rem; font-weight: 700; flex-shrink: 0;
    object-fit: cover;
  }
  .profile-avatar-picker { position: relative; flex-shrink: 0; }
  .profile-avatar-edit-btn {
    position: absolute; bottom: -2px; right: -2px;
    width: 28px; height: 28px; border-radius: 50%;
    background-color: var(--forest-green); color: #fff; border: 2px solid #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.75rem; cursor: pointer; padding: 0;
  }
  .profile-avatar-edit-btn:hover { background-color: var(--forest-green-dark, var(--forest-green)); }
  .toggle-switch { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
  .form-switch .form-check-input { width: 2.6em; height: 1.4em; cursor: pointer; }
  .form-switch .form-check-input:checked { background-color: var(--forest-green); border-color: var(--forest-green); }
</style>
</head>
<body class="dash-body">

<div class="dash-wrapper">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <main class="dash-main">
    <?php include __DIR__ . '/includes/topbar.php'; ?>

    <div class="row g-3">
      <div class="col-12">
        <div class="dash-card mb-3">
          <div class="d-flex align-items-center gap-3 mb-4">
            <div class="profile-avatar-picker" id="avatarPicker">
              <?php if (!empty($user['profile_picture'])): ?>
                <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>?v=<?php echo time(); ?>" alt="Profile picture" class="profile-avatar-lg" id="avatarImg">
              <?php else: ?>
                <span class="profile-avatar-lg" id="avatarImg"><?php echo htmlspecialchars($initials ?: 'U'); ?></span>
              <?php endif; ?>
              <button type="button" class="profile-avatar-edit-btn" id="avatarEditBtn" aria-label="Change profile picture">
                <i class="bi bi-camera-fill"></i>
              </button>
              <input type="file" id="avatarInput" accept="image/png, image/jpeg, image/webp" hidden>
            </div>
            <div>
              <div class="fw-bold" style="color:var(--forest-green); font-size:1.1rem;"><?php echo htmlspecialchars($user['full_name']); ?></div>
              <div class="text-muted small">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></div>
              <div class="auth-alert auth-alert-danger mt-2 mb-0 py-1 px-2" id="avatarError" hidden style="font-size:0.8rem;"></div>
            </div>
          </div>

          <h2 class="mb-3" style="font-size:1rem; font-weight:700; color:var(--charcoal);">Profile Information</h2>

          <div class="auth-alert auth-alert-danger mb-3" id="profileError" hidden></div>

          <form class="auth-form" id="profileForm" novalidate>
            <div class="mb-3">
              <label class="form-label" for="profileFullName">Full Name</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" class="form-control" id="profileFullName" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
              </div>
            </div>

            <div class="mb-4">
              <label class="form-label" for="profileEmail">Email Address</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" class="form-control" id="profileEmail" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
              </div>
            </div>

            <button type="submit" class="btn-forest-block" id="profileSubmitBtn" style="width:auto; padding-left:1.8rem; padding-right:1.8rem;">Save Changes</button>
          </form>
        </div>
      </div>

      <div class="col-12">
        <div class="dash-card mb-3">
          <h2 class="mb-3" style="font-size:1rem; font-weight:700; color:var(--charcoal);">Change Password</h2>

          <div class="auth-alert auth-alert-danger mb-3" id="passwordError" hidden></div>

          <form class="auth-form" id="passwordForm" novalidate>
            <div class="mb-3">
              <label class="form-label" for="currentPassword">Current Password</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" class="form-control" id="currentPassword" name="current_password" required>
                <button class="toggle-password input-group-text" type="button" data-target="currentPassword"><i class="bi bi-eye"></i></button>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label" for="newPassword">New Password</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                <input type="password" class="form-control" id="newPassword" name="new_password" minlength="8" required>
                <button class="toggle-password input-group-text" type="button" data-target="newPassword"><i class="bi bi-eye"></i></button>
              </div>
            </div>

            <div class="mb-4">
              <label class="form-label" for="confirmNewPassword">Confirm New Password</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                <input type="password" class="form-control" id="confirmNewPassword" name="confirm_password" minlength="8" required>
                <button class="toggle-password input-group-text" type="button" data-target="confirmNewPassword"><i class="bi bi-eye"></i></button>
              </div>
            </div>

            <button type="submit" class="btn-forest-block" id="passwordSubmitBtn" style="width:auto; padding-left:1.8rem; padding-right:1.8rem;">Update Password</button>
          </form>
        </div>
      </div>
    </div>
  </main>
</div>

<?php include __DIR__ . '/includes/ui_modals.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/loading.js"></script>
<script src="assets/js/dashboard.js"></script>
<script src="assets/js/ui-modals.js"></script>
<script src="assets/js/auth.js"></script>
<script src="assets/js/profile.js"></script>
</body>
</html>
