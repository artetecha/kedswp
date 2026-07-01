import { __ } from '@/utils/i18n';
import apiFetch from '@wordpress/api-fetch';
import { useState } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';
import ReactPaginate from 'react-paginate';
import useSWR from 'swr';
import TablePackage from '@/package/TablePackage';
import AddNewPakage from './AddNewPackage';
import TableSkeleton from '@/skeleton/Table';

export default function ListPackage() {
	const [ isAddNew, setIsAddNew ] = useState( false );
	const [ pageIndex, setPageIndex ] = useState( 0 );

	const { data, error, isLoading } = useSWR( addQueryArgs( '/learnpress-package/v1/admin/packages', { paged: pageIndex } ), ( url: string ) => apiFetch( { path: url } ) );

	const isError = error || data?.error;

	return (
		<div>
			<div className="flex justify-between items-center">
				<div className="flex items-center space-x-2 mb-4">
					<h2 className="text-slate-700 m-0">Packages</h2>

					<button onClick={ () => setIsAddNew( ! isAddNew ) } className="inline-flex items-center justify-center rounded h-[35px] py-0 px-[15px] text-[13px] font-medium outline-none cursor-pointer decoration-inherit text-center text-gray-600 bg-white border border-gray-200 border-solid shadow-sm hover:border-gray-300">
						{ __( 'Add new' ) }
					</button>
				</div>

				{ /* Rồi đến lúc sẽ phải mở comment này sớm thôi - haha */ }
				{ /* <a
					href={ LP_UPSELL_LOCALIZE.admin_url + 'edit.php?post_type=learnpress_package' }
					className="ml-auto inline-flex items-center gap-x-1 text-[13px] no-underline text-gray-600 hover:text-gray-800 focus:outline-none"
					target="_blank"
					rel="noopener noreferrer"
				>
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={ 1.5 } stroke="currentColor" className="w-4 h-4">
						<path strokeLinecap="round" strokeLinejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
					</svg>

					{ __( 'Edit with WordPress Admin' ) }
				</a> */ }
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
								{ __( 'Error loading Packages.' ) }
							</p>
						) : (
							<>
								<div>
									{ data?.packages && <TablePackage packages={ data?.packages } /> }
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

			{ isAddNew && <AddNewPakage popup={ isAddNew } setPopup={ setIsAddNew } /> }
		</div>
	);
}
