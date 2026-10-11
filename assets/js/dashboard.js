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

  // Custom dropdown for period pickers: the native <select> stays in the DOM
  // (hidden) and still fires its own change handler when an option is picked.
  document.querySelectorAll('select.js-pretty-select').forEach(function (select) {
    var wrap = document.createElement('div');
    wrap.className = 'pretty-select';
    select.parentNode.insertBefore(wrap, select);
    wrap.appendChild(select);

    var trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'dash-select pretty-select-trigger';
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');
    wrap.appendChild(trigger);

    var menu = document.createElement('ul');
    menu.className = 'pretty-select-menu';
    menu.setAttribute('role', 'listbox');
    menu.tabIndex = -1;
    wrap.appendChild(menu);

    var items = Array.prototype.map.call(select.options, function (opt, i) {
      var li = document.createElement('li');
      li.className = 'pretty-select-option';
      li.setAttribute('role', 'option');
      li.dataset.index = i;
      li.innerHTML = '<span></span><i class="bi bi-check2"></i>';
      li.firstChild.textContent = opt.text;
      menu.appendChild(li);
      return li;
    });

    var active = select.selectedIndex;

    // Build the trigger once; re-creating it on every render detaches the clicked
    // node, so the outside-click handler below thinks the click was outside and closes the menu.
    trigger.innerHTML = '<i class="bi bi-calendar3"></i><span></span><i class="bi bi-chevron-down pretty-select-caret"></i>';
    var triggerLabel = trigger.children[1];

    function render() {
      triggerLabel.textContent = select.options[select.selectedIndex].text;
      items.forEach(function (li, i) {
        li.classList.toggle('is-selected', i === select.selectedIndex);
        li.classList.toggle('is-active', i === active);
        li.setAttribute('aria-selected', i === select.selectedIndex ? 'true' : 'false');
      });
    }

    function open() {
      active = select.selectedIndex;
      wrap.classList.add('open');
      trigger.setAttribute('aria-expanded', 'true');
      render();
      items[active].scrollIntoView({ block: 'nearest' });
      menu.focus();
    }

    function close(refocus) {
      wrap.classList.remove('open');
      trigger.setAttribute('aria-expanded', 'false');
      if (refocus) trigger.focus();
    }

    function choose(i) {
      close(true);
      if (i === select.selectedIndex) return;
      select.selectedIndex = i;
      render();
      select.dispatchEvent(new Event('change', { bubbles: true }));
      if (select.form && select.dataset.autoSubmit === 'true') {
        select.form.submit();
      }
    }

    trigger.addEventListener('click', function () {
      wrap.classList.contains('open') ? close(false) : open();
    });

    trigger.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        open();
      }
    });

    menu.addEventListener('click', function (e) {
      var li = e.target.closest('.pretty-select-option');
      if (li) choose(Number(li.dataset.index));
    });

    menu.addEventListener('mousemove', function (e) {
      var li = e.target.closest('.pretty-select-option');
      if (li && Number(li.dataset.index) !== active) {
        active = Number(li.dataset.index);
        render();
      }
    });

    menu.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        active = Math.min(items.length - 1, Math.max(0, active + (e.key === 'ArrowDown' ? 1 : -1)));
        render();
        items[active].scrollIntoView({ block: 'nearest' });
      } else if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        choose(active);
      } else if (e.key === 'Escape' || e.key === 'Tab') {
        close(e.key === 'Escape');
      }
    });

    document.addEventListener('click', function (e) {
      if (!wrap.contains(e.target)) close(false);
    });

    render();
  });

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
