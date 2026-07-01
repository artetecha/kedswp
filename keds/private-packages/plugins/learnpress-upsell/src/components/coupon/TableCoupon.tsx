import useToast from '@/hooks/useToast';
import { __ } from '@/utils/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useState } from '@wordpress/element';
import { Popover } from '@headlessui/react';
import useMatchMutate from '@/hooks/useMatchMutate';
import EditCoupon from './EditCoupon';

const STATUS = {
	draft: {
		label: __( 'Draft' ),
		bg: 'bg-gray-200',
		color: 'text-gray-700',
	},
	trash: {
		label: __( 'Trash' ),
		bg: 'bg-red-400',
		color: 'text-white',
	},
	pending: {
		label: __( 'Pending' ),
		bg: 'bg-yellow-400',
		color: 'text-white',
	},
	future: {
		label: __( 'Scheduled' ),
		bg: 'bg-blue-400',
		color: 'text-white',
	},
	private: {
		label: __( 'Private' ),
		bg: 'bg-gray-400',
		color: 'text-white',
	},
};

export default function TableCoupon( { coupons } ) {
	const [ couponId, setCouponId ] = useState( 0 );
	const [ loading, setLoading ] = useState( '' );

	const matchMutate = useMatchMutate();

	const addToast = useToast();

	const onDelete = async ( id, trash = true ) => {
		setLoading( 'delete' );

		try {
			const response = await apiFetch( {
				path: '/learnpress-coupon/v1/admin/delete',
				method: 'POST',
				data: {
					id,
					trash,
				},
			} );

			if ( ! response.success ) {
				throw new Error( response?.message || 'Something went wrong' );
			} else {
				addToast( response?.message || 'Success', 'success' );
			}

			matchMutate( /^\/learnpress-coupon\/v1\/admin\/coupons/ );
		} catch ( e ) {
			addToast( e.message, 'error' );
		}

		setLoading( '' );
	};

	return (
		<div className="px-0">
			<div className="relative rounded border border-gray-200 border-solid">
				<table className="w-full border-separate border-spacing-0">
					<thead>
						<tr>
							<th className="sticky top-0 z-10 px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase bg-gray-50 border-b border-x-0 border-t-0 border-gray-200 border-solid rounded-tl">
								{ __( 'Code' ) }
							</th>
							<th className="sticky top-0 z-10 px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase bg-gray-50 border-b border-x-0 border-t-0 border-gray-200 border-solid">
								{ __( 'Coupon type' ) }
							</th>
							<th className="sticky top-0 z-10 px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase bg-gray-50 border-b border-x-0 border-t-0 border-gray-200 border-solid">
								{ __( 'Coupon amount' ) }
							</th>
							<th className="sticky top-0 z-10 px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase bg-gray-50 border-b border-x-0 border-t-0 border-gray-200 border-solid">
								{ __( 'Description' ) }
							</th>
							<th className="sticky top-0 z-10 px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase bg-gray-50 border-b border-x-0 border-t-0 border-gray-200 border-solid">
								{ __( 'Usage / Limit' ) }
							</th>
							<th className="sticky top-0 z-10 px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase bg-gray-50 border-b border-x-0 border-t-0 border-gray-200 border-solid">
								{ __( 'Expiry date' ) }
							</th>
							<th className="sticky top-0 z-10 px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase bg-gray-50 border-b border-x-0 border-t-0 border-gray-200 border-solid rounded-tr"></th>
						</tr>
					</thead>
					<tbody className="[&>tr:last-child>td]:border-b-0">
						{ coupons.length === 0 ? (
							<tr>
								<td colSpan={ 7 } className="px-6 py-4 whitespace-nowrap border-b border-x-0 border-t-0 border-gray-200 border-solid">
									{ __( 'No coupons found.' ) }
								</td>
							</tr>
						) : (
							<>
								{ coupons.map( ( coupon ) => (
									<tr key={ coupon.id }>
										<td className="px-6 py-4 whitespace-nowrap border-b max-w-[300px] truncate border-x-0 border-t-0 border-gray-200 border-solid align-top">
											<div
												className="flex items-center text-gray-800 hover:text-gray-600 truncate cursor-pointer"
												onClick={ () => setCouponId( coupon.id ) }
												onKeyDown={ () => setCouponId( coupon.id ) }
												role="button"
												tabIndex={ 0 }
											>
												{ coupon.title || '(no title)' }

												{ STATUS?.[ coupon.status ]?.label && (
													<span className={ `ml-2 text-xs px-2 py-1 leading-4 rounded ${ STATUS?.[ coupon.status ]?.bg || '' } ${ STATUS?.[ coupon.status ]?.color || '' }` }>
														{ STATUS?.[ coupon.status ]?.label }
													</span>
												) }
											</div>
										</td>
										<td className="max-w-[300px] px-6 py-4 whitespace-nowrap border-b border-x-0 border-t-0 border-gray-200 border-solid align-top">
											{ coupon.discountType === 'percent' ? (
												<span className="text-xs px-2 py-1 leading-4 rounded bg-gray-200 text-gray-700">
													{ __( 'Percentage discount' ) }
												</span>
											) : (
												<span className="text-xs px-2 py-1 leading-4 rounded bg-gray-200 text-gray-700">
													{ __( 'Fixed discount' ) }
												</span>
											) }
										</td>
										<td className="px-6 py-4 whitespace-nowrap border-b border-x-0 border-t-0 border-gray-200 border-solid align-top">
											{ coupon.discountAmount }
										</td>
										<td className="px-6 py-4 whitespace-normal break-words border-b border-x-0 border-t-0 border-gray-200 border-solid align-top">									
											{ coupon.description }									
										</td>
										<td
											className="px-6 py-4 whitespace-nowrap border-b border-x-0 border-t-0 border-gray-200 border-solid align-top"
											dangerouslySetInnerHTML={ { __html: `${ coupon.usageCount || 0 } / ${ coupon.limitPerCoupon ? parseInt( coupon.limitPerCoupon ) : '&infin;' }` } }
										>
										</td>
										<td className="px-6 py-4 whitespace-nowrap border-b border-x-0 border-t-0 border-gray-200 border-solid align-top">
											{ coupon.discountEndDate || __( 'Never' ) }
										</td>
										<td className="px-6 py-4 whitespace-nowrap border-b border-x-0 border-t-0 border-gray-200 border-solid align-top">
											<div className="flex items-center gap-x-2">
												<button
													onClick={ () => setCouponId( coupon.id ) }
													className="relative group h-8 w-8 inline-flex items-center justify-center font-medium text-sm text-gray-500 border border-gray-200 border-solid rounded hover:bg-indigo-50 shadow-sm bg-transparent cursor-pointer"
												>
													<div className=" opacity-0 pointer-events-none group-hover:opacity-100 group-hover:pointer-events-auto transition duration-300 mb-1 absolute bottom-full z-20 bg-gray-800 text-white text-sm rounded px-2.5 py-1">
														{ __( 'Edit' ) }
													</div>
													<svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={ 2 }>
														<path strokeLinecap="round" strokeLinejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
													</svg>
												</button>
												<Popover className="relative">
													{ ( { open } ) => (
														<>
															<Popover.Button className="relative group h-8 w-8 inline-flex items-center justify-center font-medium text-sm text-gray-500 border border-gray-200 border-solid rounded hover:bg-indigo-50 shadow-sm bg-transparent cursor-pointer">
																<div className=" opacity-0 pointer-events-none group-hover:opacity-100 group-hover:pointer-events-auto transition duration-300 mb-1 absolute bottom-full z-20 bg-gray-800 text-white text-sm rounded px-2.5 py-1">
																	{ __( 'Delete' ) }
																</div>
																<svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={ 2 }>
																	<path strokeLinecap="round" strokeLinejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
																</svg>
															</Popover.Button>
															<Popover.Panel className="absolute z-50 bottom-full w-48 mb-3 right-0 px-0">
																<div className="text-center overflow-hidden p-6 bg-gray-800 rounded-md shadow-xl ring-1 ring-black ring-opacity-5">
																	<div className="relative text-sm text-gray-200 font-semibold mb-4">
																		{ __( 'Are you sure?' ) }
																	</div>

																	{ loading === 'delete' ? (
																		<span className="bg-gray-800 rounded-md">
																			<span className="flex items-center justify-center">
																				<svg className="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
																					<circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
																					<path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
																				</svg>
																			</span>
																		</span>
																	) : (
																		<div className="flex items-center gap-x-2 justify-center">
																			{ coupon.status !== 'trash' && (
																				<button onClick={ () => onDelete( coupon.id, true ) } className="px-2 py-1 leading-4 rounded-sm border-none shadow-none bg-gray-200 text-xs font-medium text-red-600 cursor-pointer">
																					{ __( 'Trash' ) }
																				</button>
																			) }
																			<button onClick={ () => onDelete( coupon.id, false ) } className="px-2 py-1 leading-4 rounded-sm border-none shadow-none bg-gray-200 text-xs font-medium text-red-600 cursor-pointer">
																				{ __( 'Delete' ) }
																			</button>
																		</div>
																	) }
																</div>
															</Popover.Panel>
														</>
													) }
												</Popover>
											</div>
										</td>
									</tr>
								) ) }
							</>
						) }
					</tbody>
				</table>
			</div>
			{ !! couponId && <EditCoupon couponId={ couponId } setCouponId={ setCouponId } /> }
		</div>
	);
}
