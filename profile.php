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

  /* Avatar picker modal */
  .avatar-modal-content { border: none; border-radius: 20px; padding: 0.5rem; }
  .avatar-options { display: grid; grid-template-columns: repeat(5, 1fr); gap: 0.6rem; }
  .avatar-option {
    display: flex; flex-direction: column; align-items: center; gap: 0.35rem;
    padding: 0.5rem 0.25rem; border: 2px solid transparent; border-radius: 16px;
    background: none; font-size: 0.72rem; font-weight: 600; color: var(--charcoal); white-space: nowrap;
    transition: transform 0.15s ease, border-color 0.15s ease, background-color 0.15s ease;
  }
  .avatar-option img { width: 100%; max-width: 64px; aspect-ratio: 1; border-radius: 50%; }
  .avatar-option:hover { transform: translateY(-3px); background-color: rgba(127, 175, 155, 0.1); }
  .avatar-option.is-selected { border-color: var(--forest-green); background-color: rgba(127, 175, 155, 0.14); color: var(--forest-green); }
  .avatar-option:disabled { opacity: 0.6; }
  .avatar-upload-divider { display: flex; align-items: center; gap: 0.75rem; margin: 1rem 0; color: rgba(38, 51, 46, 0.45); font-size: 0.8rem; }
  .avatar-upload-divider::before, .avatar-upload-divider::after { content: ""; flex: 1; height: 1px; background-color: rgba(38, 51, 46, 0.12); }
  @media (max-width: 420px) { .avatar-options { grid-template-columns: repeat(3, 1fr); } }
  .toggle-switch { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
  .form-switch .form-check-input { width: 2.6em; height: 1.4em; cursor: pointer; }
  .form-switch .form-check-input:checked { background-color: var(--forest-green); border-color: var(--forest-green); }
  @media (min-width: 992px) {
    /* Center the two cards in the space below the topbar */
    .profile-main { display: flex; flex-direction: column; min-height: 100vh; }
    .profile-row { margin-top: auto; margin-bottom: auto; padding-bottom: 1.6rem; }
  }
</style>
</head>
<body class="dash-body">

<div class="dash-wrapper">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <main class="dash-main profile-main">
    <?php include __DIR__ . '/includes/topbar.php'; ?>

    <div class="row g-3 profile-row">
      <div class="col-lg-6">
        <div class="dash-card h-100">
          <div class="d-flex flex-column align-items-center text-center gap-2 mb-4">
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
            <?php
              // Stored as one full_name; last word is treated as the last name.
              $nameParts = preg_split('/\s+/', trim($user['full_name']));
              $lastName = count($nameParts) > 1 ? array_pop($nameParts) : '';
              $firstName = implode(' ', $nameParts);
            ?>
            <div class="row g-3 mb-3">
              <div class="col-sm-6">
                <label class="form-label" for="profileFirstName">First Name</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-person"></i></span>
                  <input type="text" class="form-control" id="profileFirstName" name="first_name" value="<?php echo htmlspecialchars($firstName); ?>" maxlength="50" required>
                </div>
              </div>
              <div class="col-sm-6">
                <label class="form-label" for="profileLastName">Last Name</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-person"></i></span>
                  <input type="text" class="form-control" id="profileLastName" name="last_name" value="<?php echo htmlspecialchars($lastName); ?>" maxlength="50" required>
                </div>
              </div>
            </div>

            <div class="mb-4">
              <label class="form-label" for="profileEmail">Email Address</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" class="form-control" id="profileEmail" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
              </div>
            </div>

            <button type="submit" class="btn-forest-block" id="profileSubmitBtn" style="width:auto; padding:0.5rem 1.25rem; font-size:0.875rem;">Save Changes</button>
          </form>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="dash-card h-100">
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
              <div class="password-strength">
                <div class="password-strength-bar" data-strength-for="newPassword"></div>
              </div>
              <ul class="password-rules" data-rules-for="newPassword">
                <li data-rule="length"><i class="bi bi-circle"></i> At least 8 characters</li>
                <li data-rule="upper"><i class="bi bi-circle"></i> An uppercase letter (A–Z)</li>
                <li data-rule="lower"><i class="bi bi-circle"></i> A lowercase letter (a–z)</li>
                <li data-rule="number"><i class="bi bi-circle"></i> A number (0–9)</li>
                <li data-rule="special"><i class="bi bi-circle"></i> A special character (!@#$)</li>
              </ul>
            </div>

            <div class="mb-4">
              <label class="form-label" for="confirmNewPassword">Confirm New Password</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                <input type="password" class="form-control" id="confirmNewPassword" name="confirm_password" minlength="8" required>
                <button class="toggle-password input-group-text" type="button" data-target="confirmNewPassword"><i class="bi bi-eye"></i></button>
              </div>
            </div>

            <button type="submit" class="btn-forest-block" id="passwordSubmitBtn" style="width:auto; padding:0.5rem 1.25rem; font-size:0.875rem;">Update Password</button>
          </form>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- Avatar picker -->
<div class="modal fade" id="avatarModal" tabindex="-1" aria-labelledby="avatarModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content avatar-modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="avatarModalLabel" style="color:var(--forest-green);">Choose your avatar</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small mb-3">Pick a money buddy, or upload your own photo.</p>
        <div class="avatar-options">
          <?php foreach (['piggy' => 'Piggy Bank', 'coin' => 'Piso Coin', 'wallet' => 'Wallet', 'moneybag' => 'Money Bag', 'sprout' => 'Money Plant'] as $key => $label):
            $path = 'assets/img/avatars/' . $key . '.svg';
          ?>
            <button type="button" class="avatar-option <?php echo ($user['profile_picture'] ?? '') === $path ? 'is-selected' : ''; ?>" data-avatar="<?php echo $key; ?>" aria-label="<?php echo $label; ?>">
              <img src="<?php echo $path; ?>" alt="">
              <span><?php echo $label; ?></span>
            </button>
          <?php endforeach; ?>
        </div>
        <div class="auth-alert auth-alert-danger mt-3 mb-0 py-1 px-2" id="avatarModalError" hidden style="font-size:0.8rem;"></div>
        <div class="avatar-upload-divider"><span>or</span></div>
        <button type="button" class="btn btn-outline-forest w-100" id="avatarUploadBtn">
          <i class="bi bi-upload me-1"></i> Upload your own photo
        </button>
      </div>
    </div>
  </div>
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
