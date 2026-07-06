/******/ (() => { // webpackBootstrap
/*!*****************************************!*\
  !*** ./assets/src/js/admin-settings.js ***!
  \*****************************************/
(() => {
  const settings = window.lpStripeAdminSettings || {};
  const testModeField = settings.testModeField || 'learn_press_stripe[test_mode]';
  const testModeSelector = `input[name="${testModeField}"]`;
  const getSettingsSection = title => {
    const headings = document.querySelectorAll('h2');
    let targetHeading = null;
    for (let i = 0; i < headings.length; i++) {
      if (headings[i].textContent.trim() === title) {
        targetHeading = headings[i];
        break;
      }
    }
    if (!targetHeading) {
      return [];
    }
    const elements = [targetHeading];
    let sibling = targetHeading.nextElementSibling;
    while (sibling) {
      if (sibling.tagName.toLowerCase() === 'table' && sibling.classList.contains('lp-metabox__table')) {
        elements.push(sibling);
        break;
      }
      sibling = sibling.nextElementSibling;
    }
    return elements;
  };
  const toggleElementsVisibility = (elements, show) => {
    elements.forEach(element => {
      element.style.display = show ? '' : 'none';
    });
  };
  const toggleModeSections = () => {
    const testMode = document.querySelector(testModeSelector);
    if (!testMode || !settings.liveTitle || !settings.testTitle) {
      return;
    }
    const isTestMode = testMode.checked;
    toggleElementsVisibility(getSettingsSection(settings.liveTitle), !isTestMode);
    toggleElementsVisibility(getSettingsSection(settings.testTitle), isTestMode);
  };
  const init = () => {
    toggleModeSections();
    document.addEventListener('change', event => {
      if (event.target && event.target.matches(testModeSelector)) {
        toggleModeSections();
      }
    });
  };
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
/******/ })()
;
//# sourceMappingURL=admin-settings.js.map