<?php
/**
 * PESO - Personal Expense & Spending Overview
 * Landing Page
 */
session_start();

$pageTitle = "PESO - Personal Expense & Spending Overview";

$authModal = $_SESSION['auth_modal'] ?? null;
$authErrors = $_SESSION['auth_errors'] ?? [];
$authOld = $_SESSION['auth_old'] ?? [];
unset($_SESSION['auth_modal'], $_SESSION['auth_errors'], $_SESSION['auth_old']);

$oldEmail = htmlspecialchars($authOld['email'] ?? '');
$oldFirstName = htmlspecialchars($authOld['first_name'] ?? '');
$oldLastName = htmlspecialchars($authOld['last_name'] ?? '');

$showPasswordHint = false;
if ($authModal === 'register' && !empty($authErrors)) {
    foreach ($authErrors as $err) {
        if (stripos($err, 'password') !== false && stripos($err, 'match') === false) {
            $showPasswordHint = true;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $pageTitle; ?></title>

<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Caveat:wght@500;600&display=swap" rel="stylesheet">

<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<!-- Custom CSS -->
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>

<!-- ===================== NAVBAR ===================== -->
<nav class="navbar navbar-expand-lg peso-navbar sticky-top">
  <div class="container-fluid px-4 px-lg-5">
    <a class="navbar-brand d-flex align-items-center gap-2" href="#">
      <span class="logo-img-wrap">
        <img src="assets/img/peso_logo.png" alt="PESO Logo" class="logo-img">
      </span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#peosoNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="peosoNav">
      <ul class="navbar-nav ms-auto me-lg-4 my-3 my-lg-0">
        <li class="nav-item"><a class="nav-link active" href="#home">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
        <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
        <li class="nav-item"><a class="nav-link" href="#faqs">FAQs</a></li>
      </ul>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-forest" data-bs-toggle="modal" data-bs-target="#loginModal">Log In</button>
        <button type="button" class="btn btn-forest" data-bs-toggle="modal" data-bs-target="#registerModal">Sign Up</button>
      </div>
    </div>
  </div>
</nav>

<!-- ===================== HERO SECTION ===================== -->
<section class="hero-section" id="home">
  <div class="blob-decor"></div>
  <div class="blob-decor blob-dark"></div>

  <div class="container position-relative">
    <div class="row align-items-center">

      <!-- Left copy -->
      <div class="col-lg-6">
        <h1 class="hero-heading">
          Smarter Spending<br>
          for a Brighter <span class="script-font">Tomorrow</span>
        </h1>

        <p class="hero-text mb-4">
          PESO is a student-friendly financial analytics system that helps you
          record expenses, track your budget, and turn your spending into
          valuable insights.
        </p>

        <div class="row hero-features text-center">
          <div class="col-6 col-sm-3 hero-feature-item">
            <div class="hero-feature-icon"><i class="bi bi-shield-check"></i></div>
            <span>Track<br>Expenses</span>
          </div>
          <div class="col-6 col-sm-3 hero-feature-item">
            <div class="hero-feature-icon"><i class="bi bi-bar-chart-line"></i></div>
            <span>Monitor<br>Your Budget</span>
          </div>
          <div class="col-6 col-sm-3 hero-feature-item">
            <div class="hero-feature-icon"><i class="bi bi-lightbulb"></i></div>
            <span>Get Smart<br>Insights</span>
          </div>
          <div class="col-6 col-sm-3 hero-feature-item">
            <div class="hero-feature-icon"><i class="bi bi-bullseye"></i></div>
            <span>Reach<br>Your Goals</span>
          </div>
        </div>
      </div>

      <!-- Right illustration -->
      <div class="col-lg-6">
        <div class="hero-illustration">

          <!-- Piggy bank illustration -->
          <div class="piggy-img-wrap">
            <div class="piggy-speech" id="piggySpeech"></div>
            <img src="assets/img/piggybank.png" alt="Piggy Bank" class="piggy-img" id="piggyBank">
          </div>

          <!-- Handwritten note -->
          <div class="hand-note script-font">
            Your Money. Your Goals.<br>
            Our Priority. <i class="bi bi-heart-fill"></i>
          </div>

        </div>
      </div>

    </div>
  </div>
</section>

<!-- ===================== FEATURES SECTION ===================== -->
<section class="pt-5 pb-3 features-section" id="features">
  <div class="container py-4">

    <!-- About PESO -->
    <div class="row align-items-center justify-content-center g-5" id="about">
      <div class="col-lg-5 d-flex justify-content-center">
        <div class="about-img-wrap">
          <div class="about-speech" id="aboutSpeech"></div>
          <img src="assets/img/about_peso.png" alt="About PESO" class="about-img" id="aboutImg">
        </div>
      </div>
      <div class="col-lg-5">
        <span class="hero-badge">About PESO</span>
        <h2 class="fw-bold mt-2 mb-3" style="color: var(--forest-green);">
          Built by students, for students
        </h2>
        <p class="text-muted">
          PESO was created to help students take control of their finances without
          the complexity of traditional finance apps. We believe small, consistent
          steps lead to real financial confidence.
        </p>
        <ul class="list-unstyled mt-4">
          <li class="mb-3 d-flex align-items-start gap-2">
            <i class="bi bi-check-circle-fill mt-1" style="color: var(--forest-green);"></i>
            <span>Simple, student-friendly interface</span>
          </li>
          <li class="mb-3 d-flex align-items-start gap-2">
            <i class="bi bi-check-circle-fill mt-1" style="color: var(--forest-green);"></i>
            <span>Real-time budget and spending insights</span>
          </li>
          <li class="mb-3 d-flex align-items-start gap-2">
            <i class="bi bi-check-circle-fill mt-1" style="color: var(--forest-green);"></i>
            <span>Free to use, always</span>
          </li>
        </ul>
      </div>
    </div>

    <div class="text-center mb-5 mt-5 pt-4">
      <span class="hero-badge">Features</span>
      <h2 class="fw-bold mt-2" style="color: var(--forest-green);">Everything You Need for Better Money Habits</h2>
      <p class="text-muted mx-auto" style="max-width:560px;">
        From daily expenses to long-term goals, PESO gives you the tools to make smarter financial decisions.
      </p>
    </div>

    <div class="row g-4">
      <div class="col-6 col-md-4 col-lg-2">
        <div class="feature-item-sm">
          <div class="feature-icon-sm"><i class="bi bi-file-earmark-text"></i></div>
          <h6 class="fw-bold">Record &amp; Categorize Expenses</h6>
          <p class="text-muted small mb-0">Keep track of your spending with ease.</p>
        </div>
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <div class="feature-item-sm">
          <div class="feature-icon-sm"><i class="bi bi-wallet2"></i></div>
          <h6 class="fw-bold">Set Monthly Budgets</h6>
          <p class="text-muted small mb-0">Stay within your limits and avoid overspending.</p>
        </div>
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <div class="feature-item-sm">
          <div class="feature-icon-sm"><i class="bi bi-bar-chart-line"></i></div>
          <h6 class="fw-bold">Analyze Spending Patterns</h6>
          <p class="text-muted small mb-0">See where your money goes and find areas to improve.</p>
        </div>
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <div class="feature-item-sm">
          <div class="feature-icon-sm"><i class="bi bi-graph-up-arrow"></i></div>
          <h6 class="fw-bold">Track Trends &amp; Compare</h6>
          <p class="text-muted small mb-0">Turn past data into better decisions.</p>
        </div>
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <div class="feature-item-sm">
          <div class="feature-icon-sm"><i class="bi bi-bell"></i></div>
          <h6 class="fw-bold">Get Budget Alerts</h6>
          <p class="text-muted small mb-0">Be notified when you're close to your limit.</p>
        </div>
      </div>
      <div class="col-6 col-md-4 col-lg-2">
        <div class="feature-item-sm">
          <div class="feature-icon-sm"><i class="bi bi-lightbulb"></i></div>
          <h6 class="fw-bold">Receive Smart Insights</h6>
          <p class="text-muted small mb-0">Let PESO help you make smarter financial choices.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ===================== FAQ SECTION ===================== -->
<section class="pt-4 pb-5" id="faqs" style="background-color: var(--warm-cream);">
  <div class="container py-4">
    <div class="text-center mb-5">
      <span class="hero-badge">FAQs</span>
      <h2 class="fw-bold mt-2" style="color: var(--forest-green);">Frequently Asked Questions</h2>
    </div>

    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="accordion" id="faqAccordion">

          <div class="accordion-item faq-item mb-3 border-0 rounded-4 overflow-hidden">
            <h2 class="accordion-header">
              <button class="accordion-button fw-semibold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                Is PESO free to use?
              </button>
            </h2>
            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body text-muted">
                Yes! PESO is completely free for students to track expenses and manage their budget.
              </div>
            </div>
          </div>

          <div class="accordion-item faq-item mb-3 border-0 rounded-4 overflow-hidden">
            <h2 class="accordion-header">
              <button class="accordion-button fw-semibold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                Do I need to link a bank account?
              </button>
            </h2>
            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body text-muted">
                No, PESO works with manual expense entry so your financial data stays private and secure.
              </div>
            </div>
          </div>

          <div class="accordion-item faq-item mb-3 border-0 rounded-4 overflow-hidden">
            <h2 class="accordion-header">
              <button class="accordion-button fw-semibold collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                Can I set monthly budget goals?
              </button>
            </h2>
            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
              <div class="accordion-body text-muted">
                Absolutely. You can set budget limits per category and PESO will alert you as you approach them.
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <div class="text-center mt-5 pt-4">
      <h2 class="fw-bold" style="color: var(--forest-green);">Ready to Take Control of Your Money?</h2>
      <p class="text-muted mx-auto mb-4" style="max-width:560px;">
        Join students who are already building better habits with PESO &mdash; track, budget, and grow your savings one peso at a time.
      </p>
      <button type="button" class="btn btn-forest" data-bs-toggle="modal" data-bs-target="#registerModal">Get Started <i class="bi bi-arrow-right ms-1"></i></button>
    </div>
  </div>
</section>

<!-- ===================== FOOTER ===================== -->
<footer class="py-4" style="background-color: var(--forest-green-dark, #1a4436);">
  <div class="container text-center">
    <small class="text-white-50">
      &copy; <?php echo date("Y"); ?> PESO &mdash; Personal Expense &amp; Spending Overview. All rights reserved.
    </small>
  </div>
</footer>

<!-- ===================== LOG IN MODAL ===================== -->
<div class="modal fade auth-modal" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content auth-card auth-modal-content">
      <button type="button" class="btn-close auth-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-body">

        <div class="auth-mobile-logo mb-3">
          <img src="assets/img/peso_logo.png" alt="PESO Logo" class="logo-img">
        </div>

        <h1 id="loginModalLabel">Log in to PESO</h1>
        <p class="auth-subtitle">Welcome back! Please enter your details.</p>

        <?php if ($authModal === 'login' && !empty($authErrors)): ?>
          <div class="auth-alert auth-alert-danger mb-3">
            <ul class="mb-0 ps-3">
              <?php foreach ($authErrors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form class="auth-form" method="POST" action="login.php" novalidate>
          <div class="mb-3">
            <label class="form-label" for="loginEmail">Email Address</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-envelope"></i></span>
              <input type="email" class="form-control" id="loginEmail" name="email" placeholder="Enter your email" value="<?php echo $authModal === 'login' ? $oldEmail : ''; ?>" required>
            </div>
          </div>

          <div class="mb-2">
            <label class="form-label" for="loginPassword">Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock"></i></span>
              <input type="password" class="form-control" id="loginPassword" name="password" placeholder="Enter your password" required>
              <button class="toggle-password input-group-text" type="button" data-target="loginPassword">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>

          <div class="d-flex justify-content-end mb-4 mt-3">
            <a href="#" class="auth-forgot-link" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal" data-bs-dismiss="modal">Forgot password?</a>
          </div>

          <button type="submit" class="btn-forest-block" id="loginSubmitBtn">Log In</button>
        </form>

        <div class="auth-divider">or</div>

        <p class="auth-switch">
          Don't have an account?
          <a href="#" data-bs-toggle="modal" data-bs-target="#registerModal">Sign Up</a>
        </p>

      </div>
    </div>
  </div>
</div>

<!-- ===================== SIGN UP MODAL ===================== -->
<div class="modal fade auth-modal" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content auth-card auth-modal-content">
      <button type="button" class="btn-close auth-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-body">

        <div class="auth-mobile-logo mb-3">
          <img src="assets/img/peso_logo.png" alt="PESO Logo" class="logo-img">
        </div>

        <h1 id="registerModalLabel">Create your account</h1>
        <p class="auth-subtitle">Let's set up your PESO account and start saving smarter.</p>

        <?php if ($authModal === 'register' && !empty($authErrors)): ?>
          <div class="auth-alert auth-alert-danger mb-3">
            <ul class="mb-0 ps-3">
              <?php foreach ($authErrors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form class="auth-form" method="POST" action="register.php" novalidate>
          <div class="row g-3 mb-3">
            <div class="col-sm-6">
              <label class="form-label" for="registerFirstName">First Name</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" class="form-control" id="registerFirstName" name="first_name" placeholder="Juan" value="<?php echo $authModal === 'register' ? $oldFirstName : ''; ?>" required>
              </div>
            </div>
            <div class="col-sm-6">
              <label class="form-label" for="registerLastName">Last Name</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" class="form-control" id="registerLastName" name="last_name" placeholder="Dela Cruz" value="<?php echo $authModal === 'register' ? $oldLastName : ''; ?>" required>
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label" for="registerEmail">Email Address</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-envelope"></i></span>
              <input type="email" class="form-control" id="registerEmail" name="email" placeholder="Enter your email" value="<?php echo $authModal === 'register' ? $oldEmail : ''; ?>" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label" for="registerPassword">Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock"></i></span>
              <input type="password" class="form-control" id="registerPassword" name="password" placeholder="At least 8 characters" minlength="8" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$" title="Password must be at least 8 characters and include an uppercase letter, a lowercase letter, a number, and a special character." required>
              <button class="toggle-password input-group-text" type="button" data-target="registerPassword">
                <i class="bi bi-eye"></i>
              </button>
            </div>
            <div class="password-strength">
              <div class="password-strength-bar" id="strengthBar"></div>
            </div>
            <div class="form-text" style="color: var(--muted-red);" <?php echo $showPasswordHint ? '' : 'hidden'; ?>>Must be at least 8 characters and include uppercase, lowercase, a number, and a special character.</div>
          </div>

          <div class="mb-4">
            <label class="form-label" for="registerConfirmPassword">Confirm Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
              <input type="password" class="form-control" id="registerConfirmPassword" name="confirm_password" placeholder="Re-enter your password" minlength="8" required>
              <button class="toggle-password input-group-text" type="button" data-target="registerConfirmPassword">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>

          <button type="submit" class="btn-forest-block" id="registerSubmitBtn">Create Account</button>
        </form>

        <div class="auth-divider">or</div>

        <p class="auth-switch">
          Already have an account?
          <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal">Log In</a>
        </p>

      </div>
    </div>
  </div>
</div>

<!-- ===================== FORGOT PASSWORD MODAL ===================== -->
<div class="modal fade auth-modal" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content auth-card auth-modal-content">
      <button type="button" class="btn-close auth-modal-close" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-body">

        <div class="auth-mobile-logo mb-3">
          <img src="assets/img/peso_logo.png" alt="PESO Logo" class="logo-img">
        </div>

        <!-- Step 1: request code -->
        <div id="fpStep1">
          <h1 id="forgotPasswordModalLabel">Forgot your password?</h1>
          <p class="auth-subtitle">Enter your email and we'll send you a 6-digit code to reset it.</p>

          <div class="auth-alert auth-alert-danger mb-3" id="fpStep1Error" hidden></div>

          <div class="mb-4">
            <label class="form-label" for="fpEmail">Email Address</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-envelope"></i></span>
              <input type="email" class="form-control" id="fpEmail" placeholder="Enter your email" required>
            </div>
          </div>

          <button type="button" class="btn-forest-block" id="fpSendOtpBtn">Send Code</button>

          <p class="auth-switch mt-3">
            <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-dismiss="modal">Back to Log In</a>
          </p>
        </div>

        <!-- Step 2: enter OTP + new password -->
        <div id="fpStep2" hidden>
          <h1>Enter the code</h1>
          <p class="auth-subtitle">We sent a 6-digit code to <strong id="fpSentEmail"></strong>.</p>

          <div class="auth-alert auth-alert-danger mb-3" id="fpStep2Error" hidden></div>
          <div class="auth-alert auth-alert-success mb-3" id="fpStep2Success" hidden></div>

          <div class="otp-input-group mb-2">
            <input type="text" inputmode="numeric" maxlength="1" class="otp-digit" data-index="0">
            <input type="text" inputmode="numeric" maxlength="1" class="otp-digit" data-index="1">
            <input type="text" inputmode="numeric" maxlength="1" class="otp-digit" data-index="2">
            <input type="text" inputmode="numeric" maxlength="1" class="otp-digit" data-index="3">
            <input type="text" inputmode="numeric" maxlength="1" class="otp-digit" data-index="4">
            <input type="text" inputmode="numeric" maxlength="1" class="otp-digit" data-index="5">
          </div>

          <div class="fp-timer-row mb-4">
            <span id="fpTimer">Code expires in 03:00</span>
            <button type="button" class="fp-resend-btn" id="fpResendBtn" disabled>Resend code</button>
          </div>

          <div class="mb-3">
            <label class="form-label" for="fpNewPassword">New Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock"></i></span>
              <input type="password" class="form-control" id="fpNewPassword" placeholder="At least 8 characters" minlength="8">
              <button class="toggle-password input-group-text" type="button" data-target="fpNewPassword">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label" for="fpConfirmPassword">Confirm New Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
              <input type="password" class="form-control" id="fpConfirmPassword" placeholder="Re-enter new password" minlength="8">
              <button class="toggle-password input-group-text" type="button" data-target="fpConfirmPassword">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>

          <button type="button" class="btn-forest-block" id="fpResetBtn">Reset Password</button>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/loading.js"></script>
<script src="assets/js/main.js"></script>
<script src="assets/js/auth.js"></script>
<script src="assets/js/forgot-password.js"></script>

<?php if ($authModal === 'login' || $authModal === 'register'): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var modalEl = document.getElementById('<?php echo $authModal === "login" ? "loginModal" : "registerModal"; ?>');
  if (modalEl) {
    new bootstrap.Modal(modalEl).show();
  }
});
</script>
<?php endif; ?>

</body>
</html>
