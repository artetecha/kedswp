/******/ (() => { // webpackBootstrap
/*!**********************************************************!*\
  !*** ./assets/src/js/backend/certificate-cache-tools.js ***!
  \**********************************************************/
document.addEventListener('DOMContentLoaded', () => {
  const settings = window.lpCertCacheTools;
  const button = document.querySelector('.lp-cert-cache-tools__delete');
  if (!settings || !button) {
    return;
  }
  const tools = button.closest('.lp-cert-cache-tools');
  const submit = document.querySelector('.learn-press-settings-form .submit, .submit');
  if (tools && submit) {
    submit.parentNode.insertBefore(tools, submit);
  }
  button.addEventListener('click', async () => {
    if (!window.confirm(settings.confirmMessage)) {
      return;
    }
    button.disabled = true;
    button.textContent = settings.deletingLabel;
    try {
      const response = await fetch(settings.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: new URLSearchParams({
          action: 'lp_cert_delete_all_images',
          nonce: settings.nonce
        })
      });
      const result = await response.json();
      window.alert(result.success ? result.data.message : settings.errorMessage);
    } catch (e) {
      window.alert(settings.errorMessage);
    } finally {
      button.disabled = false;
      button.textContent = settings.buttonLabel;
    }
  });
});
/******/ })()
;
//# sourceMappingURL=certificate-cache-tools.js.map