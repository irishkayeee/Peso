document.addEventListener('DOMContentLoaded', function () {

  // Mobile sidebar toggle
  var toggle = document.getElementById('dashMenuToggle');
  var sidebar = document.querySelector('.dash-sidebar');
  var backdrop = document.getElementById('dashSidebarBackdrop');

  if (toggle && sidebar && backdrop) {
    function closeSidebar() {
      sidebar.classList.remove('show');
      backdrop.classList.remove('show');
    }

    toggle.addEventListener('click', function () {
      sidebar.classList.add('show');
      backdrop.classList.add('show');
    });

    backdrop.addEventListener('click', closeSidebar);
  }

  // Clickable cards: navigate to data-href unless the click was on a link/button inside the card
  document.querySelectorAll('.js-card-link').forEach(function (card) {
    card.addEventListener('click', function (e) {
      if (e.target.closest('a, button')) return;
      var href = card.getAttribute('data-href');
      if (href) window.location.href = href;
    });
  });

  // Log out: confirm before navigating away
  document.querySelectorAll('.js-logout-link').forEach(function (link) {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      var href = link.getAttribute('href');
      pesoConfirm('Are you sure you want to log out?', function () {
        var modalEl = document.getElementById('confirmModal');
        var cancelBtn = modalEl ? modalEl.querySelector('[data-bs-dismiss="modal"]') : null;
        if (cancelBtn) cancelBtn.disabled = true;
        pesoButtonLoading(document.getElementById('confirmModalConfirmBtn'), true, 'Logging out...');
        window.location.href = href;
      }, 'Log Out', { keepOpen: true });
    });
  });

});

/**
 * Show a small dismissible toast-style alert at the top of the main content.
 */
function pesoFlash(message, type) {
  var main = document.querySelector('.dash-main');
  if (!main) return;

  var el = document.createElement('div');
  el.className = 'alert alert-' + (type === 'error' ? 'danger' : 'success') + ' py-2 px-3 mb-3';
  el.style.borderRadius = '10px';
  el.style.fontSize = '0.88rem';
  el.textContent = message;

  main.insertBefore(el, main.firstChild);
  setTimeout(function () {
    el.remove();
  }, 3500);
}
