/**
 * PESO - Shared button loading state (coin spinner)
 */

function pesoButtonLoading(btn, isLoading, loadingText) {
  if (!btn) return;

  if (isLoading) {
    if (btn.dataset.originalHtml === undefined) {
      btn.dataset.originalHtml = btn.innerHTML;
    }
    btn.disabled = true;
    btn.innerHTML = '<span class="coin-loader"><span class="coin"></span><span class="coin"></span><span class="coin"></span></span>' +
      (loadingText ? '<span class="coin-loader-text">' + loadingText + '</span>' : '');
    return;
  }

  btn.disabled = false;
  if (btn.dataset.originalHtml !== undefined) {
    btn.innerHTML = btn.dataset.originalHtml;
    delete btn.dataset.originalHtml;
  }
}
