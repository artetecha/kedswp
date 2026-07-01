import { __ } from '@/utils/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useState } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';
import ReactPaginate from 'react-paginate';
import useSWR from 'swr';
import TableCoupon from '@/coupon/TableCoupon';
import TableSkeleton from '@/skeleton/Table';
import AddNewCoupon from './AddNewCoupon';

export default function ListCoupon() {
	const [ isAddNew, setIsAddNew ] = useState( false );
	const [ pageIndex, setPageIndex ] = useState( 0 );

	const { data, error, isLoading } = useSWR( addQueryArgs( '/learnpress-coupon/v1/admin/coupons', { paged: pageIndex } ), ( url: string ) => apiFetch( { path: url } ) );

	const isError = error || data?.error;

	return (
		<div>
			<div className="flex justify-between items-center">
				<div className="flex items-center space-x-2 mb-4">
					<h2 className="text-slate-700 m-0">Coupons</h2>

					<button onClick={ () => setIsAddNew( ! isAddNew ) } className="inline-flex items-center justify-center rounded h-[35px] py-0 px-[15px] text-[13px] font-medium outline-none cursor-pointer decoration-inherit text-center text-gray-600 bg-white border border-gray-200 border-solid shadow-sm hover:border-gray-300">
						{ __( 'Add new' ) }
					</button>
				</div>
				<p><i>{ __('Feature only apply for payment via LearnPress') }</i></p>
			</div>

			<div>
				{ isLoading ? (
					<div className="relative rounded overflow-hidden border [&>*:last-child>*]:border-b-0 border-solid border-gray-200">
						<TableSkeleton />
						<TableSkeleton />
						<TableSkeleton />
						<TableSkeleton />
						<TableSkeleton />
						<TableSkeleton />
					</div>
				) : (
					<>
						{ isError ? (
							<p className="w-[260px] text-red-700 bg-red-100 text-sm font-medium border border-solid border-red-400 rounded py-3 px-4">
								{ __( 'Error loading Coupons.' ) }
							</p>
						) : (
							<>
								<div>
									{ data?.coupons && <TableCoupon coupons={ data?.coupons } /> }
								</div>

								<div className="mt-8 mb-4">
									{ data?.pages > 1 && (
										<ReactPaginate
											breakLabel="..."
											nextLabel={ __( 'Next' ) }
											initialPage={ pageIndex }
											onPageChange={ ( e ) => setPageIndex( e.selected ) }
											pageRangeDisplayed={ 2 }
											marginPagesDisplayed={ 2 }
											pageCount={ parseInt( data.pages ) || 0 }
											previousLabel={ __( 'Prev' ) }
											activeLinkClassName="bg-gray-200"
											containerClassName="flex items-center gap-x-3"
											pageLinkClassName="text-[13px] font-medium flex items-center justify-center h-[35px] w-[42px] cursor-pointer text-slate-600 border border-gray-300 border-solid shadow-sm rounded"
											previousLinkClassName="text-[13px] font-medium flex items-center justify-center h-[35px] cursor-pointer text-slate-600 px-4 border border-gray-300 border-solid shadow-sm rounded"
											nextLinkClassName="text-[13px] font-medium flex items-center justify-center h-[35px] cursor-pointer text-slate-600 px-4 border border-gray-300 border-solid shadow-sm rounded"
											disabledLinkClassName="border-gray-200 bg-gray-200 cursor-not-allowed"
											renderOnZeroPageCount={ null }
										/>
									) }
								</div>
							</>
						) }
					</>
				) }
			</div>

			{ isAddNew && <AddNewCoupon popup={ isAddNew } setPopup={ setIsAddNew } /> }
		</div>
	);
}
