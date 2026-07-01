import useMatchMutate from '@/hooks/useMatchMutate';
import useToast from '@/hooks/useToast';
import { changeValue, couponSelection } from '@/store/coupon';
import { __ } from '@/utils/i18n';
import { Dialog, Transition } from '@headlessui/react';
import apiFetch from '@wordpress/api-fetch';
import { useEffect } from '@wordpress/element';
import { useDispatch, useSelector } from 'react-redux';
import useSWR from 'swr';
import useSWRMutation from 'swr/mutation';
import CouponContent from './CouponContent';
import EditSketeton from '@/skeleton/EditSketeton';

interface Props {
	couponId: number;
	setCouponId: ( id: number ) => void;
}

export default function EditCoupon( { couponId, setCouponId }: Props ) {
	const couponSlice = useSelector( couponSelection );
	const dispatch = useDispatch();
	const addToast = useToast();
	const matchMutate = useMatchMutate();

	const { data, error, isLoading } = useSWR(
		`/learnpress-coupon/v1/admin/coupon/${ couponId || 0 }`,
		( url ) => apiFetch( { path: url } ),
		{
			revalidateOnFocus: false,
			revalidateIfStale: false,
			refreshInterval: 0,
			revalidateOnReconnect: false,
		} );

	const { trigger, isMutating } = useSWRMutation(
		`/learnpress-coupon/v1/admin/coupon/${ couponId || 0 }`,
		( url, { arg } ) => apiFetch( { path: url, method: 'POST', data: arg } ),
		{
			onSuccess: ( data ) => {
				if ( data?.success ) {
					addToast( data?.message || 'Success', 'success' );
				} else {
					addToast( data?.message || 'Something went wrong', 'error' );
				}

				matchMutate( /^\/learnpress-coupon\/v1\/admin\/coupons/ );
			},
			onError: ( error ) => {
				addToast( error.message, 'error' );
			},
		}
	);

	useEffect( () => {
		if ( data?.success ) {
			dispatch( changeValue( { ...data.coupon } ) );
		}
	}, [ data ] );

	return (
		<Transition appear show={ !! couponId }>
			<Dialog
				open={ true }
				as="div"
				className="fixed inset-0 z-[99999] overflow-y-auto"
				onClose={ () => setCouponId( 0 ) }
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
										{ __( 'Edit Coupon' ) }
									</h2>
									<button
										onClick={ () => setCouponId( 0 ) }
										className="ml-auto px-1 outline-none cursor-pointer decoration-inherit text-center text-gray-600 bg-transparent border-none hover:border-gray-300"
									>
										<svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={ 2 }>
											<path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
										</svg>
									</button>
								</div>

								{ isLoading || ! couponSlice?.id ? <EditSketeton /> : (
									<>
										{ error ? (
											<div className="flex-1">
												<div className="bg-red-500 inline-flex items-center gap-x-2 text-white text-sm font-medium border rounded py-3 px-4">
													<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={ 1.5 } stroke="currentColor" className="w-6 h-6">
														<path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
													</svg>

													{ __( 'Something went wrong' ) }
												</div>
											</div>
										) : <CouponContent /> }
									</>
								) }

								<div className="sticky bottom-0 bg-white border-t border-x-0 border-b-0 border-gray-200 border-solid flex items-center gap-x-2 pt-5 pb-3">
									<button
										disabled={ isMutating || isLoading }
										onClick={ () => {
											if ( ! couponSlice?.title ) {
												addToast( 'Please enter coupon code', 'error' );
											} else if ( couponSlice.limitPerCoupon && couponSlice.limitPerUser && ( parseInt( couponSlice.limitPerCoupon ) < parseInt( couponSlice.limitPerUser ) ) ) {
												addToast( 'Limit per coupon must be greater than limit per user', 'error' );
											} else {
												trigger( { ...couponSlice } );
											}
										} }
										className="relative inline-flex items-center justify-center rounded h-[35px] py-0 px-[15px] text-white text-[13px] font-medium outline-none cursor-pointer decoration-inherit text-center bg-indigo-600 border border-indigo-600 border-solid shadow-sm hover:border-gray-300"
									>
										{ isMutating && (
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
										onClick={ () => setCouponId( 0 ) }
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
