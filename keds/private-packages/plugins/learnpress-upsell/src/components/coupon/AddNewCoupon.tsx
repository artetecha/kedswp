import { __, sprintf } from '@/utils/i18n';
import { Dialog, Transition } from '@headlessui/react';
import { useState, useEffect } from '@wordpress/element';
import useToast from '@/hooks/useToast';
import apiFetch from '@wordpress/api-fetch';
import { couponSelection, reset } from '@/store/coupon';
import useMatchMutate from '@/hooks/useMatchMutate';
import { useDispatch, useSelector } from 'react-redux';
import CouponContent from './CouponContent';

export default function AddNewCoupon( { popup, setPopup } ) {
	const couponSlice = useSelector( couponSelection );

	const dispatch = useDispatch();

	useEffect( () => {
		dispatch( reset() );
	}, [] );

	const addToast = useToast();

	const [ loading, setLoading ] = useState( '' );

	const matchMutate = useMatchMutate();

	const onSave = async () => {
		setLoading( 'save' );

		try {
			if ( ! couponSlice?.title ) {
				throw new Error( __( 'Please enter coupon code' ) );
			}

			if ( couponSlice.limitPerCoupon && couponSlice.limitPerUser && ( parseInt( couponSlice.limitPerCoupon ) < parseInt( couponSlice.limitPerUser ) ) ) {
				throw new Error( __( 'Limit per coupon must be greater than limit per user' ) );
			}

			const response = await apiFetch( {
				path: '/learnpress-coupon/v1/admin/coupon/0',
				method: 'POST',
				data: { ...couponSlice },
			} );

			if ( ! response?.success ) {
				throw new Error( response?.message || 'Something went wrong' );
			} else {
				addToast( response?.message || 'Success', 'success' );

				setPopup( false );
			}

			matchMutate( /^\/learnpress-coupon\/v1\/admin\/coupons/ );
		} catch ( error ) {
			addToast( error.message, 'error' );
		}

		setLoading( '' );
	};

	return (
		<Transition appear show={ popup }>
			<Dialog
				open={ true }
				as="div"
				className="fixed inset-0 z-[99999] overflow-y-auto"
				onClose={ () => setPopup( false ) }
			>
				<div className="min-h-screen flex items-center justify-center px-4 text-center antialiased font-sans">
					<Transition.Child
						enter="ease-out duration-300"
						enterFrom="opacity-0"
						enterTo="opacity-100"
						leave="ease-in duration-200"
						leaveFrom="opacity-100"
						leaveTo="opacity-0"
					>
						<div className="fixed inset-0 bg-slate-500/50" />
					</Transition.Child>

					<Transition.Child
						enter="ease-out duration-300"
						enterFrom="opacity-0 scale-95"
						enterTo="opacity-100 scale-100"
						leave="ease-in duration-200"
						leaveFrom="opacity-100 scale-100"
						leaveTo="opacity-0 scale-95"
					>
						<div className="flex flex-col w-full min-w-[1100px] min-h-[500px] h-full max-w-md p-6 pb-3 my-8 text-left align-middle transition-all transform bg-white shadow-xl rounded-md">
							<div className="flex flex-col flex-1 h-full">
								<div className="flex items-center justify-between mb-4 pb-2 border-b">
									<h2 className="focus:outline-none text-slate-700 m-0 p-0">
										{ __( 'Add new coupon' ) }
									</h2>
									<button
										onClick={ () => setPopup( false ) }
										className="ml-auto px-1 outline-none cursor-pointer decoration-inherit text-center text-gray-600 bg-transparent border-none hover:border-gray-300"
									>
										<svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={ 2 }>
											<path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
										</svg>
									</button>
								</div>

								<CouponContent />

								<div className="sticky bottom-0 bg-white border-t border-x-0 border-b-0 border-gray-200 border-solid flex items-center gap-x-2 pt-5 pb-3">
									<button
										disabled={ !! loading }
										onClick={ onSave }
										className="relative inline-flex items-center justify-center rounded h-[35px] py-0 px-[15px] text-white text-[13px] font-medium outline-none cursor-pointer decoration-inherit text-center bg-indigo-600 border border-indigo-600 border-solid shadow-sm hover:border-gray-300"
									>
										{ loading === 'save' && (
											<span className="absolute inset-0 bg-indigo-600 rounded-md">
												<span className="flex items-center justify-center absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
													<svg className="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
														<circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
														<path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
													</svg>
												</span>
											</span>
										) }
										{ __( 'Save' ) }
									</button>
									<button
										disabled={ !! loading }
										onClick={ () => setPopup( false ) }
										className="inline-flex items-center justify-center rounded h-[35px] py-0 px-[15px] text-gray-600 text-[13px] font-medium outline-none cursor-pointer decoration-inherit text-center bg-white border border-gray-200 border-solid shadow-sm"
									>
										{ __( 'Close' ) }
									</button>
								</div>
							</div>
						</div>
					</Transition.Child>
				</div>
			</Dialog>
		</Transition>
	);
}
