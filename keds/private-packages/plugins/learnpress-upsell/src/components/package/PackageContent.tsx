import DateTime from '@/fields/DateTime';
import { changeValue, packageSelection } from '@/store/package';
import { sprintf, __ } from '@/utils/i18n';
import { Combobox } from '@headlessui/react';
import apiFetch from '@wordpress/api-fetch';
import { useCallback, useState } from '@wordpress/element';
import { MediaUpload } from '@wordpress/media-utils';
import { addQueryArgs } from '@wordpress/url';
import { format } from 'date-fns';
import { debounce } from 'lodash';
import { useForm } from 'react-hook-form';
import { useDispatch, useSelector } from 'react-redux';
import useSWR, { useSWRConfig } from 'swr';
import Certificate from './Certificate';
import ClassicEdit from '@/utils/ClassicEditor';

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

const PRICE_DISCOUNT_TYPE = [
	{
		id: 'percent',
		title: __( 'Percentage discount' ),
	},
	{
		id: 'fixed',
		title: __( 'Fixed discount' ),
	},
];

export default function PackageContent() {
	const dispatch = useDispatch();
	const packageSlice = useSelector( packageSelection );

	const { mutate } = useSWRConfig();

	const [ addTag, setAddTag ] = useState( false );
	const [ searchCourse, setSearchCourse ] = useState( '' );
	const [ loadingTag, setloadingTag ] = useState( false );

	const { data: dataCourses, error: errorCourses } = useSWR( addQueryArgs( '/learnpress/v1/courses', { search: searchCourse || '' } ), ( url: string ) => apiFetch( { path: url } ) );
	const { data: dataTags, error: errorTags } = useSWR( '/wp/v2/learnpress_package_tag', ( url ) => apiFetch( { path: url } ) );

	const loadingCourses = ! dataCourses && ! errorCourses;

	const { register: registerTag, handleSubmit: handleSubmitTag, formState: { errors: errorTagsForm }, setValue: setTagValue, setError: setErrorTag } = useForm();

	const searchCoursesFn = useCallback( debounce( ( searchInput: string ) => setSearchCourse( searchInput ), 600 ), [] );

	const onSelectCourse = ( value ) => {
		const newSelected = [ ...packageSlice.courses, ...dataCourses.map( ( item ) => ( { id: item.id, name: item?.name || '', price: item.price || 0 } ) ) ];

		const newSelectedItem = value.map( ( id ) => {
			const item = newSelected.find( ( item ) => item.id === id );

			return {
				id: item.id,
				name: item?.name || '',
				price: item.price || 0,
			};
		} );

		dispatch( changeValue( { courses: newSelectedItem } ) );
	};

	const onRemoveCourse = ( id ) => {
		const newSelectedItem = packageSlice.courses.filter( ( item ) => item.id !== id );
		dispatch( changeValue( { courses: newSelectedItem } ) );
	};

	async function saveAddTag( { add_tag } ) {
		setloadingTag( true );

		try {
			const response = await apiFetch( {
				path: '/wp/v2/learnpress_package_tag',
				method: 'POST',
				data: {
					name: add_tag,
				},
			} );

			if ( response?.id ) {
				mutate( '/wp/v2/learnpress_package_tag' );

				dispatch( changeValue( { tags: [ ...packageSlice.tags, response?.id ] } ) );
			}

			if ( response?.code === 'term_exists' ) {
				setErrorTag( 'add_tag', {
					type: 'manual',
					message: __( 'Tag already exists' ),
				} );
			}

			setTagValue( 'add_tag', '' );
		} catch ( error ) {
			setErrorTag( 'add_tag', {
				type: 'manual',
				message: __( 'Invalid tag name' ),
			} );
		}

		setloadingTag( false );
	}

	return (
		<div className="grid grid-cols-12 gap-6 mb-6 flex-1">
			<div className="col-span-12">
				<label htmlFor="title" className="block text-sm font-medium text-gray-700">
					{ __( 'Title' ) }
				</label>
				<input
					id="title"
					value={ packageSlice.title }
					onChange={ ( e ) => dispatch( changeValue( { title: e.target.value } ) ) }
					type="text"
					className="px-3 py-2 mt-1 text-gray-900 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
				/>
			</div>

			<div className="col-span-12">
				<div className="flex items-end justify-between">
					<label htmlFor="description" className="block text-sm font-medium text-gray-700">
						{ __( 'Description' ) }
					</label>
				</div>
				{ packageSlice.description !== null && (
					<ClassicEdit content={ packageSlice.description || '' } setContent={ ( value: string ) => dispatch( changeValue( { description: value } ) ) } />
				) }
			</div>

			<div className="col-span-9">
				<div className="grid grid-cols-8 gap-6">
					<div className="col-span-3">
						<label htmlFor="status" className="block text-sm font-medium text-gray-700">
							{ __( 'Status' ) }
						</label>
						<select
							id="status"
							className="mt-1 py-1.5 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm text-gray-800 text-sm border-gray-300 rounded-md"
							value={ packageSlice.status }
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
							{ packageSlice.publishDate ? (
								<div className="flex items-center gap-x-2">
									<DateTime
										id="publishDate"
										value={ packageSlice.publishDate }
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

					<div className="col-span-8">
						<div className="block text-sm font-medium text-gray-700">
							{ __( 'Courses' ) }
							<span className="text-base text-red-500 ml-1">*</span>
						</div>
						<div className="mt-1 flex items-center justify-center">
							<Combobox value={ packageSlice.courses.map( ( item ) => item.id ) } onChange={ onSelectCourse } multiple nullable>
								<div className="relative w-full">
									{ packageSlice.courses.length > 0 && (
										<ul className="m-0 mb-2 p-1.5 flex flex-wrap gap-1.5 focus:ring-indigo-500 focus:border-indigo-700 shadow-sm text-gray-800 text-sm border border-solid border-gray-300 rounded-md">
											{ packageSlice.courses.map( ( item ) => (
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
											className="px-3 pl-9 py-2 text-gray-900 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm text-sm border-gray-300 rounded-md"
											onChange={ ( e ) => searchCoursesFn( e.target.value ) }
										/>
										{ loadingCourses && (
											<span className="flex items-center absolute right-2 pointer-events-none">
												<svg className="animate-spin h-5 w-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
													<circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
													<path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
												</svg>
											</span>
										) }
									</div>
									{ dataCourses && dataCourses.length > 0 && (
										<Combobox.Options className="absolute z-10 w-full mt-1 bg-white shadow-lg rounded-md border border-gray-300 border-solid text-sm">
											{ dataCourses.map( ( option ) => (
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
																{ option?.name ? (
																	<>
																		{ option.name }
																		{ option?.price_rendered && (
																			<>
																				{ ' - ' }
																				<span className="font-semibold">
																					{ option?.price_rendered }
																				</span>
																			</>
																		) }
																	</>
																) : '' }
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
					</div>

					<div className="col-span-8">
						<div className="block text-sm font-medium text-gray-700">
							{ __( 'Price' ) }
						</div>
						<p className="text-sm text-gray-500 m-0 mt-1">
							{ sprintf( __( 'Total price of the course selected (%s): ' ), LP_UPSELL_LOCALIZE.symbol ) }
							<span className="font-medium text-gray-800">{ packageSlice.price }</span>
						</p>

						<div className="mt-4 flex flex-col gap-3">
							<div className="flex items-center">
								<input
									checked={ packageSlice.newPriceEnabled }
									id="new-price-checkbox"
									type="checkbox"
									className="focus:ring-indigo-500 focus:ring-offset-2 focus:ring-2 h-4 w-4 text-indigo-600 border-gray-300 rounded"
									onChange={ () => dispatch( changeValue( { newPriceEnabled: ! packageSlice.newPriceEnabled } ) ) }
								/>
								<label htmlFor="new-price-checkbox" className="pl-2 text-sm font-medium text-gray-700 select-none leading-4">
									{ __( 'Set a new price' ) }
								</label>
							</div>

							{ packageSlice.newPriceEnabled && (
								<>
									<div className="flex items-center gap-2">
										<label htmlFor="price-type" className="min-w-[100px] text-sm font-medium text-gray-700">
											{ __( 'Type' ) }
										</label>
										<select
											id="price-type"
											className="mt-1 py-1.5 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm text-gray-800 text-sm border-gray-300 rounded-md"
											value={ packageSlice.newPriceType }
											onChange={ ( e ) => dispatch( changeValue( { newPriceType: e.target.value } ) ) }
											onBlur={ ( e ) => dispatch( changeValue( { newPriceType: e.target.value } ) ) }
										>
											{ PRICE_DISCOUNT_TYPE.map( ( option ) => (
												<option
													key={ option.id }
													value={ option.id }
												>
													{ option.title }
												</option>
											) ) }
										</select>
									</div>
									{ packageSlice.newPriceType === 'percent' && (
										<>
											<div className="flex items-center gap-2">
												<label htmlFor="price-amount" className="min-w-[100px] text-sm font-medium text-gray-700">
													{ __( 'Amount' ) }
												</label>
												<input
													id="price-amount"
													type="number"
													min={ 0 }
													max={ 100 }
													className="mt-1 py-1.5 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm text-gray-800 text-sm border-gray-300 rounded-md"
													value={ packageSlice.newPriceAmount }
													onChange={ ( e ) => {
														const value = e.target.value;

														// validate with regex with only number and 0<value<100.
														if ( value.match( /^([1-9][0-9]?|100)$/ ) ) {
															dispatch( changeValue( { newPriceAmount: value } ) );
														} else {
															dispatch( changeValue( { newPriceAmount: '' } ) );
														}
													} }
												/>
											</div>
											<div className="flex items-center gap-2">
												<label htmlFor="price-total" className="min-w-[100px] text-sm font-medium text-gray-700">
													{ `${ __( 'Total' ) } (${ LP_UPSELL_LOCALIZE.symbol })` }
												</label>
												<div className="mt-1 text-sm text-gray-800 font-medium">
													{ packageSlice.salePrice }
												</div>
											</div>
										</>
									) }

									{ packageSlice.newPriceType === 'fixed' && (
										<div className="flex items-center gap-2">
											<label htmlFor="price-amount" className="min-w-[100px] text-sm font-medium text-gray-700">
												{ `${ __( 'Fixed price' ) } (${ LP_UPSELL_LOCALIZE.symbol })` }
											</label>
											<input
												id="price-amount"
												type="number"
												className="mt-1 py-1.5 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm text-gray-800 text-sm border-gray-300 rounded-md"
												value={ packageSlice.salePrice }
												step="0.01"
												min={ 0 }
												onChange={ ( e ) => dispatch( changeValue( { salePrice: parseFloat( e.target.value ) } ) ) }
											/>
										</div>
									) }
								</>
							) }
						</div>
					</div>
					{ /*{ window.LP_Certificate && (
						<div className="col-span-8">
							<div className="block mb-1 text-sm font-medium text-gray-700">
								{ __( 'Certificate' ) }
							</div>
							<div className="mt-1 flex items-center">
								<Certificate />
							</div>
						</div>
					) }*/ }
				</div>
			</div>

			<div className="col-span-3 space-y-6">
				<div>
					<label htmlFor="featuredImage" className="block text-sm font-medium text-gray-700">
						{ __( 'Featured image' ) }
					</label>
					<div className="mt-2">
						{ packageSlice.featuredImage.url ? (
							<>
								<div className="flex items-center mb-3 aspect-w-1 aspect-h-1">
									<div>
										<img
											src={ packageSlice.featuredImage.url }
											alt=""
											className="w-64 h-64 rounded-md object-cover object-center"
										/>
									</div>
								</div>
								<div className="flex gap-x-2">
									<MediaUpload
										onSelect={ ( media ) => {
											dispatch( changeValue( {
												featuredImage: {
													id: media.id,
													url: media.url,
												},
											} ) );
										} }
										value={ '' }
										allowedTypes={ [ 'image' ] }
										render={ ( { open } ) => (
											<button
												onClick={ open }
												className="inline-flex items-center justify-center rounded h-[35px] py-0 px-[15px] text-gray-600 text-[13px] font-medium outline-none cursor-pointer decoration-inherit text-center bg-white border border-gray-200 border-solid shadow-sm hover:border-gray-300"
											>
												{ __( 'Replace' ) }
											</button>
										) }
									/>

									<button
										onClick={ () => dispatch(
											changeValue( {
												featuredImage: {
													id: '',
													url: '',
												},
											} )
										) }
										className="inline-flex items-center justify-center rounded h-[35px] py-0 px-[15px] text-red-600 text-[13px] font-medium outline-none cursor-pointer decoration-inherit text-center bg-white border border-gray-200 border-solid shadow-sm hover:border-gray-300"
									>
										{ __( 'Remove' ) }
									</button>
								</div>
							</>
						) : (
							<MediaUpload
								onSelect={ ( media ) => {
									dispatch( changeValue( {
										featuredImage: {
											id: media.id,
											url: media.url,
										},
									} ) );
								} }
								value={ '' }
								allowedTypes={ [ 'image' ] }
								render={ ( { open } ) => (
									<button
										onClick={ open }
										className="inline-flex items-center justify-center rounded h-[35px] py-0 px-[15px] text-gray-600 text-[13px] font-medium outline-none cursor-pointer decoration-inherit text-center bg-white border border-gray-300 border-solid shadow-sm hover:border-gray-200"
									>
										{ __( 'Upload' ) }
									</button>
								) }
							/>
						) }
					</div>
				</div>
				<div>
					<div className="flex justify-between">
						<label htmlFor="tags" className="block text-sm font-medium text-gray-700">
							{ __( 'Tags' ) }
						</label>
						<a
							className="ml-auto inline-flex items-center gap-x-1 text-[13px] no-underline text-indigo-600 font-medium focus:outline-none"
							href={ LP_UPSELL_LOCALIZE.admin_url + 'edit-tags.php?taxonomy=learnpress_package_tag' }
							target="_blank"
							rel="noopener noreferrer"
						>
							{ __( 'Manage tags' ) }
						</a>
					</div>
					<div className="mt-2 -mx-2">
						<div className="overflow-y-auto max-h-36 px-2">
							<div className="space-y-2">
								{ dataTags?.length > 0 && dataTags.map( ( tag ) => (
									<div className="flex items-start" key={ `package_tag_${ tag.id }` }>
										<div className="flex items-center h-5">
											<input
												id={ `package_tag_${ tag.id }` }
												type="checkbox"
												onChange={ ( e ) => dispatch( changeValue( { tags: e.target.checked ? [ ...packageSlice.tags, tag.id ] : packageSlice.tags.filter( ( item ) => item !== tag.id ) } ) ) }
												checked={ packageSlice.tags.includes( tag.id ) }
												className="focus:ring-indigo-500 focus:ring-offset-2 focus:ring-2 h-4 w-4 text-indigo-600 border-gray-300 rounded"
											/>
										</div>
										<div className="text-sm">
											<label htmlFor={ `package_tag_${ tag.id }` } className="pl-2 font-medium text-gray-700" dangerouslySetInnerHTML={ { __html: tag.name } } />
										</div>
									</div>
								) ) }
							</div>
							<div className="sticky bottom-0 h-10 bg-gradient-to-t from-white"></div>
						</div>
						<button
							onClick={ () => setAddTag( ! addTag ) }
							className="p-0 mt-3 inline-flex items-center justify-center rounded text-indigo-600 text-[13px] font-medium outline-none cursor-pointer decoration-inherit text-center bg-transparent border-none shadow-none"
						>
							{ ! addTag ? (
								<>
									<svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={ 2 }>
										<path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
									</svg>
									{ __( 'Add new' ) }
								</>
							) : (
								<>
									<svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={ 2 }>
										<path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
									</svg>
									{ __( 'Cancel' ) }
								</>
							) }
						</button>
						{ addTag && (
							<>
								<form onSubmit={ handleSubmitTag( saveAddTag ) } className="mt-2 flex items-center gap-x-1">
									<input
										id="add_tag"
										type="text"
										className="px-2 py-1 text-gray-900 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm text-sm border-gray-300 rounded"
										{ ...registerTag( 'add_tag', { required: true } ) }
									/>
									<button
										disabled={ loadingTag }
										type="submit"
										className="relative inline-flex items-center justify-center rounded px-3 py-1 text-white text-sm font-medium outline-none cursor-pointer decoration-inherit text-center bg-indigo-600 border border-indigo-600 border-solid shadow-sm hover:border-indigo-500"
									>
										{ loadingTag && (
											<span className="absolute inset-0 bg-indigo-600 rounded-md">
												<span className="flex items-center justify-center absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
													<svg className="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
														<circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
														<path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
													</svg>
												</span>
											</span>
										) }
										{ __( 'Add' ) }
									</button>
								</form>

								{ errorTagsForm.add_tag && (
									<p className={ `text-[13px] text-red-600 flex gap-1 items-center p-0 mt-1 mb-0` }>
										<svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
											<path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
										</svg>
										{ errorTagsForm.add_tag.type === 'required' ? __( 'This field is required' ) : errorTagsForm.add_tag.message }
									</p>
								) }
							</>
						) }
					</div>
				</div>
			</div>
		</div>
	);
}
