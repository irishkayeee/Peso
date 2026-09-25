document.addEventListener('DOMContentLoaded', function () {
  var modalEl = document.getElementById('forgotPasswordModal');
  if (!modalEl) return;

  var step1 = document.getElementById('fpStep1');
  var step2 = document.getElementById('fpStep2');
  var step1Error = document.getElementById('fpStep1Error');
  var step2Error = document.getElementById('fpStep2Error');
  var step2Success = document.getElementById('fpStep2Success');
  var emailInput = document.getElementById('fpEmail');
  var sentEmailLabel = document.getElementById('fpSentEmail');
  var sendOtpBtn = document.getElementById('fpSendOtpBtn');
  var resendBtn = document.getElementById('fpResendBtn');
  var resetBtn = document.getElementById('fpResetBtn');
  var timerLabel = document.getElementById('fpTimer');
  var otpDigits = Array.prototype.slice.call(document.querySelectorAll('.otp-digit'));
  var newPasswordInput = document.getElementById('fpNewPassword');
  var confirmPasswordInput = document.getElementById('fpConfirmPassword');

  var countdownInterval = null;
  var currentEmail = '';

  function showError(el, message) {
    el.textContent = message;
    el.hidden = false;
  }

  function hideError(el) {
    el.hidden = true;
    el.textContent = '';
  }

  function resetToStep1() {
    step1.hidden = false;
    step2.hidden = true;
    hideError(step1Error);
    hideError(step2Error);
    step2Success.hidden = true;
    pesoButtonLoading(sendOtpBtn, false);
    pesoButtonLoading(resendBtn, false);
    pesoButtonLoading(resetBtn, false);
    otpDigits.forEach(function (d) { d.value = ''; });
    newPasswordInput.value = '';
    confirmPasswordInput.value = '';
    if (countdownInterval) {
      clearInterval(countdownInterval);
      countdownInterval = null;
    }
  }

  modalEl.addEventListener('hidden.bs.modal', function () {
    resetToStep1();
    emailInput.value = '';
  });

  function startCountdown(seconds) {
    var remaining = seconds;
    resendBtn.disabled = true;
    timerLabel.classList.remove('expired');
    updateTimerLabel(remaining);

    if (countdownInterval) clearInterval(countdownInterval);
    countdownInterval = setInterval(function () {
      remaining--;
      if (remaining <= 0) {
        clearInterval(countdownInterval);
        countdownInterval = null;
        timerLabel.textContent = 'Code expired';
        timerLabel.classList.add('expired');
        resendBtn.disabled = false;
        return;
      }
      updateTimerLabel(remaining);
    }, 1000);
  }

  function updateTimerLabel(remaining) {
    var m = Math.floor(remaining / 60);
    var s = remaining % 60;
    timerLabel.textContent = 'Code expires in ' + m + ':' + (s < 10 ? '0' : '') + s;
  }

  function requestOtp(triggerBtn) {
    var email = emailInput.value.trim();
    hideError(step1Error);

    if (!email) {
      showError(step1Error, 'Please enter your email address.');
      return;
    }

    pesoButtonLoading(triggerBtn, true, 'Sending...');

    var formData = new FormData();
    formData.append('email', email);

    fetch('api/send_otp.php', { method: 'POST', body: formData })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        pesoButtonLoading(triggerBtn, false);

        if (!data.success) {
          showError(step1Error, data.message || 'Something went wrong. Please try again.');
          return;
        }

        currentEmail = email;
        sentEmailLabel.textContent = email;
        step1.hidden = true;
        step2.hidden = false;
        otpDigits[0].focus();
        startCountdown(data.expires_in || 180);
      })
      .catch(function () {
        pesoButtonLoading(triggerBtn, false);
        showError(step1Error, 'Network error. Please try again.');
      });
  }

  sendOtpBtn.addEventListener('click', function () {
    requestOtp(sendOtpBtn);
  });

  resendBtn.addEventListener('click', function () {
    if (resendBtn.disabled) return;
    hideError(step2Error);
    requestOtp(resendBtn);
  });

  // OTP digit auto-advance / backspace / paste
  otpDigits.forEach(function (digit, index) {
    digit.addEventListener('input', function () {
      digit.value = digit.value.replace(/[^0-9]/g, '').slice(0, 1);
      if (digit.value && index < otpDigits.length - 1) {
        otpDigits[index + 1].focus();
      }
    });

    digit.addEventListener('keydown', function (e) {
      if (e.key === 'Backspace' && !digit.value && index > 0) {
        otpDigits[index - 1].focus();
      }
    });

    digit.addEventListener('paste', function (e) {
      var pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
      if (!pasted) return;
      e.preventDefault();
      pasted.split('').slice(0, otpDigits.length).forEach(function (char, i) {
        otpDigits[i].value = char;
      });
      var next = Math.min(pasted.length, otpDigits.length - 1);
      otpDigits[next].focus();
    });
  });

  resetBtn.addEventListener('click', function () {
    hideError(step2Error);
    step2Success.hidden = true;

    var otp = otpDigits.map(function (d) { return d.value; }).join('');

    if (otp.length !== 6) {
      showError(step2Error, 'Please enter the full 6-digit code.');
      return;
    }

    if (newPasswordInput.value.length < 8) {
      showError(step2Error, 'Password must be at least 8 characters long.');
      return;
    }

    if (newPasswordInput.value !== confirmPasswordInput.value) {
      showError(step2Error, 'Passwords do not match.');
      return;
    }

    pesoButtonLoading(resetBtn, true, 'Resetting...');

    var formData = new FormData();
    formData.append('email', currentEmail);
    formData.append('otp', otp);
    formData.append('password', newPasswordInput.value);
    formData.append('confirm_password', confirmPasswordInput.value);

    fetch('api/reset_password.php', { method: 'POST', body: formData })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        pesoButtonLoading(resetBtn, false);

        if (!data.success) {
          showError(step2Error, data.message || 'Something went wrong. Please try again.');
          return;
        }

        if (countdownInterval) {
          clearInterval(countdownInterval);
          countdownInterval = null;
        }

        step2Success.textContent = data.message || 'Your password has been reset.';
        step2Success.hidden = false;

        setTimeout(function () {
          bootstrap.Modal.getOrCreateInstance(modalEl).hide();
          var loginModalEl = document.getElementById('loginModal');
          if (loginModalEl) {
            bootstrap.Modal.getOrCreateInstance(loginModalEl).show();
          }
        }, 1500);
      })
      .catch(function () {
        pesoButtonLoading(resetBtn, false);
        showError(step2Error, 'Network error. Please try again.');
      });
  });
});
