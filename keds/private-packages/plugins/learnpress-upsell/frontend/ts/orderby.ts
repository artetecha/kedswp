
export default function Orderby() {
	const select = document.querySelector( 'form.learnpress-packages__ordering > select' );

	if ( ! select ) {
		return;
	}

	select.addEventListener( 'change', ( e ) => {
		const form = select.closest( 'form' );
		const input = document.createElement( 'input' );

		input.type = 'hidden';
		input.name = 'orderby';
		input.value = select.value;

		form.append( input );
		form.submit();
	} );
}
