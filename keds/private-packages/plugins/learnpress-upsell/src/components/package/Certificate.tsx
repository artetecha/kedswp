import { useState, useEffect, useRef } from '@wordpress/element';
import { useSelector, useDispatch } from 'react-redux';
import { changeValue, packageSelection } from '@/store/package';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@/utils/i18n';
import useSWR from 'swr';

export default function Certificate() {
	const dispatch = useDispatch();
	const packageSlice = useSelector( packageSelection );

	const [ certificate, setCertificate ] = useState( [] );

	const certificateRef = useRef( null );

	const { data, error, isLoading } = useSWR(
		`/lp/certificate/admin/v1/course-metabox`,
		( url ) => apiFetch( { path: url } ),
		{
			revalidateOnFocus: false,
		}
	);

	useEffect( () => {
		if ( data ) {
			setCertificate( data?.data || [] );
		}
	}, [ data ] );

	useEffect( () => {
		if ( certificateRef.current && window.LP_Certificate ) {
			const listCert = certificateRef.current.querySelectorAll( '.lp-certificate-list' );

			listCert.forEach( ( item ) => {
				item.parentNode.querySelector( '.certificate-result' )?.remove();

				const id = `#${ item.getAttribute( 'id' ) }`;
				const dataCer = item.querySelector( '.lp-data-config-cer' ) && JSON.parse( item.querySelector( '.lp-data-config-cer' ).value );

				window.LP_Certificate( id, dataCer );

				item.querySelector( '.canvas-container' )?.remove();
			} );
		}
	}, [ certificateRef, certificate ] );

	if ( isLoading ) {
		return (
			<div className="flex items-center gap-x-2">
				<span className="flex items-center justify-center">
					<svg className="animate-spin h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
						<circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
						<path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
					</svg>
				</span>
				<span className="text-gray-600">{ __( 'Loading…' ) }</span>
			</div>
		);
	}

	if ( error || certificate.length === 0 ) {
		return (
			<div className="mt-2 flex items-center gap-x-1 text-red-600 text-sm">
				<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={ 1.5 } stroke="currentColor" className="w-5 h-5">
					<path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
				</svg>

				{ __( 'Error loading certificate.' ) }
			</div>
		);
	}

	return (
		<div className="relative" ref={ certificateRef }>
			<div className="flex items-center flex-wrap gap-6">
				{ certificate.map( ( item ) => (
					<div className={ `relative w-56 rounded-lg shadow overflow-hidden [&>img]:object-cover [&>img]:object-center [&>img]:w-full [&>img]:align-middle [&>img]:h-40 ${ packageSlice?.certificateID === item.id ? 'border-solid border-2 border-indigo-600' : 'border' }` } key={ item.id }>
						<div className="lp-certificate-list" id={ `lp-certificate-${ item.id }` } data-id={ item.id }>
							<div className="certificate-preview-inner">
								<canvas></canvas>
							</div>
							<input className="lp-data-config-cer" type="hidden" value={ JSON.stringify( item?.data ) }></input>
						</div>

						<div className="flex items-center justify-between text-sm px-3 py-2 gap-x-2 bg-gray-100">
							<div className="truncate text-gray-600">{ item?.data?.name }</div>
							<div className="flex items-center gap-x-2">
								{ packageSlice?.certificateID === item.id ? (
									<button onClick={ () => dispatch( changeValue( { certificateID: 0 } ) ) } className="inline-flex items-center justify-center rounded h-[30px] py-0 px-[10px] text-red-600 text-[12px] font-medium outline-none cursor-pointer no-underline text-center bg-white border border-gray-200 border-solid shadow-sm hover:border-gray-300">
										{ __( 'Remove' ) }
									</button>
								) : (
									<button onClick={ () => dispatch( changeValue( { certificateID: item.id } ) ) } className="inline-flex items-center justify-center rounded h-[30px] py-0 px-[10px] text-gray-600 text-[12px] font-medium outline-none cursor-pointer no-underline text-center bg-white border border-gray-200 border-solid shadow-sm hover:border-gray-300">
										{ __( 'Assign' ) }
									</button>
								) }
							</div>
						</div>
					</div>
				) ) }
			</div>
		</div>
	);
}
