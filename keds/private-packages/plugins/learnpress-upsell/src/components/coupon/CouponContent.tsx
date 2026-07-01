import DateTime from '@/fields/DateTime';
import { changeValue, couponSelection } from '@/store/coupon';
import { sprintf, __ } from '@/utils/i18n';
import { Combobox } from '@headlessui/react';
import apiFetch from '@wordpress/api-fetch';
import { useCallback, useState } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';
import { format } from 'date-fns';
import { debounce } from 'lodash';
import { useDispatch, useSelector } from 'react-redux';
import useSWR, { useSWRConfig } from 'swr';

const STATUS = [
	{
		id: 'publish',
		title: __( 'Published' ),
	},
	{
		id: 'draft',
		title: __( 'Draft' ),
	},
	{
		id: 'pending',
		title: __( 'Pending' ),
	},
	{
		id: 'trash',
		title: __( 'Trash' ),
	},
];

const DISCOUNT_TYPE = [
	{
		id: 'percent',
		title: __( 'Percentage discount' ),
	},
	{
		id: 'fixed',
		title: __( 'Fixed discount' ),
	},
];

export default function CouponContent() {
	const dispatch = useDispatch();
	const couponSlice = useSelector( couponSelection );

	const [ searchCourse, setSearchCourse ] = useState( '' );
	const [ searchPackage, setSearchPackage ] = useState( '' );
	const [ searchCourseCategories, setSearchCourseCategories ] = useState( '' );

	const { data: dataCourses, error: errorCourses } = useSWR( addQueryArgs( '/learnpress/v1/courses', { search: searchCourse || '' } ), ( url: string ) => apiFetch( { path: url } ) );

	const { data: dataPackages, error: errorPackages } = useSWR( addQueryArgs( '/learnpress-coupon/v1/admin/get-packages', { search: searchPackage } ), ( url: string ) => apiFetch( { path: url } ) );

	const { data: dataCourseCategories, error: errorCourseCategories } = useSWR( addQueryArgs( '/wp/v2/course_category', { search: searchCourseCategories } ), ( url: string ) => apiFetch( { path: url } ) );

	const { mutate } = useSWRConfig();

	const searchCoursesFn = useCallback( debounce( ( searchInput: string ) => setSearchCourse( searchInput ), 600 ), [] );
	const searchPackagesFn = useCallback( debounce( ( searchInput: string ) => setSearchPackage( searchInput ), 600 ), [] );
	const searchCourseCategoriesFn = useCallback( debounce( ( searchInput: string ) => setSearchCourseCategories( searchInput ), 600 ), [] );

	const onSelectCourse = ( value ) => {
		const newSelected = [ ...couponSlice.includeCourses, ...dataCourses.map( ( item ) => ( { id: item.id, name: item?.name || '' } ) ) ];

		const newSelectedItem = value.map( ( id ) => {
			const item = newSelected.find( ( item ) => item.id === id );

			return {
				id: item.id,
				name: item?.name || '',
			};
		} );

		dispatch( changeValue( { includeCourses: newSelectedItem } ) );
	};

	const onRemoveCourse = ( id ) => {
		const newSelectedItem = couponSlice.includeCourses.filter( ( item ) => item.id !== id );
		dispatch( changeValue( { includeCourses: newSelectedItem } ) );
	};

	return (
		<div className="grid grid-cols-12 gap-6 mb-6 flex-1">
			<div className="col-span-6">
				<label htmlFor="title" className="block text-sm font-medium text-gray-700">
					{ __( 'Coupon code' ) }
				</label>
				<div className="flex items-center gap-x-2">
					<input
						id="title"
						value={ couponSlice.title }
						onChange={ ( e ) => dispatch( changeValue( { title: e.target.value } ) ) }
						type="text"
						className="flex-1 px-3 py-2 mt-1 text-gray-900 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
					/>
					<button
						onClick={ () => {
							const couponCode = sprintf( '%s%s', Math.random().toString( 36 ).substring( 2, 8 ).toUpperCase(), Math.floor( 100 + Math.random() * 900 ) );
							dispatch( changeValue( { title: couponCode } ) );
						} }
						type="button"
						className="mt-1 flex items-center gap-x-1 text-gray-700 border border-solid border-gray-300 rounded-md py-2 px-3 text-sm bg-transparent shadow-none outline-none cursor-pointer hover:bg-slate-100"
					>
						{ __( 'Generate coupon code' ) }
					</button>
				</div>
			</div>

			<div className="col-span-12">
				<label htmlFor="description" className="block text-sm font-medium text-gray-700">
					{ __( 'Description' ) }
				</label>
				<textarea
					id="description"
					value={ couponSlice.description }
					onChange={ ( e ) => dispatch( changeValue( { description: e.target.value } ) ) }
					className="mt-1 px-3 py-2 text-gray-900 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
				/>
			</div>

			<div className="col-span-12">
				<div className="grid grid-cols-8 gap-6">
					<div className="col-span-3">
						<label htmlFor="status" className="block text-sm font-medium text-gray-700">
							{ __( 'Status' ) }
						</label>
						<select
							id="status"
							className="mt-1 py-1.5 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm text-gray-800 text-sm border-gray-300 rounded-md"
							value={ couponSlice.status }
							onChange={ ( e ) => dispatch( changeValue( { status: e.target.value } ) ) }
							onBlur={ ( e ) => dispatch( changeValue( { status: e.target.value } ) ) }
						>
							{ STATUS.map( ( option ) => (
								<option
									key={ option.id }
									value={ option.id }
								>
									{ option.title }
								</option>
							) ) }
						</select>
					</div>

					<div className="col-span-5">
						<label htmlFor="publishDate" className="block text-sm font-medium text-gray-700">
							{ __( 'Publish Date' ) }
						</label>
						<div className="mt-1 flex gap-2 items-center">
							{ couponSlice.publishDate ? (
								<div className="flex items-center gap-x-2">
									<DateTime
										id="publishDate"
										value={ couponSlice.publishDate }
										onChange={ ( value ) => dispatch( changeValue( { publishDate: value } ) ) }
										showTime={ true }
										minDate={ format( new Date(), 'yyyy-MM-dd' ) }
									/>
									<button onClick={ () => dispatch( changeValue( { publishDate: '' } ) ) } className="m-0 p-0 flex items-center gap-x-1 text-gray-800 border-none text-[13px] bg-transparent shadow-none outline-none cursor-pointer">
										<svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={ 2 }>
											<path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
										</svg>
										{ __( 'Cancel' ) }
									</button>
								</div>
							) : (
								<button onClick={ () => dispatch( changeValue( { publishDate: `${ format( new Date(), 'yyyy-MM-dd' ) } 00:00` } ) ) } className="flex items-center gap-x-2 text-gray-500 border border-solid border-gray-300 rounded-md py-1.5 px-3 text-sm bg-transparent shadow-none outline-none cursor-pointer">
									<span className="text-gray-800">{ __( 'Immediately' ) }</span>
									<svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={ 2 }>
										<path strokeLinecap="round" strokeLinejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
									</svg>
								</button>
							) }
						</div>
					</div>
				</div>
			</div>

			<div className="col-span-12">
				<div className="block text-sm font-medium text-gray-700">
					{ __( 'General' ) }
				</div>

				<div className=" grid grid-cols-6 gap-3 mt-2 p-5 border border-solid border-gray-200 rounded-md">
					<div className="col-span-3 flex items-center gap-2">
						<label htmlFor="discount-type" className="min-w-[100px] text-sm font-medium text-gray-700">
							{ __( 'Discount type' ) }
						</label>
						<select
							id="discount-type"
							className="mt-1 py-1.5 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm text-gray-800 text-sm border-gray-300 rounded-md"
							value={ couponSlice.discountType }
							onChange={ ( e ) => dispatch( changeValue( { discountType: e.target.value } ) ) }
							onBlur={ ( e ) => dispatch( changeValue( { discountType: e.target.value } ) ) }
						>
							{ DISCOUNT_TYPE.map( ( option ) => (
								<option
									key={ option.id }
									value={ option.id }
								>
									{ option.title }
								</option>
							) ) }
						</select>
					</div>
					<div className="col-span-3 flex items-center gap-2">
						<label htmlFor="discount-start" className="min-w-[140px] text-sm font-medium text-gray-700">
							{ __( 'Discount start date' ) }
						</label>
						<DateTime
							id="discount-start"
							value={ couponSlice.discountStartDate }
							onChange={ ( value ) => dispatch( changeValue( { discountStartDate: value } ) ) }
							showTime={ true }
							minDate={ format( new Date(), 'yyyy-MM-dd' ) }
							enableClear={ true }
						/>
					</div>

					<div className="col-span-3 flex items-center gap-2">
						<label htmlFor="discount-amount" className="min-w-[100px] text-sm font-medium text-gray-700">
							{ __( 'Amount' ) }
						</label>
						<input
							id="discount-amount"
							value={ couponSlice.discountAmount }
							onChange={ ( e ) => {
								const value = e.target.value;

								if ( couponSlice.discountType === 'percent' ) {
									if ( value.match( /^([1-9][0-9]?|100)$/ ) ) {
										dispatch( changeValue( { discountAmount: value } ) );
									} else {
										dispatch( changeValue( { discountAmount: '' } ) );
									}
								} else {
									dispatch( changeValue( { discountAmount: value } ) );
								}
							} }
							step={ couponSlice.discountType === 'percent' ? 1 : 0.01 }
							type="number"
							min={ 0 }
							max={ couponSlice.discountType === 'percent' ? 100 : 99999 }
							className="max-w-[190px] flex-1 px-3 py-1.5 mt-1 text-gray-900 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
						/>
					</div>

					<div className="col-span-3 flex items-center gap-2">
						<label htmlFor="discount-end" className="min-w-[140px] text-sm font-medium text-gray-700">
							{ __( 'Discount end date' ) }
						</label>
						<DateTime
							id="discount-end"
							value={ couponSlice.discountEndDate || '' }
							onChange={ ( value ) => dispatch( changeValue( { discountEndDate: value } ) ) }
							showTime={ true }
							minDate={ format( couponSlice.discountStartDate ? new Date( couponSlice.discountStartDate ) : new Date(), 'yyyy-MM-dd' ) }
							enableClear={ true }
						/>
					</div>
				</div>
			</div>

			<div className="col-span-12">
				<div className="block text-sm font-medium text-gray-700">
					{ __( 'Limit' ) }
				</div>

				<div className=" grid grid-cols-6 gap-3 mt-2 p-5 border border-solid border-gray-200 rounded-md">
					<div className="col-span-6 flex items-center gap-2">
						<label htmlFor="limit-per-coupon" className="min-w-[200px] text-sm font-medium text-gray-700">
							{ __( 'Usage limit per coupon' ) }
						</label>
						<input
							id="limit-per-coupon"
							placeholder="Unlimited usage"
							value={ couponSlice.limitPerCoupon || '' }
							onChange={ ( e ) => dispatch( changeValue( { limitPerCoupon: e.target.value } ) ) }
							type="number"
							min={ 0 }
							className="max-w-[190px] flex-1 px-3 py-1.5 mt-1 text-gray-900 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
						/>
						<span>
							{ __( 'How many times can this coupon be used in total.' ) }
						</span>
					</div>

					<div className="col-span-6 flex items-center gap-2">
						<label htmlFor="limit-per-user" className="min-w-[200px] text-sm font-medium text-gray-700">
							{ __( 'Usage limit per user' ) }
						</label>
						<input
							id="limit-per-user"
							placeholder="Unlimited usage"
							value={ couponSlice.limitPerUser || '' }
							onChange={ ( e ) => dispatch( changeValue( { limitPerUser: e.target.value } ) ) }
							type="number"
							min={ 0 }
							className="max-w-[190px] flex-1 px-3 py-1.5 mt-1 text-gray-900 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
						/>
						<span>
							{ __( 'How many times can this coupon be used per user.' ) }
						</span>
					</div>
				</div>
			</div>

			<div className="col-span-12">
				<div className="block text-sm font-medium text-gray-700">
					{ __( 'Usage restriction' ) }
				</div>

				<div className=" grid grid-cols-6 gap-3 mt-2 p-5 border border-solid border-gray-200 rounded-md">
					<div className="col-span-6 flex items-center gap-2">
						<label htmlFor="include-packages" className="min-w-[200px] text-sm font-medium text-gray-700">
							{ __( 'Include packages' ) }
						</label>
						<div className="mt-1 flex items-center justify-center">
							<Combobox
								value={ couponSlice.includePackages.map( ( item: any ) => item.id ) }
								onChange={ ( value ) => {
									const newSelected = [ ...couponSlice.includePackages, ...dataPackages.map( ( item: any ) => ( { id: item.id, name: item?.name || '' } ) ) ];

									const newSelectedItem = value.map( ( id ) => {
										const item = newSelected.find( ( item ) => item.id === id );

										return {
											id: item.id,
											name: item?.name || '',
										};
									} );

									dispatch( changeValue( { includePackages: newSelectedItem } ) );
								} }
								multiple
								nullable
							>
								<div className="relative w-full border border-solid border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-700">
									{ couponSlice.includePackages.length > 0 && (
										<ul className="m-0 mb-2 p-1.5 flex flex-wrap gap-1.5 text-gray-800 text-sm">
											{ couponSlice.includePackages.map( ( item: any ) => (
												<li key={ item.id } className="inline-flex item-center gap-1 rounded bg-gray-200 py-1 px-2 m-0">
													<button
														className="flex items-center hover:text-red-600 outline-none border-none p-0 m-0 bg-transparent cursor-pointer no-underline"
														onClick={ () => {
															const newSelectedItem = couponSlice.includePackages.filter( ( itemRemove : any ) => itemRemove.id !== item.id );
															dispatch( changeValue( { includePackages: newSelectedItem } ) );
														} }
													>
														<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={ 1.5 } stroke="currentColor" className="w-4 h-4">
															<path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
														</svg>
													</button>
													{ item?.name || '' }
												</li>
											) ) }
										</ul>
									) }
									<div className="relative flex items-center">
										<span className="absolute flex items-center left-0 pl-3 pointer-events-none text-gray-500">
											<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={ 1.5 } stroke="currentColor" className="w-5 h-5">
												<path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
											</svg>
										</span>
										<Combobox.Input
											id="select-course"
											placeholder={ __( 'Search package…' ) }
											className="px-3 pl-9 py-2 text-gray-900 focus:border-none focus:outline-none outline-none block w-full shadow-sm text-sm border-gray-300 border-0 rounded-md"
											onChange={ ( e ) => searchPackagesFn( e.target.value ) }
										/>
									</div>
									{ dataPackages && dataPackages.length > 0 && (
										<Combobox.Options className="absolute z-10 w-full mt-1 bg-white shadow-lg rounded-md border border-gray-300 border-solid text-sm">
											{ dataPackages.map( ( option: any ) => (
												<Combobox.Option
													key={ option.id }
													value={ option.id }
													className={ ( { active } ) =>
														`relative cursor-default select-none py-2 pl-10 pr-4 ${ active ? 'bg-indigo-600 text-white' : 'text-gray-900'
														}`
													}
												>
													{ ( { selected, active } ) => (
														<>
															<span className={ `block truncate ${ selected ? 'font-medium' : 'font-normal' }` }>
																{ option?.name || '' }
															</span>

															{ selected ? (
																<span
																	className={ `absolute inset-y-0 left-0 flex items-center pl-3 ${ active ? 'text-white' : 'text-indigo-600' }` }>
																	<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={ 1.5 } stroke="currentColor" className="w-5 h-5">
																		<path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
																	</svg>

																</span>
															) : null }
														</>
													) }
												</Combobox.Option>
											) ) }
										</Combobox.Options>
									) }
								</div>
							</Combobox>
						</div>

						<span className="group relative cursor-pointer">
							<div className="min-w-[200px] text-center opacity-0 pointer-events-none group-hover:opacity-100 group-hover:pointer-events-auto transition duration-300 mb-2 absolute left-1/2 -translate-x-1/2 bottom-full z-20 bg-gray-800 text-white text-[12px] rounded px-2.5 py-1">
								{ __( 'Packages will be applied to the cart when the coupon is applied. Leave blank to apply to all packages.' ) }
							</div>
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" className="w-4 h-4">
								<path fillRule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm11.378-3.917c-.89-.777-2.366-.777-3.255 0a.75.75 0 01-.988-1.129c1.454-1.272 3.776-1.272 5.23 0 1.513 1.324 1.513 3.518 0 4.842a3.75 3.75 0 01-.837.552c-.676.328-1.028.774-1.028 1.152v.75a.75.75 0 01-1.5 0v-.75c0-1.279 1.06-2.107 1.875-2.502.182-.088.351-.199.503-.331.83-.727.83-1.857 0-2.584zM12 18a.75.75 0 100-1.5.75.75 0 000 1.5z" clipRule="evenodd" />
							</svg>
						</span>
					</div>
					<div className="col-span-6 flex items-center gap-2">
						<label htmlFor="exclude-packages" className="min-w-[200px] text-sm font-medium text-gray-700">
							{ __( 'Exclude packages' ) }
						</label>
						<div className="mt-1 flex items-center justify-center">
							<Combobox
								value={ couponSlice.excludePackages.map( ( item: any ) => item.id ) }
								onChange={ ( value ) => {
									const newSelected = [ ...couponSlice.excludePackages, ...dataPackages.map( ( item: any ) => ( { id: item.id, name: item?.name || '' } ) ) ];

									const newSelectedItem = value.map( ( id ) => {
										const item = newSelected.find( ( item ) => item.id === id );

										return {
											id: item.id,
											name: item?.name || '',
										};
									} );

									dispatch( changeValue( { excludePackages: newSelectedItem } ) );
								} }
								multiple
								nullable
							>
								<div className="relative w-full border border-solid border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-700">
									{ couponSlice.excludePackages.length > 0 && (
										<ul className="m-0 mb-2 p-1.5 flex flex-wrap gap-1.5 text-gray-800 text-sm">
											{ couponSlice.excludePackages.map( ( item: any ) => (
												<li key={ item.id } className="inline-flex item-center gap-1 rounded bg-gray-200 py-1 px-2 m-0">
													<button
														className="flex items-center hover:text-red-600 outline-none border-none p-0 m-0 bg-transparent cursor-pointer no-underline"
														onClick={ () => {
															const newSelectedItem = couponSlice.excludePackages.filter( ( itemRemove : any ) => itemRemove.id !== item.id );
															dispatch( changeValue( { excludePackages: newSelectedItem } ) );
														} }
													>
														<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={ 1.5 } stroke="currentColor" className="w-4 h-4">
															<path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
														</svg>
													</button>
													{ item?.name || '' }
												</li>
											) ) }
										</ul>
									) }
									<div className="relative flex items-center">
										<span className="absolute flex items-center left-0 pl-3 pointer-events-none text-gray-500">
											<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={ 1.5 } stroke="currentColor" className="w-5 h-5">
												<path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
											</svg>
										</span>
										<Combobox.Input
											id="select-course"
											placeholder={ __( 'Search package…' ) }
											className="px-3 pl-9 py-2 text-gray-900 focus:border-none focus:outline-none outline-none block w-full shadow-sm text-sm border-gray-300 border-0 rounded-md"
											onChange={ ( e ) => searchPackagesFn( e.target.value ) }
										/>
									</div>
									{ dataPackages && dataPackages.length > 0 && (
										<Combobox.Options className="absolute z-10 w-full mt-1 bg-white shadow-lg rounded-md border border-gray-300 border-solid text-sm">
											{ dataPackages.map( ( option: any ) => (
												<Combobox.Option
													key={ option.id }
													value={ option.id }
													className={ ( { active } ) =>
														`relative cursor-default select-none py-2 pl-10 pr-4 ${ active ? 'bg-indigo-600 text-white' : 'text-gray-900'
														}`
													}
												>
													{ ( { selected, active } ) => (
														<>
															<span className={ `block truncate ${ selected ? 'font-medium' : 'font-normal' }` }>
																{ option?.name || '' }
															</span>

															{ selected ? (
																<span
																	className={ `absolute inset-y-0 left-0 flex items-center pl-3 ${ active ? 'text-white' : 'text-indigo-600' }` }>
																	<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={ 1.5 } stroke="currentColor" className="w-5 h-5">
																		<path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
																	</svg>

																</span>
															) : null }
														</>
													) }
												</Combobox.Option>
											) ) }
										</Combobox.Options>
									) }
								</div>
							</Combobox>
						</div>
						<span className="group relative cursor-pointer">
							<div className="min-w-[200px] text-center opacity-0 pointer-events-none group-hover:opacity-100 group-hover:pointer-events-auto transition duration-300 mb-2 absolute left-1/2 -translate-x-1/2 bottom-full z-20 bg-gray-800 text-white text-[12px] rounded px-2.5 py-1">
								{ __( 'Packages that the coupon will not apply to when added to the cart.' ) }
							</div>
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" className="w-4 h-4">
								<path fillRule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm11.378-3.917c-.89-.777-2.366-.777-3.255 0a.75.75 0 01-.988-1.129c1.454-1.272 3.776-1.272 5.23 0 1.513 1.324 1.513 3.518 0 4.842a3.75 3.75 0 01-.837.552c-.676.328-1.028.774-1.028 1.152v.75a.75.75 0 01-1.5 0v-.75c0-1.279 1.06-2.107 1.875-2.502.182-.088.351-.199.503-.331.83-.727.83-1.857 0-2.584zM12 18a.75.75 0 100-1.5.75.75 0 000 1.5z" clipRule="evenodd" />
							</svg>
						</span>
					</div>
					<div className="col-span-6 flex items-center gap-2">
						<label htmlFor="include-courses" className="min-w-[200px] text-sm font-medium text-gray-700">
							{ __( 'Include courses' ) }
						</label>
						<div className="mt-1 flex items-center justify-center">
							<Combobox value={ couponSlice.includeCourses.map( ( item: any ) => item.id ) } onChange={ onSelectCourse } multiple nullable>
								<div className="relative w-full border border-solid border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-700">
									{ couponSlice.includeCourses.length > 0 && (
										<ul className="m-0 mb-2 p-1.5 flex flex-wrap gap-1.5 text-gray-800 text-sm">
											{ couponSlice.includeCourses.map( ( item: any ) => (
												<li key={ item.id } className="inline-flex item-center gap-1 rounded bg-gray-200 py-1 px-2 m-0">
													<button
														className="flex items-center hover:text-red-600 outline-none border-none p-0 m-0 bg-transparent cursor-pointer no-underline"
														onClick={ () => onRemoveCourse( item.id ) }
													>
														<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={ 1.5 } stroke="currentColor" className="w-4 h-4">
															<path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
														</svg>
													</button>
													{ item?.name || '' }
												</li>
											) ) }
										</ul>
									) }
									<div className="relative flex items-center">
										<span className="absolute flex items-center left-0 pl-3 pointer-events-none text-gray-500">
											<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={ 1.5 } stroke="currentColor" className="w-5 h-5">
												<path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
											</svg>
										</span>
										<Combobox.Input
											id="select-course"
											placeholder={ __( 'Search course…' ) }
											className="px-3 pl-9 py-2 text-gray-900 focus:border-none focus:outline-none outline-none block w-full shadow-sm text-sm border-gray-300 border-0 rounded-md"
											onChange={ ( e ) => searchCoursesFn( e.target.value ) }
										/>
									</div>
									{ dataCourses && dataCourses.length > 0 && (
										<Combobox.Options className="absolute z-10 w-full mt-1 bg-white shadow-lg rounded-md border border-gray-300 border-solid text-sm">
											{ dataCourses.map( ( option: any ) => (
												<Combobox.Option
													key={ option.id }
													value={ option.id }
													className={ ( { active } ) =>
														`relative cursor-default select-none py-2 pl-10 pr-4 ${ active ? 'bg-indigo-600 text-white' : 'text-gray-900'
														}`
													}
												>
													{ ( { selected, active } ) => (
														<>
															<span className={ `block truncate ${ selected ? 'font-medium' : 'font-normal' }` }>
																{ option?.name || '' }
															</span>

															{ selected ? (
																<span
																	className={ `absolute inset-y-0 left-0 flex items-center pl-3 ${ active ? 'text-white' : 'text-indigo-600' }` }>
																	<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={ 1.5 } stroke="currentColor" className="w-5 h-5">
																		<path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
																	</svg>

																</span>
															) : null }
														</>
													) }
												</Combobox.Option>
											) ) }
										</Combobox.Options>
									) }
								</div>
							</Combobox>
						</div>
						<span className="group relative cursor-pointer">
							<div className="min-w-[200px] text-center opacity-0 pointer-events-none group-hover:opacity-100 group-hover:pointer-events-auto transition duration-300 mb-2 absolute left-1/2 -translate-x-1/2 bottom-full z-20 bg-gray-800 text-white text-[12px] rounded px-2.5 py-1">
								{ __( 'Courses will be applied to the cart when the coupon is applied. Leave blank to apply to all courses.' ) }
							</div>
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" className="w-4 h-4">
								<path fillRule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm11.378-3.917c-.89-.777-2.366-.777-3.255 0a.75.75 0 01-.988-1.129c1.454-1.272 3.776-1.272 5.23 0 1.513 1.324 1.513 3.518 0 4.842a3.75 3.75 0 01-.837.552c-.676.328-1.028.774-1.028 1.152v.75a.75.75 0 01-1.5 0v-.75c0-1.279 1.06-2.107 1.875-2.502.182-.088.351-.199.503-.331.83-.727.83-1.857 0-2.584zM12 18a.75.75 0 100-1.5.75.75 0 000 1.5z" clipRule="evenodd" />
							</svg>
						</span>
					</div>
					<div className="col-span-6 flex items-center gap-2">
						<label htmlFor="exclude-courses" className="min-w-[200px] text-sm font-medium text-gray-700">
							{ __( 'Exclude courses' ) }
						</label>
						<div className="mt-1 flex items-center justify-center">
							<Combobox
								value={ couponSlice.excludeCourses.map( ( item: any ) => item.id ) }
								onChange={ ( value ) => {
									const newSelected = [ ...couponSlice.excludeCourses, ...dataCourses.map( ( item: any ) => ( { id: item.id, name: item?.name || '' } ) ) ];

									const newSelectedItem = value.map( ( id ) => {
										const item = newSelected.find( ( item ) => item.id === id );

										return {
											id: item.id,
											name: item?.name || '',
										};
									} );

									dispatch( changeValue( { excludeCourses: newSelectedItem } ) );
								} }
								multiple
								nullable
							>
								<div className="relative w-full border border-solid border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-700">
									{ couponSlice.excludeCourses.length > 0 && (
										<ul className="m-0 mb-2 p-1.5 flex flex-wrap gap-1.5 text-gray-800 text-sm">
											{ couponSlice.excludeCourses.map( ( item: any ) => (
												<li key={ item.id } className="inline-flex item-center gap-1 rounded bg-gray-200 py-1 px-2 m-0">
													<button
														className="flex items-center hover:text-red-600 outline-none border-none p-0 m-0 bg-transparent cursor-pointer no-underline"
														onClick={ () => {
															const newSelectedItem = couponSlice.excludeCourses.filter( ( itemRemove : any ) => itemRemove.id !== item.id );
															dispatch( changeValue( { excludeCourses: newSelectedItem } ) );
														} }
													>
														<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={ 1.5 } stroke="currentColor" className="w-4 h-4">
															<path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
														</svg>
													</button>
													{ item?.name || '' }
												</li>
											) ) }
										</ul>
									) }
									<div className="relative flex items-center">
										<span className="absolute flex items-center left-0 pl-3 pointer-events-none text-gray-500">
											<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={ 1.5 } stroke="currentColor" className="w-5 h-5">
												<path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
											</svg>
										</span>
										<Combobox.Input
											id="select-course"
											placeholder={ __( 'Search course…' ) }
											className="px-3 pl-9 py-2 text-gray-900 focus:border-none focus:outline-none outline-none block w-full shadow-sm text-sm border-gray-300 border-0 rounded-md"
											onChange={ ( e ) => searchCoursesFn( e.target.value ) }
										/>
									</div>
									{ dataCourses && dataCourses.length > 0 && (
										<Combobox.Options className="absolute z-10 w-full mt-1 bg-white shadow-lg rounded-md border border-gray-300 border-solid text-sm">
											{ dataCourses.map( ( option: any ) => (
												<Combobox.Option
													key={ option.id }
													value={ option.id }
													className={ ( { active } ) =>
														`relative cursor-default select-none py-2 pl-10 pr-4 ${ active ? 'bg-indigo-600 text-white' : 'text-gray-900'
														}`
													}
												>
													{ ( { selected, active } ) => (
														<>
															<span className={ `block truncate ${ selected ? 'font-medium' : 'font-normal' }` }>
																{ option?.name || '' }
															</span>

															{ selected ? (
																<span
																	className={ `absolute inset-y-0 left-0 flex items-center pl-3 ${ active ? 'text-white' : 'text-indigo-600' }` }>
																	<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={ 1.5 } stroke="currentColor" className="w-5 h-5">
																		<path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
																	</svg>

																</span>
															) : null }
														</>
													) }
												</Combobox.Option>
											) ) }
										</Combobox.Options>
									) }
								</div>
							</Combobox>
						</div>
						<span className="group relative cursor-pointer">
							<div className="min-w-[200px] text-center opacity-0 pointer-events-none group-hover:opacity-100 group-hover:pointer-events-auto transition duration-300 mb-2 absolute left-1/2 -translate-x-1/2 bottom-full z-20 bg-gray-800 text-white text-[12px] rounded px-2.5 py-1">
								{ __( 'Courses that the coupon will not apply to when added to the cart.' ) }
							</div>
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" className="w-4 h-4">
								<path fillRule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm11.378-3.917c-.89-.777-2.366-.777-3.255 0a.75.75 0 01-.988-1.129c1.454-1.272 3.776-1.272 5.23 0 1.513 1.324 1.513 3.518 0 4.842a3.75 3.75 0 01-.837.552c-.676.328-1.028.774-1.028 1.152v.75a.75.75 0 01-1.5 0v-.75c0-1.279 1.06-2.107 1.875-2.502.182-.088.351-.199.503-.331.83-.727.83-1.857 0-2.584zM12 18a.75.75 0 100-1.5.75.75 0 000 1.5z" clipRule="evenodd" />
							</svg>
						</span>
					</div>
					<div className="col-span-6 flex items-center gap-2">
						<label htmlFor="include-course-categories" className="min-w-[200px] text-sm font-medium text-gray-700">
							{ __( 'Include course categories' ) }
						</label>
						<div className="mt-1 flex items-center justify-center">
							<Combobox
								value={ couponSlice.includeCourseCategories.map( ( item: any ) => item.id ) }
								onChange={ ( value ) => {
									const newSelected = [ ...couponSlice.includeCourseCategories, ...dataCourseCategories.map( ( item: any ) => ( { id: item.id, name: item?.name || '' } ) ) ];

									const newSelectedItem = value.map( ( id ) => {
										const item = newSelected.find( ( item ) => item.id === id );

										return {
											id: item.id,
											name: item?.name || '',
										};
									} );

									dispatch( changeValue( { includeCourseCategories: newSelectedItem } ) );
								} }
								multiple
								nullable
							>
								<div className="relative w-full border border-solid border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-700">
									{ couponSlice.includeCourseCategories.length > 0 && (
										<ul className="m-0 mb-2 p-1.5 flex flex-wrap gap-1.5 text-gray-800 text-sm">
											{ couponSlice.includeCourseCategories.map( ( item: any ) => (
												<li key={ item.id } className="inline-flex item-center gap-1 rounded bg-gray-200 py-1 px-2 m-0">
													<button
														className="flex items-center hover:text-red-600 outline-none border-none p-0 m-0 bg-transparent cursor-pointer no-underline"
														onClick={ () => {
															const newSelectedItem = couponSlice.includeCourseCategories.filter( ( itemRemove : any ) => itemRemove.id !== item.id );
															dispatch( changeValue( { includeCourseCategories: newSelectedItem } ) );
														} }
													>
														<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={ 1.5 } stroke="currentColor" className="w-4 h-4">
															<path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
														</svg>
													</button>
													{ item?.name || '' }
												</li>
											) ) }
										</ul>
									) }
									<div className="relative flex items-center">
										<span className="absolute flex items-center left-0 pl-3 pointer-events-none text-gray-500">
											<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={ 1.5 } stroke="currentColor" className="w-5 h-5">
												<path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
											</svg>
										</span>
										<Combobox.Input
											id="select-course"
											placeholder={ __( 'Search course category…' ) }
											className="px-3 pl-9 py-2 text-gray-900 focus:border-none focus:outline-none outline-none block w-full shadow-sm text-sm border-gray-300 border-0 rounded-md"
											onChange={ ( e ) => searchCourseCategoriesFn( e.target.value ) }
										/>
									</div>
									{ dataCourseCategories && dataCourseCategories.length > 0 && (
										<Combobox.Options className="absolute z-10 w-full mt-1 bg-white shadow-lg rounded-md border border-gray-300 border-solid text-sm">
											{ dataCourseCategories.map( ( option: any ) => (
												<Combobox.Option
													key={ option.id }
													value={ option.id }
													className={ ( { active } ) =>
														`relative cursor-default select-none py-2 pl-10 pr-4 ${ active ? 'bg-indigo-600 text-white' : 'text-gray-900'
														}`
													}
												>
													{ ( { selected, active } ) => (
														<>
															<span className={ `block truncate ${ selected ? 'font-medium' : 'font-normal' }` }>
																{ option?.name || '' }
															</span>

															{ selected ? (
																<span
																	className={ `absolute inset-y-0 left-0 flex items-center pl-3 ${ active ? 'text-white' : 'text-indigo-600' }` }>
																	<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={ 1.5 } stroke="currentColor" className="w-5 h-5">
																		<path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
																	</svg>

																</span>
															) : null }
														</>
													) }
												</Combobox.Option>
											) ) }
										</Combobox.Options>
									) }
								</div>
							</Combobox>
						</div>
						<span className="group relative cursor-pointer">
							<div className="min-w-[200px] text-center opacity-0 pointer-events-none group-hover:opacity-100 group-hover:pointer-events-auto transition duration-300 mb-2 absolute left-1/2 -translate-x-1/2 bottom-full z-20 bg-gray-800 text-white text-[12px] rounded px-2.5 py-1">
								{ __( 'Course categories will be applied to the cart when the coupon is applied. Leave blank to apply to all course categories.' ) }
							</div>
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" className="w-4 h-4">
								<path fillRule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm11.378-3.917c-.89-.777-2.366-.777-3.255 0a.75.75 0 01-.988-1.129c1.454-1.272 3.776-1.272 5.23 0 1.513 1.324 1.513 3.518 0 4.842a3.75 3.75 0 01-.837.552c-.676.328-1.028.774-1.028 1.152v.75a.75.75 0 01-1.5 0v-.75c0-1.279 1.06-2.107 1.875-2.502.182-.088.351-.199.503-.331.83-.727.83-1.857 0-2.584zM12 18a.75.75 0 100-1.5.75.75 0 000 1.5z" clipRule="evenodd" />
							</svg>
						</span>
					</div>
					<div className="col-span-6 flex items-center gap-2">
						<label htmlFor="exclude-course-categories" className="min-w-[200px] text-sm font-medium text-gray-700">
							{ __( 'Exclude course categories' ) }
						</label>
						<div className="mt-1 flex items-center justify-center">
							<Combobox
								value={ couponSlice.excludeCourseCategories.map( ( item: any ) => item.id ) }
								onChange={ ( value ) => {
									const newSelected = [ ...couponSlice.excludeCourseCategories, ...dataCourseCategories.map( ( item: any ) => ( { id: item.id, name: item?.name || '' } ) ) ];

									const newSelectedItem = value.map( ( id ) => {
										const item = newSelected.find( ( item ) => item.id === id );

										return {
											id: item.id,
											name: item?.name || '',
										};
									} );

									dispatch( changeValue( { excludeCourseCategories: newSelectedItem } ) );
								} }
								multiple
								nullable
							>
								<div className="relative w-full border border-solid border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-700">
									{ couponSlice.excludeCourseCategories.length > 0 && (
										<ul className="m-0 mb-2 p-1.5 flex flex-wrap gap-1.5 text-gray-800 text-sm">
											{ couponSlice.excludeCourseCategories.map( ( item: any ) => (
												<li key={ item.id } className="inline-flex item-center gap-1 rounded bg-gray-200 py-1 px-2 m-0">
													<button
														className="flex items-center hover:text-red-600 outline-none border-none p-0 m-0 bg-transparent cursor-pointer no-underline"
														onClick={ () => {
															const newSelectedItem = couponSlice.excludeCourseCategories.filter( ( itemRemove : any ) => itemRemove.id !== item.id );
															dispatch( changeValue( { excludeCourseCategories: newSelectedItem } ) );
														} }
													>
														<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={ 1.5 } stroke="currentColor" className="w-4 h-4">
															<path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
														</svg>
													</button>
													{ item?.name || '' }
												</li>
											) ) }
										</ul>
									) }
									<div className="relative flex items-center">
										<span className="absolute flex items-center left-0 pl-3 pointer-events-none text-gray-500">
											<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={ 1.5 } stroke="currentColor" className="w-5 h-5">
												<path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
											</svg>
										</span>
										<Combobox.Input
											id="select-course"
											placeholder={ __( 'Search course category…' ) }
											className="px-3 pl-9 py-2 text-gray-900 focus:border-none focus:outline-none outline-none block w-full shadow-sm text-sm border-gray-300 border-0 rounded-md"
											onChange={ ( e ) => searchCourseCategoriesFn( e.target.value ) }
										/>
									</div>
									{ dataCourseCategories && dataCourseCategories.length > 0 && (
										<Combobox.Options className="absolute z-10 w-full mt-1 bg-white shadow-lg rounded-md border border-gray-300 border-solid text-sm">
											{ dataCourseCategories.map( ( option: any ) => (
												<Combobox.Option
													key={ option.id }
													value={ option.id }
													className={ ( { active } ) =>
														`relative cursor-default select-none py-2 pl-10 pr-4 ${ active ? 'bg-indigo-600 text-white' : 'text-gray-900'
														}`
													}
												>
													{ ( { selected, active } ) => (
														<>
															<span className={ `block truncate ${ selected ? 'font-medium' : 'font-normal' }` }>
																{ option?.name || '' }
															</span>

															{ selected ? (
																<span
																	className={ `absolute inset-y-0 left-0 flex items-center pl-3 ${ active ? 'text-white' : 'text-indigo-600' }` }>
																	<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={ 1.5 } stroke="currentColor" className="w-5 h-5">
																		<path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
																	</svg>

																</span>
															) : null }
														</>
													) }
												</Combobox.Option>
											) ) }
										</Combobox.Options>
									) }
								</div>
							</Combobox>
						</div>
						<span className="group relative cursor-pointer">
							<div className="min-w-[200px] text-center opacity-0 pointer-events-none group-hover:opacity-100 group-hover:pointer-events-auto transition duration-300 mb-2 absolute left-1/2 -translate-x-1/2 bottom-full z-20 bg-gray-800 text-white text-[12px] rounded px-2.5 py-1">
								{ __( 'Course categories that the coupon will not apply to when added to the cart.' ) }
							</div>
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" className="w-4 h-4">
								<path fillRule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm11.378-3.917c-.89-.777-2.366-.777-3.255 0a.75.75 0 01-.988-1.129c1.454-1.272 3.776-1.272 5.23 0 1.513 1.324 1.513 3.518 0 4.842a3.75 3.75 0 01-.837.552c-.676.328-1.028.774-1.028 1.152v.75a.75.75 0 01-1.5 0v-.75c0-1.279 1.06-2.107 1.875-2.502.182-.088.351-.199.503-.331.83-.727.83-1.857 0-2.584zM12 18a.75.75 0 100-1.5.75.75 0 000 1.5z" clipRule="evenodd" />
							</svg>
						</span>
					</div>
					<div className="col-span-6 flex items-center gap-2">
						<label htmlFor="allow-emails" className="block min-w-[200px] text-sm font-medium text-gray-700">
							{ __( 'Allowed emails' ) }
						</label>
						<input
							id="allow-emails"
							placeholder="No restriction"
							value={ couponSlice.allowEmails }
							onChange={ ( e ) => dispatch( changeValue( { allowEmails: e.target.value } ) ) }
							type="email"
							className="min-w-[200px] px-3 py-2 mt-1 text-gray-900 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm sm:text-sm border-gray-300 rounded-md"
						/>
						<span className="group relative cursor-pointer">
							<div className="min-w-[200px] text-center opacity-0 pointer-events-none group-hover:opacity-100 group-hover:pointer-events-auto transition duration-300 mb-2 absolute left-1/2 -translate-x-1/2 bottom-full z-20 bg-gray-800 text-white text-[12px] rounded px-2.5 py-1">
								{ __( 'List of allowed billing emails to check against when an order is placed. Separate email addresses with commas. You can also use an asterisk (*) to match parts of an email. For example "*@gmail.com" would match all gmail addresses.' ) }
							</div>
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" className="w-4 h-4">
								<path fillRule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm11.378-3.917c-.89-.777-2.366-.777-3.255 0a.75.75 0 01-.988-1.129c1.454-1.272 3.776-1.272 5.23 0 1.513 1.324 1.513 3.518 0 4.842a3.75 3.75 0 01-.837.552c-.676.328-1.028.774-1.028 1.152v.75a.75.75 0 01-1.5 0v-.75c0-1.279 1.06-2.107 1.875-2.502.182-.088.351-.199.503-.331.83-.727.83-1.857 0-2.584zM12 18a.75.75 0 100-1.5.75.75 0 000 1.5z" clipRule="evenodd" />
							</svg>
						</span>
					</div>
				</div>
			</div>
		</div>
	);
}
