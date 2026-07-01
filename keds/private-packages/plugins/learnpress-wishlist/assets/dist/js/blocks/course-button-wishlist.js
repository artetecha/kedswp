/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./assets/src/js/gutenberg/blocks/course-button-wishlist/edit.js"
/*!***********************************************************************!*\
  !*** ./assets/src/js/gutenberg/blocks/course-button-wishlist/edit.js ***!
  \***********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/block-editor */ "@wordpress/block-editor");
/* harmony import */ var _wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__);




const Edit = props => {
  const {
    attributes,
    setAttributes
  } = props;
  const isIconOnly = attributes.layout === 'icon-only';
  const mapAlignItems = {
    top: 'flex-start',
    center: 'center',
    bottom: 'flex-end'
  };

  // Outer wrapper style (flex container)
  const wrapperStyle = {
    display: 'flex',
    justifyContent: attributes.justifyContent,
    width: '100%'
  };

  // Button style - includes width
  const buttonStyle = {
    width: isIconOnly ? 'auto' : attributes.width ? `${attributes.width}%` : '100%',
    justifyContent: attributes.textAlign,
    alignItems: mapAlignItems[attributes.alignItems] || 'center'
  };

  // Apply blockProps to button element (Gutenberg styles like color, typography)
  // This matches PHP render behavior where wrapper classes are merged onto button
  const blockProps = (0,_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.useBlockProps)({
    className: `lp-button lp-button-wishlist-action ${attributes.layout || 'modern'}`,
    style: buttonStyle
  });

  // Render button content based on layout
  const renderButtonContent = () => {
    switch (attributes.layout) {
      case 'icon-only':
        return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("svg", {
          width: "20",
          height: "20",
          viewBox: "0 0 20 20",
          fill: "none",
          children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("path", {
            fillRule: "evenodd",
            clipRule: "evenodd",
            d: "M12.0516 2.07769C12.7089 1.80532 13.4135 1.66513 14.125 1.66513C14.8366 1.66513 15.5411 1.80532 16.1985 2.07769C16.8557 2.35002 17.4528 2.74914 17.9558 3.25226C18.4589 3.75522 18.8583 4.35264 19.1306 5.00986C19.403 5.66721 19.5432 6.37178 19.5432 7.08332C19.5432 7.79486 19.403 8.49943 19.1306 9.15677C18.8583 9.81406 18.4591 10.4113 17.9559 10.9142C17.9559 10.9143 17.956 10.9142 17.9559 10.9142L10.5893 18.2809C10.2638 18.6063 9.7362 18.6063 9.41076 18.2809L2.0441 10.9142C1.02807 9.89822 0.457275 8.52019 0.457275 7.08332C0.457275 5.64644 1.02807 4.26842 2.0441 3.2524C3.06012 2.23637 4.43814 1.66558 5.87502 1.66558C7.31189 1.66558 8.68992 2.23637 9.70594 3.2524L10 3.54647L10.294 3.25253C10.2939 3.25258 10.294 3.25249 10.294 3.25253C10.7969 2.74935 11.3943 2.35004 12.0516 2.07769ZM14.125 3.3318C13.6324 3.3318 13.1446 3.42885 12.6895 3.61742C12.2345 3.80598 11.821 4.08236 11.4727 4.43077L10.5893 5.31424C10.2638 5.63968 9.7362 5.63968 9.41076 5.31424L8.52743 4.43091C7.82397 3.72744 6.86986 3.33224 5.87502 3.33224C4.88017 3.33224 3.92607 3.72744 3.22261 4.43091C2.51914 5.13437 2.12394 6.08847 2.12394 7.08332C2.12394 8.07817 2.51914 9.03227 3.22261 9.73573L10 16.5131L16.7774 9.73573C17.1258 9.38749 17.4024 8.97387 17.5909 8.51879C17.7795 8.0637 17.8765 7.57592 17.8765 7.08332C17.8765 6.59071 17.7795 6.10294 17.5909 5.64785C17.4024 5.19276 17.126 4.77929 16.7776 4.43105C16.4293 4.08264 16.0156 3.80598 15.5605 3.61742C15.1054 3.42885 14.6176 3.3318 14.125 3.3318Z",
            fill: "currentColor"
          })
        });
      case 'classic':
        return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.Fragment, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("i", {
            className: "lp-icon-heart-o",
            style: {
              color: 'inherit'
            }
          }), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Add to Wishlist', 'learnpress-wishlist')]
        });
      case 'modern':
      default:
        return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.Fragment, {
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("i", {
            className: "lp-icon-heart-o",
            style: {
              color: 'inherit'
            }
          }), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Wishlist', 'learnpress-wishlist')]
        });
    }
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.Fragment, {
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.BlockControls, {
      children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.AlignmentToolbar, {
        value: attributes.textAlign,
        onChange: newAlign => setAttributes({
          textAlign: newAlign
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.JustifyToolbar, {
        value: attributes.justifyContent,
        onChange: newJustify => setAttributes({
          justifyContent: newJustify
        })
      }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.BlockVerticalAlignmentToolbar, {
        value: attributes.alignItems,
        onChange: newAlign => setAttributes({
          alignItems: newAlign
        })
      })]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_block_editor__WEBPACK_IMPORTED_MODULE_2__.InspectorControls, {
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.PanelBody, {
        title: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Settings', 'learnpress-wishlist'),
        children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.SelectControl, {
          label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Layout', 'learnpress-wishlist'),
          value: attributes.layout,
          options: [{
            label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Classic', 'learnpress-wishlist'),
            value: 'classic'
          }, {
            label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Modern', 'learnpress-wishlist'),
            value: 'modern'
          }, {
            label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Icon Only', 'learnpress-wishlist'),
            value: 'icon-only'
          }],
          onChange: value => setAttributes({
            layout: value
          })
        }), !isIconOnly && /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.__experimentalToggleGroupControl, {
          label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)('Width', 'learnpress-wishlist'),
          value: attributes.width || '100',
          onChange: value => {
            setAttributes({
              width: value || '100'
            });
          },
          isBlock: true,
          children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.__experimentalToggleGroupControlOption, {
            value: "25",
            label: "25%"
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.__experimentalToggleGroupControlOption, {
            value: "50",
            label: "50%"
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.__experimentalToggleGroupControlOption, {
            value: "75",
            label: "75%"
          }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_1__.__experimentalToggleGroupControlOption, {
            value: "100",
            label: "100%"
          })]
        })]
      })
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
      className: "course-button-wishlist__wrapper",
      style: wrapperStyle,
      children: /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("button", {
        ...blockProps,
        children: renderButtonContent()
      })
    })]
  });
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (Edit);

