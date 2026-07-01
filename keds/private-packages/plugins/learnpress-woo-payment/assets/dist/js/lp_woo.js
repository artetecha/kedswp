/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./assets/src/js/add-item-to-cart.js":
/*!*******************************************!*\
  !*** ./assets/src/js/add-item-to-cart.js ***!
  \*******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./utils */ "./assets/src/js/utils.js");
/**
 * Add course to cart
 *
 * @since 4.1.4
 * @version 1.0.0
 */


// Event click add item to cart
document.addEventListener('submit', function (e) {
  const target = e.target;
  if (target.name === 'form-add-item-to-cart') {
    addItemToCart(e, target);
  }
});

/**
 * Add item to cart
 *
 * @param e
 * @param form
 */
const addItemToCart = function (e, form) {
  e.preventDefault();

  /**
   * For theme Eduma
   * When user not login, click add-to-cart will show popup login
   * Set params submit course
   */
  const elThimLoginPopup = document.querySelector('.thim-login-popup .login');
  if (elThimLoginPopup && 'yes' !== lpWoo.woo_enable_signup_and_login_from_checkout && 'yes' !== lpWoo.woocommerce_enable_guest_checkout) {
    if (parseInt(lpData.user_id) === 0) {
      elThimLoginPopup.click();
      return;
    }
  }
  let elItemId = form.querySelector('input[name="item-id"]');
  if (!elItemId) {
    return;
  }
  const itemID = elItemId.value;
  const lang = lpData.urlParams.lang ? `?lang=${lpData.urlParams.lang}` : '';
  const elBtnAddToCart = form.querySelector('.lp-btn-add-item-to-cart');
  if (elBtnAddToCart) {
    (0,_utils__WEBPACK_IMPORTED_MODULE_0__.lpSetLoadingEl)(elBtnAddToCart, 1);
  }
  let formData = new FormData(form);
  formData.append('action', `lpWooAddItemToCart`);
  formData.append('nonce', lpWoo.nonce);
  fetch(lpWoo.url_ajax + lang, {
    method: 'POST',
    body: formData
  }).then(res => res.text()).then(res => {
    const dataObj = JSON.parse(res);
    const {
      data,
      status,
      message
    } = dataObj;
    if (status === 'error') {
      form.innerHTML = message;
      return;
    }
    if ('undefined' !== typeof data.redirect_to && data.redirect_to !== '') {
      window.location = data.redirect_to;
    } else {
      // Find all form item id same set new HTML
      const elInput = document.querySelectorAll(`input[name="item-id"][value="${itemID}"]`);
      elInput.forEach(el => {
        el.closest('form[name=form-add-item-to-cart]').outerHTML = data.button_view_cart;
      });
      const el_mini_cart_count = document.querySelectorAll('.minicart_hover .items-number');
      if (el_mini_cart_count.length) {
        el_mini_cart_count.forEach(el => {
          el.innerHTML = data.count_items;
        });
      }
      const el_mini_cart_count_elementor = document.querySelectorAll('.elementor-widget-woocommerce-menu-cart .elementor-button-icon-qty');
      if (el_mini_cart_count_elementor.length) {
        el_mini_cart_count_elementor.forEach(el => {
          el.innerText = data.count_items;
          el.dataset.counter = dataset.counter;
        });
      }

      // ThimElKit count items cart
      const el_thim_el_kit_cart_count = document.querySelectorAll('.thim-ekits-mini-cart .cart-items-number');
      if (el_thim_el_kit_cart_count.length) {
        el_thim_el_kit_cart_count.forEach(el => {
          el.innerHTML = data.count_items;
        });
      }

      // Update cart widget
      const el_widget_shopping_cart_contents = document.querySelectorAll('.widget_shopping_cart_content');
      if (el_widget_shopping_cart_contents.length) {
        el_widget_shopping_cart_contents.forEach(el => {
          el.innerHTML = data.widget_shopping_cart_content;
        });
      }
    }
  }).catch(err => console.log(err)).finally(() => {
    if (elBtnAddToCart) {
      (0,_utils__WEBPACK_IMPORTED_MODULE_0__.lpSetLoadingEl)(elBtnAddToCart, 0);
    }
  });
};
const check_reload_browser = () => {
  window.addEventListener('pageshow', function (event) {
    const hasCache = event.persisted || typeof window.performance != 'undefined' && String(window.performance.getEntriesByType('navigation')[0].type) === 'back_forward';

    //console.log( hasCache );

    if (hasCache) {
      location.reload();
    }
  });
};

// Fix event browser back - load page to show 'view cart' button if added to cart
check_reload_browser();

/***/ }),

/***/ "./assets/src/js/utils.js":
/*!********************************!*\
  !*** ./assets/src/js/utils.js ***!
  \********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   listenElementCreated: () => (/* binding */ listenElementCreated),
