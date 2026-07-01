import apiFetch from '@wordpress/api-fetch';

export default function CourseTab() {
	const loadmore = document.querySelector( '.lp-course-packages__loadmore__btn' );

	if ( loadmore ) {
		// Ajax action
		// eslint-disable-next-line @wordpress/no-global-event-listener
		document.addEventListener( 'click', async ( e ) => {
			if ( e.target !== loadmore ) {
				return;
			}
			e.preventDefault();

			const page = loadmore.getAttribute( 'data-page' );
			const courseID = loadmore.getAttribute( 'data-course-id' );
			const btnText = loadmore.textContent;

			if ( ! courseID || ! page ) {
				return;
			}

			loadmore.textContent = 'Loading...';
			loadmore.setAttribute( 'disabled', 'disabled' );

			const response = await apiFetch( {
				path: '/learnpress-package/v1/frontend/package-load-more-course',
				method: 'POST',
				data: {
					page,
					course_id: courseID,
				},
			} );

			loadmore.textContent = btnText;
			loadmore.removeAttribute( 'disabled' );

			if ( response.html ) {
				const courseList = document.querySelector( '.lp-course-packages__list' );

				if ( courseList ) {
					courseList.insertAdjacentHTML( 'beforeend', response.html );

					if ( response.total_page === parseInt( page ) ) {
						// Remove load more button
						loadmore.remove();
					} else {
						// Update page
						loadmore.setAttribute( 'data-page', parseInt( page ) + 1 );
					}
				}
			}
		} );
	}
}
