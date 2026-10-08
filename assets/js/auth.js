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

  // Live password rule checklist + strength bar (change password on profile).
  // Rules match the sign up regex in register.php.
  var PASSWORD_RULES = {
    length: function (v) { return v.length >= 8; },
    upper: function (v) { return /[A-Z]/.test(v); },
    lower: function (v) { return /[a-z]/.test(v); },
    number: function (v) { return /\d/.test(v); },
    special: function (v) { return /[^A-Za-z0-9]/.test(v); }
  };

  document.querySelectorAll('.password-rules[data-rules-for]').forEach(function (list) {
    var input = document.getElementById(list.getAttribute('data-rules-for'));
    if (!input) return;
    var bar = document.querySelector('.password-strength-bar[data-strength-for="' + input.id + '"]');

    function update() {
      var value = input.value;
      var passed = 0;

      list.querySelectorAll('li[data-rule]').forEach(function (li) {
        var ok = PASSWORD_RULES[li.getAttribute('data-rule')](value);
        if (ok) passed++;
        li.classList.toggle('is-met', ok);
        li.querySelector('i').className = 'bi ' + (ok ? 'bi-check-circle-fill' : 'bi-circle');
      });

      if (bar) {
        var colors = ['#D96C6C', '#D96C6C', '#D96C6C', '#D9A441', '#7FAF9B', '#245C4A'];
        bar.style.width = value.length ? (passed / 5 * 100) + '%' : '0%';
        bar.style.backgroundColor = colors[passed];
      }
    }

    input.addEventListener('input', update);
    // Clear the ticks when the form is reset after a successful change
    if (input.form) input.form.addEventListener('reset', function () { setTimeout(update, 0); });
    update();
  });

});
