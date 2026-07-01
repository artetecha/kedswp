"use strict";
globalThis["webpackHotUpdatelearnpress_assignments"]("./frontend/assignment",{

/***/ "./assets/src/js/frontend/assignment.js":
/*!**********************************************!*\
  !*** ./assets/src/js/frontend/assignment.js ***!
  \**********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _utils_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../utils.js */ "./assets/src/js/utils.js");
/* harmony import */ var toastify_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! toastify-js */ "./node_modules/toastify-js/src/toastify.js");
/* harmony import */ var toastify_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(toastify_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var toastify_js_src_toastify_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! toastify-js/src/toastify.css */ "./node_modules/toastify-js/src/toastify.css");
/**
 * Single Assignment Frontend JS
 *
 * @since 4.1.2
 * @version 1.0.0
 */





const toastifyAssignment = toastify_js__WEBPACK_IMPORTED_MODULE_1___default()( {
	gravity: lpData.toast.gravity, // `top` or `bottom`
	position: lpData.toast.position, // `left`, `center` or `right`
	close: lpData.toast.close == 1,
	className: `${ lpData.toast.classPrefix }`,
	stopOnFocus: lpData.toast.stopOnFocus == 1,
	duration: lpData.toast.duration,
} );

const countDown = ( elCountDown, elForm, timeRemaining ) => {
	const elProgress = elCountDown.querySelector( '.progress-number' );
	if ( ! elProgress ) {
		return;
	}

	let interval = setInterval( function () {
		const hours = Math.floor( timeRemaining / 3600 );
		const minutes = Math.floor( ( timeRemaining % 3600 ) / 60 );
		const secs = timeRemaining % 60;
		const timeFormat = `${ hours }:${ minutes
			.toString()
			.padStart( 2, '0' ) }:${ secs.toString().padStart( 2, '0' ) }`;
		elProgress.innerHTML = timeFormat;

		if ( timeRemaining < 1 ) {
			clearInterval( interval );
			const action = elForm.querySelector( 'input[name=action]' );
			if ( ! action ) {
				return;
			}

			action.value = 'send';
			elForm.submit();
		} else {
			timeRemaining -= 1;
		}
	}, 1000 );
};

/**
 * Count down
 *
 * @since 4.1.2
 * @version 1.0.0
 */
(0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.lpOnElementReady)( '.assignment-countdown', ( el ) => {
	if ( lpAssignment === undefined ) {
		return;
	}

	if ( ! lpAssignment.timeRemaining ) {
		return;
	}

	const elForm = document.querySelector( 'form[name=save-assignment]' );
	if ( ! elForm ) {
		return;
	}

	// For case unlimited time
	const duration = parseInt( lpAssignment.assignmentDuration );
	if ( duration <= 0 ) {
		return;
	}

	const timeRemaining = parseInt( lpAssignment.timeRemaining );

	// Auto submit assignment when time remaining is 0
	if ( timeRemaining <= 0 ) {
		const action = elForm.querySelector( 'input[name=action]' );
		if ( ! action ) {
			return;
		}

		action.value = 'send';
		elForm.submit();

		return;
	}

	countDown( el, elForm, timeRemaining );
} );

/**
 * Delete file upload of user
 *
 * @since 4.1.2
 * @version 1.0.0
 */
const lpAssignmentDeleteUserFile = ( elDeleteFile ) => {
	const form = elDeleteFile.closest( 'form' );
	if ( ! form ) {
		return;
	}

	const elLi = elDeleteFile.closest( 'li' );

	const courseId = form.querySelector( 'input[name="course-id"]' ).value;
	const assignmentId = form.querySelector(
		'input[name="assignment-id"]'
	).value;
	const file = elDeleteFile.dataset.file;
	const messAlert = elDeleteFile.dataset.confirm;
	if ( ! confirm( messAlert ) ) {
		return;
	}

	const url =
		lpData.lp_rest_url + 'learnpress/v1/assignments/delete-submit-file';
	const dataSend = {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': lpData.nonce,
		},
		body: JSON.stringify( {
			course_id: parseInt( courseId ),
			assignment_id: parseInt( assignmentId ),
			file_id: file,
		} ),
	};

	const callBack = {
		before: () => {
			toastifyAssignment.options.className = `${ lpData.toast.classPrefix }`;
		},
		success: ( response ) => {
			const { data, status, message } = response;

			if ( status === 'success' ) {
				elLi.remove();
				const elNumberFileCanUpload = form.querySelector(
					'.assignment-number-file-can-upload'
				);
				const elInputFile = form.querySelector( '#_lp_upload_file' );

				if (
					data.number_file_can_upload &&
					data.number_file_can_upload > 0
				) {
					elNumberFileCanUpload.innerHTML =
						data.number_file_can_upload;
					elInputFile.disabled = false;
				} else {
					elInputFile.disabled = true;
				}
			}

			toastifyAssignment.options.text = message;
			toastifyAssignment.options.className += ` ${ status }`;
			toastifyAssignment.showToast();
		},
		error: ( err ) => {
			toastifyAssignment.options.text = err.message;
			toastifyAssignment.options.className += ' error';
			toastifyAssignment.showToast();
		},
		completed: () => {},
	};

	(0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.lpFetchAPI)( url, dataSend, callBack );
};

// Events
document.addEventListener( 'submit', ( e ) => {
	const target = e.target;

	// Event start assignment
	if ( target.classList.contains( 'start-assignment' ) ) {
		const btnSubmit = target.querySelector( 'button[type="submit"]' );
		(0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.lpSetLoadingEl)( btnSubmit, 1 );
	}

	// Event start assignment
	if ( target.classList.contains( 'retake-assignment' ) ) {
		const btnSubmit = target.querySelector( 'button[type="submit"]' );
		(0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.lpSetLoadingEl)( btnSubmit, 1 );
	}
} );

document.addEventListener( 'click', ( e ) => {
	const target = e.target;

	// Event delete file user upload
	if ( target.classList.contains( 'assignment-delete-user-file' ) ) {
		lpAssignmentDeleteUserFile( target );
	}

	// Event save or send answer of user
	if (
		target.classList.contains( 'lp-btn-assignment-save' ) ||
		target.classList.contains( 'lp-btn-assignment-send' )
	) {
		const form = target.closest( 'form' );
		if ( ! form ) {
			return;
		}

		const elAction = form.querySelector( 'input[name=action]' );
		if ( ! elAction ) {
			return;
		}

		const action = target.value;
		elAction.value = action;
		if ( action === 'send' ) {
			const confirmMessage = target.dataset.confirm;
			if ( ! confirm( confirmMessage ) ) {
				return;
			}
		}

		(0,_utils_js__WEBPACK_IMPORTED_MODULE_0__.lpSetLoadingEl)( target, 1 );
		form.submit();
	}
} );


/***/ })

},
/******/ function(__webpack_require__) { // webpackRuntimeModules
/******/ /* webpack/runtime/getFullHash */
/******/ (() => {
/******/ 	__webpack_require__.h = () => ("29fda47e2727127bf878")
/******/ })();
/******/ 
/******/ }
);
//# sourceMappingURL=assignment.b3cd3f45b14a0b78ce6e.hot-update.js.map