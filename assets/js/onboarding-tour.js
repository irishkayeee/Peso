/**
 * PESO - Lightweight onboarding tour
 * Highlights a sequence of elements (by [data-tour] target) with a
 * positioned tooltip. No dependencies.
 */
(function () {
  function PesoTour(steps) {
    this.steps = steps;
    this.index = 0;
    this.overlay = null;
    this.spotlight = null;
    this.tooltip = null;
    this.onResize = this.position.bind(this);
  }

  PesoTour.prototype.start = function () {
    if (!this.steps.length) return;

    this.overlay = document.createElement('div');
    this.overlay.className = 'peso-tour-overlay';

    this.spotlight = document.createElement('div');
    this.spotlight.className = 'peso-tour-spotlight';

    this.tooltip = document.createElement('div');
    this.tooltip.className = 'peso-tour-tooltip';

    document.body.appendChild(this.overlay);
    document.body.appendChild(this.spotlight);
    document.body.appendChild(this.tooltip);

    window.addEventListener('resize', this.onResize);
    this.render();
  };

  PesoTour.prototype.end = function () {
    window.removeEventListener('resize', this.onResize);
    [this.overlay, this.spotlight, this.tooltip].forEach(function (el) {
      if (el && el.parentNode) el.parentNode.removeChild(el);
    });
    var focused = document.activeElement;
    if (focused && focused.blur) focused.blur();
  };

  PesoTour.prototype.render = function () {
    var step = this.steps[this.index];
    var target = document.querySelector('[data-tour="' + step.target + '"]');

    if (!target) {
      this.next();
      return;
    }

    // Focusing the nav link expands the sidebar (it already expands on
    // hover/focus-within), so the label is visible while it's explained.
    target.focus({ preventScroll: true });
    target.scrollIntoView({ block: 'nearest', behavior: 'smooth' });

    var self = this;
    setTimeout(function () { self.position(); }, 220); // wait for the sidebar's expand transition
  };

  PesoTour.prototype.position = function () {
    var step = this.steps[this.index];
    var target = document.querySelector('[data-tour="' + step.target + '"]');
    if (!target) return;

    var rect = target.getBoundingClientRect();
    var pad = 8;

    this.spotlight.style.top = (rect.top - pad) + 'px';
    this.spotlight.style.left = (rect.left - pad) + 'px';
    this.spotlight.style.width = (rect.width + pad * 2) + 'px';
    this.spotlight.style.height = (rect.height + pad * 2) + 'px';

    var isLast = this.index === this.steps.length - 1;
    var isFirst = this.index === 0;

    this.tooltip.innerHTML =
      '<div class="peso-tour-step">' + (this.index + 1) + ' of ' + this.steps.length + '</div>' +
      '<h4>' + step.title + '</h4>' +
      '<p>' + step.body + '</p>' +
      '<div class="peso-tour-actions">' +
        '<button type="button" class="peso-tour-skip">Skip tour</button>' +
        '<div class="peso-tour-nav">' +
          (isFirst ? '' : '<button type="button" class="peso-tour-back">Back</button>') +
          '<button type="button" class="peso-tour-next">' + (isLast ? 'Done' : 'Next') + '</button>' +
        '</div>' +
      '</div>';

    // Position to the right of the target, flipping to the left if it
    // would overflow the viewport.
    var ttRect = this.tooltip.getBoundingClientRect();
    var top = Math.max(12, Math.min(rect.top, window.innerHeight - ttRect.height - 12));
    var left = rect.right + pad * 2;
    if (left + ttRect.width > window.innerWidth - 12) {
      left = rect.left - ttRect.width - pad * 2;
    }
    if (left < 12) {
      left = 12;
      top = rect.bottom + pad * 2;
    }

    this.tooltip.style.top = top + 'px';
    this.tooltip.style.left = left + 'px';

    var self = this;
    this.tooltip.querySelector('.peso-tour-skip').addEventListener('click', function () { self.end(); });
    this.tooltip.querySelector('.peso-tour-next').addEventListener('click', function () { self.next(); });
    var backBtn = this.tooltip.querySelector('.peso-tour-back');
    if (backBtn) backBtn.addEventListener('click', function () { self.back(); });
  };

  PesoTour.prototype.next = function () {
    if (this.index >= this.steps.length - 1) {
      this.end();
      return;
    }
    this.index++;
    this.render();
  };

  PesoTour.prototype.back = function () {
    if (this.index === 0) return;
    this.index--;
    this.render();
  };

  window.PesoTour = PesoTour;
})();