/* harmony export */   listenElementViewed: () => (/* binding */ listenElementViewed),
/* harmony export */   lpAddQueryArgs: () => (/* binding */ lpAddQueryArgs),
/* harmony export */   lpAjaxParseJsonOld: () => (/* binding */ lpAjaxParseJsonOld),
/* harmony export */   lpFetchAPI: () => (/* binding */ lpFetchAPI),
/* harmony export */   lpGetCurrentURLNoParam: () => (/* binding */ lpGetCurrentURLNoParam),
/* harmony export */   lpOnElementReady: () => (/* binding */ lpOnElementReady),
/* harmony export */   lpSetLoadingEl: () => (/* binding */ lpSetLoadingEl),
/* harmony export */   lpShowHideEl: () => (/* binding */ lpShowHideEl)
/* harmony export */ });
/**
 * Utils functions
 *
 * @param url
 * @param data
 * @param functions
 * @since 4.2.5.1
 * @version 1.0.3
 */
const lpClassName = {
  hidden: 'lp-hidden',
  loading: 'loading'
};
const lpFetchAPI = (url, data = {}, functions = {}) => {
  if ('function' === typeof functions.before) {
    functions.before();
  }
  fetch(url, {
    method: 'GET',
    ...data
  }).then(response => response.json()).then(response => {
    if ('function' === typeof functions.success) {
      functions.success(response);
    }
  }).catch(err => {
    if ('function' === typeof functions.error) {
      functions.error(err);
    }
  }).finally(() => {
    if ('function' === typeof functions.completed) {
      functions.completed();
    }
  });
};

/**
 * Get current URL without params.
 *
 * @since 4.2.5.1
 */
const lpGetCurrentURLNoParam = () => {
  let currentUrl = window.location.href;
  const hasParams = currentUrl.includes('?');
  if (hasParams) {
    currentUrl = currentUrl.split('?')[0];
  }
  return currentUrl;
};
const lpAddQueryArgs = (endpoint, args) => {
  const url = new URL(endpoint);
  Object.keys(args).forEach(arg => {
    url.searchParams.set(arg, args[arg]);
  });
  return url;
};

/**
 * Listen element viewed.
 *
 * @param el
 * @param callback
 * @since 4.2.5.8
 */
const listenElementViewed = (el, callback) => {
  const observerSeeItem = new IntersectionObserver(function (entries) {
    for (const entry of entries) {
      if (entry.isIntersecting) {
        callback(entry);
      }
    }
  });
  observerSeeItem.observe(el);
};

/**
 * Listen element created.
 *
 * @param callback
 * @since 4.2.5.8
 */
const listenElementCreated = callback => {
  const observerCreateItem = new MutationObserver(function (mutations) {
    mutations.forEach(function (mutation) {
      if (mutation.addedNodes) {
        mutation.addedNodes.forEach(function (node) {
          if (node.nodeType === 1) {
            callback(node);
          }
        });
      }
    });
  });
  observerCreateItem.observe(document, {
    childList: true,
    subtree: true
  });
  // End.
};

/**
 * Listen element created.
 *
 * @param selector
 * @param callback
 * @since 4.2.7.1
 */
const lpOnElementReady = (selector, callback) => {
  const element = document.querySelector(selector);
  if (element) {
    callback(element);
    return;
  }
  const observer = new MutationObserver((mutations, obs) => {
    const element = document.querySelector(selector);
    if (element) {
      obs.disconnect();
      callback(element);
    }
  });
  observer.observe(document.documentElement, {
    childList: true,
    subtree: true
  });
};

// Parse JSON from string with content include LP_AJAX_START.
const lpAjaxParseJsonOld = data => {
  if (typeof data !== 'string') {
    return data;
  }
  const m = String.raw({
    raw: data
  }).match(/<-- LP_AJAX_START -->(.*)<-- LP_AJAX_END -->/s);
  try {
    if (m) {
      data = JSON.parse(m[1].replace(/(?:\r\n|\r|\n)/g, ''));
    } else {
      data = JSON.parse(data);
    }
  } catch (e) {
    data = {};
  }
  return data;
};

// status 0: hide, 1: show
const lpShowHideEl = (el, status = 0) => {
  if (!el) {
    return;
  }
  if (!status) {
    el.classList.add(lpClassName.hidden);
  } else {
    el.classList.remove(lpClassName.hidden);
  }
};

// status 0: hide, 1: show
const lpSetLoadingEl = (el, status) => {
  if (!el) {
    return;
  }
  if (!status) {
    el.classList.remove(lpClassName.loading);
  } else {
    el.classList.add(lpClassName.loading);
  }
};


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!*********************************!*\
  !*** ./assets/src/js/lp_woo.js ***!
  \*********************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _add_item_to_cart_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./add-item-to-cart.js */ "./assets/src/js/add-item-to-cart.js");
/**
 * Js handle add to cart
 *
 * @version 1.0.2
 * @since 3.0.0
 */


//import './check-course-product.js';
//import {} from './add-package-to-cart.js'
})();

/******/ })()
;
//# sourceMappingURL=lp_woo.js.map