/***/ },

/***/ "./assets/src/js/gutenberg/blocks/course-button-wishlist/save.js"
/*!***********************************************************************!*\
  !*** ./assets/src/js/gutenberg/blocks/course-button-wishlist/save.js ***!
  \***********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   save: () => (/* binding */ save)
/* harmony export */ });
const save = props => null;

/***/ },

/***/ "./assets/src/js/gutenberg/blocks/utilBlock.js"
/*!*****************************************************!*\
  !*** ./assets/src/js/gutenberg/blocks/utilBlock.js ***!
  \*****************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   checkTemplatesCanLoadBlock: () => (/* binding */ checkTemplatesCanLoadBlock)
/* harmony export */ });
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_1__);
/**
 * Check if the block can be loaded in the current template.
 *
 * @version 1.0.0
 */


let currentPostIdOld = null;
const checkTemplatesCanLoadBlock = (templates, metadata, callBack) => {
  (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_1__.subscribe)(() => {
    const metaDataNew = {
      ...metadata
    };
    const store = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_1__.select)('core/editor') || null;
    if (!store || typeof store.getCurrentPostId !== 'function' || !store.getCurrentPostId()) {
      return;
    }
    const currentPostId = store.getCurrentPostId();
    if (currentPostId === null) {
      return;
    }
    if (currentPostIdOld === currentPostId) {
      return;
    }
    currentPostIdOld = currentPostId;
    if ((0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__.getBlockType)(metaDataNew.name)) {
      (0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_0__.unregisterBlockType)(metaDataNew.name);
      if (templates.includes(currentPostId)) {
        metaDataNew.ancestor = null;
        callBack(metaDataNew);
      } else {
        if (!metaDataNew.ancestor) {
          metaDataNew.ancestor = [metaDataNew.name];
        }
        callBack(metaDataNew);
      }
    }
  });
};


/***/ },

/***/ "react/jsx-runtime"
/*!**********************************!*\
  !*** external "ReactJSXRuntime" ***!
  \**********************************/
(module) {

module.exports = window["ReactJSXRuntime"];

/***/ },

/***/ "@wordpress/block-editor"
/*!*************************************!*\
  !*** external ["wp","blockEditor"] ***!
  \*************************************/
(module) {

module.exports = window["wp"]["blockEditor"];

/***/ },

