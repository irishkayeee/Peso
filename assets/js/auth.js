document.addEventListener('DOMContentLoaded', function () {

  // Show a coin loading state on the submit button while the auth form posts
  var loginSubmitBtn = document.getElementById('loginSubmitBtn');
  var loginForm = loginSubmitBtn ? loginSubmitBtn.closest('form') : null;
  if (loginForm) {
    loginForm.addEventListener('submit', function () {
      pesoButtonLoading(loginSubmitBtn, true, 'Logging in...');
    });
  }

  var registerSubmitBtn = document.getElementById('registerSubmitBtn');
  var registerForm = registerSubmitBtn ? registerSubmitBtn.closest('form') : null;
  if (registerForm) {
    registerForm.addEventListener('submit', function () {
      pesoButtonLoading(registerSubmitBtn, true, 'Creating account...');
    });
  }

  // Password show/hide toggle
  document.querySelectorAll('.toggle-password').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = document.getElementById(btn.getAttribute('data-target'));
      if (!input) return;

      var icon = btn.querySelector('i');
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
      }
    });
  });

  // Password strength meter (sign up page)
  var password = document.getElementById('registerPassword');
  var strengthBar = document.getElementById('strengthBar');

  if (password && strengthBar) {
    password.addEventListener('input', function () {
      var value = password.value;
      var score = 0;

      if (value.length >= 8) score++;
      if (/[A-Z]/.test(value)) score++;
      if (/[0-9]/.test(value)) score++;
      if (/[^A-Za-z0-9]/.test(value)) score++;

      var colors = ['#D96C6C', '#D96C6C', '#D9A441', '#7FAF9B', '#245C4A'];
      var widths = ['10%', '30%', '55%', '80%', '100%'];

      strengthBar.style.width = value.length ? widths[score] : '0%';
      strengthBar.style.backgroundColor = colors[score];
    });
  }

});
