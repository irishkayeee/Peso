// Transactions notebook: show 5 rows per page and turn pages like a book.
document.addEventListener('DOMContentLoaded', function () {
  var pager = document.getElementById('txPager');
  if (!pager) return;

  var card = pager.closest('.notebook-card');
  var sheet = card.querySelector('.table-responsive');
  var rows = Array.prototype.slice.call(sheet.querySelectorAll('tbody tr'));
  var PER_PAGE = 5;
  var pageCount = Math.ceil(rows.length / PER_PAGE);
  var current = 1;
  var turning = false;
  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  if (pageCount <= 1) {
    pager.hidden = true;
    return;
  }

  function showRows(page) {
    rows.forEach(function (tr, i) {
      tr.hidden = i < (page - 1) * PER_PAGE || i >= page * PER_PAGE;
    });
  }

  function renderPager() {
    var first = (current - 1) * PER_PAGE + 1;
    var last = Math.min(current * PER_PAGE, rows.length);
    var html = '<span class="notebook-pager-info">Showing ' + first + '–' + last + ' of ' + rows.length + '</span>';
    html += '<div class="notebook-pager-pages">';
    html += '<button type="button" class="notebook-pager-btn" data-page="' + (current - 1) + '"' + (current === 1 ? ' disabled' : '') + ' aria-label="Previous page"><i class="bi bi-chevron-left"></i></button>';
    for (var p = 1; p <= pageCount; p++) {
      html += '<button type="button" class="notebook-pager-btn' + (p === current ? ' active' : '') + '" data-page="' + p + '"' + (p === current ? ' aria-current="page"' : '') + '>' + p + '</button>';
    }
    html += '<button type="button" class="notebook-pager-btn" data-page="' + (current + 1) + '"' + (current === pageCount ? ' disabled' : '') + ' aria-label="Next page"><i class="bi bi-chevron-right"></i></button>';
    html += '</div>';
    pager.innerHTML = html;
  }

  // Play one CSS animation on the sheet and resolve when it ends.
  function play(className) {
    return new Promise(function (resolve) {
      sheet.classList.add(className);
      sheet.addEventListener('animationend', function done() {
        sheet.removeEventListener('animationend', done);
        sheet.classList.remove(className);
        resolve();
      });
    });
  }

  function goTo(page) {
    if (turning || page < 1 || page > pageCount || page === current) return;
    var forward = page > current;
    current = page;
    renderPager();

    if (reduceMotion) {
      showRows(current);
      return;
    }

    // Forward: the page lifts off and swings over the spine (left edge),
    // revealing the next page. Back: the previous page swings back into place.
    turning = true;
    play(forward ? 'page-turn-out' : 'page-fade-out')
      .then(function () {
        showRows(current);
        return play(forward ? 'page-reveal' : 'page-turn-in');
      })
      .then(function () { turning = false; });
  }

  pager.addEventListener('click', function (e) {
    var btn = e.target.closest('.notebook-pager-btn');
    if (btn && !btn.disabled) goTo(Number(btn.dataset.page));
  });

  showRows(current);
  renderPager();
});
