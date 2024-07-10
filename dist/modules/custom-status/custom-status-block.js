/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./modules/custom-status/lib/editor.scss":
/*!***********************************************!*\
  !*** ./modules/custom-status/lib/editor.scss ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "react":
/*!************************!*\
  !*** external "React" ***!
  \************************/
/***/ ((module) => {

module.exports = window["React"];

/***/ }),

/***/ "@wordpress/components":
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
/***/ ((module) => {

module.exports = window["wp"]["components"];

/***/ }),

/***/ "@wordpress/compose":
/*!*********************************!*\
  !*** external ["wp","compose"] ***!
  \*********************************/
/***/ ((module) => {

module.exports = window["wp"]["compose"];

/***/ }),

/***/ "@wordpress/data":
/*!******************************!*\
  !*** external ["wp","data"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["data"];

/***/ }),

/***/ "@wordpress/edit-post":
/*!**********************************!*\
  !*** external ["wp","editPost"] ***!
  \**********************************/
/***/ ((module) => {

module.exports = window["wp"]["editPost"];

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["i18n"];

/***/ }),

/***/ "@wordpress/plugins":
/*!*********************************!*\
  !*** external ["wp","plugins"] ***!
  \*********************************/
/***/ ((module) => {

module.exports = window["wp"]["plugins"];

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
// This entry need to be wrapped in an IIFE because it need to be isolated against other modules in the chunk.
(() => {
/*!**********************************************************!*\
  !*** ./modules/custom-status/lib/custom-status-block.js ***!
  \**********************************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _editor_scss__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./editor.scss */ "./modules/custom-status/lib/editor.scss");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/compose */ "@wordpress/compose");
/* harmony import */ var _wordpress_compose__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_compose__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_edit_post__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/edit-post */ "@wordpress/edit-post");
/* harmony import */ var _wordpress_edit_post__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_edit_post__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _wordpress_plugins__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @wordpress/plugins */ "@wordpress/plugins");
/* harmony import */ var _wordpress_plugins__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_wordpress_plugins__WEBPACK_IMPORTED_MODULE_7__);









/**
 * Map Custom Statuses as options for SelectControl
 */
const statuses = window.VipWorkflowCustomStatuses.map(s => ({
  label: s.name,
  value: s.slug
}));

/**
 * Subscribe to changes so we can set a default status and update a button's text.
 */
let buttonTextObserver = null;
(0,_wordpress_data__WEBPACK_IMPORTED_MODULE_4__.subscribe)(function () {
  const postId = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_4__.select)('core/editor').getCurrentPostId();
  if (!postId) {
    // Post isn't ready yet so don't do anything.
    return;
  }

  // For new posts, we need to force the default custom status.
  const isCleanNewPost = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_4__.select)('core/editor').isCleanNewPost();
  if (isCleanNewPost) {
    (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_4__.dispatch)('core/editor').editPost({
      status: vw_default_custom_status
    });
  }

  // If the save button exists, let's update the text if needed.
  maybeUpdateButtonText(document.querySelector('.editor-post-save-draft'));

  // The post is being saved, so we need to set up an observer to update the button text when it's back.
  if (buttonTextObserver === null && window.MutationObserver && (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_4__.select)('core/editor').isSavingPost()) {
    buttonTextObserver = createButtonObserver(document.querySelector('.edit-post-header__settings'));
  }
});

/**
 * Create a mutation observer that will update the
 * save button text right away when it's changed/re-added.
 *
 * Ideally there will be better ways to go about this in the future.
 * @see https://github.com/Automattic/Edit-Flow/issues/583
 */
function createButtonObserver(parentNode) {
  if (!parentNode) {
    return null;
  }
  const observer = new MutationObserver(mutationsList => {
    for (const mutation of mutationsList) {
      for (const node of mutation.addedNodes) {
        maybeUpdateButtonText(node);
      }
    }
  });
  observer.observe(parentNode, {
    childList: true
  });
  return observer;
}
function maybeUpdateButtonText(saveButton) {
  /*
   * saveButton.children < 1 accounts for when a user hovers over the save button
   * and a tooltip is rendered
   */
  if (saveButton && saveButton.children < 1 && (saveButton.innerText === (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__.__)('Save Draft') || saveButton.innerText === (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__.__)('Save as Pending'))) {
    saveButton.innerText = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__.__)('Save');
  }
}

/**
 * Custom status component
 * @param object props
 */
const VIPWorkflowCustomPostStati = ({
  onUpdate,
  status
}) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_edit_post__WEBPACK_IMPORTED_MODULE_5__.PluginPostStatusInfo, {
  className: `vip-workflow-extended-post-status vip-workflow-extended-post-status-${status}`
}, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, status !== 'publish' ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__.__)('Extended Post Status', 'vip-workflow') : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__.__)('Extended Post Status Disabled.', 'vip-workflow')), status !== 'publish' ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.SelectControl, {
  label: "",
  value: status,
  options: statuses,
  onChange: onUpdate
}) : null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("small", {
  className: "vip-workflow-extended-post-status-note"
}, status !== 'publish' ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__.__)('Note: this will override all status settings above.', 'vip-workflow') : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_6__.__)('To select a custom status, please unpublish the content first.', 'vip-workflow')));
const mapSelectToProps = select => {
  return {
    status: select('core/editor').getEditedPostAttribute('status')
  };
};
const mapDispatchToProps = dispatch => {
  return {
    onUpdate(status) {
      dispatch('core/editor').editPost({
        status
      });
    }
  };
};
const plugin = (0,_wordpress_compose__WEBPACK_IMPORTED_MODULE_3__.compose)((0,_wordpress_data__WEBPACK_IMPORTED_MODULE_4__.withSelect)(mapSelectToProps), (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_4__.withDispatch)(mapDispatchToProps))(VIPWorkflowCustomPostStati);

/**
 * Kick it off
 */
(0,_wordpress_plugins__WEBPACK_IMPORTED_MODULE_7__.registerPlugin)('vip-workflow-custom-status', {
  icon: 'vip-workflow',
  render: plugin
});
})();

/******/ })()
;
//# sourceMappingURL=custom-status-block.js.map