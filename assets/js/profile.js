document.addEventListener('DOMContentLoaded', function () {

  function handleForm(formId, url, errorId, btnId, successTitle, onSuccess) {
    var form = document.getElementById(formId);
    if (!form) return;

    var errorEl = document.getElementById(errorId);
    var btn = document.getElementById(btnId);

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      errorEl.hidden = true;
      pesoButtonLoading(btn, true, 'Saving...');

      fetch(url, { method: 'POST', body: new FormData(form) })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          pesoButtonLoading(btn, false);

          if (!data.success) {
            errorEl.textContent = data.message || 'Something went wrong. Please try again.';
            errorEl.hidden = false;
            return;
          }

          if (onSuccess) onSuccess(data);
          pesoSuccess(data.message || 'Saved.', successTitle);
        })
        .catch(function () {
          pesoButtonLoading(btn, false);
          errorEl.textContent = 'Network error. Please try again.';
          errorEl.hidden = false;
        });
    });
  }

  handleForm('profileForm', 'api/update_profile.php', 'profileError', 'profileSubmitBtn', 'Profile Updated!');

  handleForm('passwordForm', 'api/change_password.php', 'passwordError', 'passwordSubmitBtn', 'Password Changed!', function () {
    document.getElementById('passwordForm').reset();
  });

  var avatarEditBtn = document.getElementById('avatarEditBtn');
  var avatarInput = document.getElementById('avatarInput');
  var avatarError = document.getElementById('avatarError');

  if (avatarEditBtn && avatarInput) {
    avatarEditBtn.addEventListener('click', function () {
      avatarInput.click();
    });

    avatarInput.addEventListener('change', function () {
      var file = avatarInput.files[0];
      if (!file) return;

      if (avatarError) avatarError.hidden = true;

      var formData = new FormData();
      formData.append('avatar', file);

      fetch('api/upload_avatar.php', { method: 'POST', body: formData })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (!data.success) {
            if (avatarError) {
              avatarError.textContent = data.message || 'Could not upload image.';
              avatarError.hidden = false;
            }
            return;
          }

          var src = data.profile_picture + '?v=' + Date.now();

          var profileImg = document.getElementById('avatarImg');
          if (profileImg) {
            var newImg = document.createElement('img');
            newImg.src = src;
            newImg.alt = 'Profile picture';
            newImg.id = 'avatarImg';
            newImg.className = 'profile-avatar-lg';
            profileImg.replaceWith(newImg);
          }

          var topbarAvatar = document.getElementById('topbarAvatar');
          if (topbarAvatar) {
            topbarAvatar.innerHTML = '<img src="' + src + '" alt="">';
          }

          pesoSuccess(data.message || 'Profile picture updated.', 'Photo Updated!');
        })
        .catch(function () {
          if (avatarError) {
            avatarError.textContent = 'Network error. Please try again.';
            avatarError.hidden = false;
          }
        })
        .finally(function () {
          avatarInput.value = '';
        });
    });
  }

  var notifyToggle = document.getElementById('notifyToggle');
  if (notifyToggle) {
    notifyToggle.addEventListener('change', function () {
      var formData = new FormData();
      formData.append('enabled', notifyToggle.checked ? '1' : '0');
      fetch('api/update_notifications.php', { method: 'POST', body: formData });
    });
  }

});