/***/ "@wordpress/blocks"
/*!********************************!*\
  !*** external ["wp","blocks"] ***!
  \********************************/
(module) {

module.exports = window["wp"]["blocks"];

/***/ },

/***/ "@wordpress/components"
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
(module) {

module.exports = window["wp"]["components"];

/***/ },

/***/ "@wordpress/data"
/*!******************************!*\
  !*** external ["wp","data"] ***!
  \******************************/
(module) {

module.exports = window["wp"]["data"];

/***/ },

/***/ "@wordpress/i18n"
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
(module) {

module.exports = window["wp"]["i18n"];

/***/ },

/***/ "./assets/src/js/gutenberg/blocks/course-button-wishlist/block.json"
/*!**************************************************************************!*\
  !*** ./assets/src/js/gutenberg/blocks/course-button-wishlist/block.json ***!
  \**************************************************************************/
(module) {

module.exports = /*#__PURE__*/JSON.parse('{"$schema":"https://schemas.wp.org/trunk/block.json","apiVersion":3,"name":"learnpress/course-button-wishlist","title":"Course Button Wishlist","category":"learnpress-course-elements","icon":"heart","description":"Renders template Button Wishlist Course PHP templates.","textdomain":"learnpress-wishlist","keywords":["button wishlist course","learnpress"],"ancestor":["learnpress/single-course","learnpress/course-item-template"],"usesContext":["lpCourseData"],"attributes":{"textAlign":{"type":"string","default":"center"},"justifyContent":{"type":"string","default":"center"},"alignItems":{"type":"string","default":"center"},"width":{"type":"string","default":"100"},"layout":{"type":"string","default":"modern","enum":["classic","modern","icon-only"]}},"supports":{"multiple":true,"align":["wide","full"],"html":false,"typography":{"fontSize":true,"__experimentalDefaultControls":{"fontSize":true}},"color":{"background":true,"text":true,"__experimentalDefaultControls":{"background":true,"text":true}},"__experimentalBorder":{"color":true,"radius":true,"width":true,"__experimentalDefaultControls":{"width":false,"color":false,"radius":false}},"spacing":{"margin":true,"padding":true,"content":true,"__experimentalDefaultControls":{"margin":false,"padding":false,"content":true}}}}');

/***/ }

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
/******/ 		if (!(moduleId in __webpack_modules__)) {
/******/ 			delete __webpack_module_cache__[moduleId];
/******/ 			var e = new Error("Cannot find module '" + moduleId + "'");
/******/ 			e.code = 'MODULE_NOT_FOUND';
/******/ 			throw e;
/******/ 		}
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
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
/*!************************************************************************!*\
  !*** ./assets/src/js/gutenberg/blocks/course-button-wishlist/index.js ***!
  \************************************************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _edit_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./edit.js */ "./assets/src/js/gutenberg/blocks/course-button-wishlist/edit.js");
/* harmony import */ var _save_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./save.js */ "./assets/src/js/gutenberg/blocks/course-button-wishlist/save.js");
/* harmony import */ var _block_json__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./block.json */ "./assets/src/js/gutenberg/blocks/course-button-wishlist/block.json");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/blocks */ "@wordpress/blocks");
/* harmony import */ var _wordpress_blocks__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_blocks__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _utilBlock_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utilBlock.js */ "./assets/src/js/gutenberg/blocks/utilBlock.js");
/**
 * Register block course button wishlist.
 */






const templatesName = ['learnpress/learnpress//single-lp_course', 'learnpress/learnpress//single-lp_course-offline'];
(0,_utilBlock_js__WEBPACK_IMPORTED_MODULE_4__.checkTemplatesCanLoadBlock)(templatesName, _block_json__WEBPACK_IMPORTED_MODULE_2__, metadataNew => {
  (0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_3__.registerBlockType)(metadataNew.name, {
    ...metadataNew,
    edit: _edit_js__WEBPACK_IMPORTED_MODULE_0__["default"],
    save: _save_js__WEBPACK_IMPORTED_MODULE_1__.save
  });
});
(0,_wordpress_blocks__WEBPACK_IMPORTED_MODULE_3__.registerBlockType)(_block_json__WEBPACK_IMPORTED_MODULE_2__.name, {
  ..._block_json__WEBPACK_IMPORTED_MODULE_2__,
  edit: _edit_js__WEBPACK_IMPORTED_MODULE_0__["default"],
  save: _save_js__WEBPACK_IMPORTED_MODULE_1__.save
});
})();

/******/ })()
;
//# sourceMappingURL=course-button-wishlist.js.map