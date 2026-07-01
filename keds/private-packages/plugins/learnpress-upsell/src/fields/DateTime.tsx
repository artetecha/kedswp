import { useMemo, useState } from '@wordpress/element';
import {
	DateObj,
	useDayzed,
	RenderProps,
} from 'dayzed';
import * as dateFns from 'date-fns';
import { Popover } from '@headlessui/react';

interface DateTimePops {
	id: string;
	value: string;
	onChange?: ( nextValue: string ) => void;
	minDate?: string;
	maxDate?: string;
	dateFormat?: string;
	showTime?: boolean;
	enableClear?: boolean;
}

export interface SingleDatepickerProps {
	value?: string;
	disabled?: boolean;
	onChange: ( date?: string ) => void;
	minDate?: string;
	maxDate?: string;
	dateFormat?: string;
	enableClear?: boolean;
}
// https://codesandbox.io/s/rs450?file=/src/index.tsx:6745-6760
const DATE_FORMAT = 'yyyy-MM-dd';

const MONTH_NAMES = [
	'Jan',
	'Feb',
	'Mar',
	'Apr',
	'May',
	'Jun',
	'Jul',
	'Aug',
	'Sep',
	'Oct',
	'Nov',
	'Dec',
];
const DAY_NAMES = [ 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' ];

export default function DateTime( pops: DateTimePops ) {
	const split = pops.value ? pops.value.split( ' ' ) : [];
	const splitTime = split[ 1 ] ? split[ 1 ].split( ':' ) : [];

	const [ day, setDay ] = useState( split[ 0 ] || '' );
	const [ hours, setHours ] = useState( parseInt( splitTime[ 0 ] ) || '0' );
	const [ mins, setMins ] = useState( parseInt( splitTime[ 1 ] ) || '0' );

	const onChangeValue = ( { day, hours, mins } ) => {
		setDay( day );
		setHours( hours );
		setMins( mins );

		if ( parseInt( hours ) < 10 ) {
			hours = `0${ parseInt( hours ) }`;
		}

		if ( parseInt( mins ) < 10 ) {
			mins = `0${ parseInt( mins ) }`;
		}

		const newValue = `${ day } ${ hours }:${ mins }`;
		pops.onChange && pops.onChange( newValue );
	};

	return useMemo( () => (
		<div>

			<div className="flex gap-2 items-center">
				<SingleDatePicker
					value={ pops.value && pops.showTime ? day : pops.value }
					minDate={ pops.minDate }
					maxDate={ pops.maxDate }
					dateFormat={ pops.dateFormat || DATE_FORMAT }
					onChange={ ( value ) => {
						if ( pops.showTime ) {
							if ( value ) {
								onChangeValue( { day: value, hours, mins } );
							} else {
								pops.onChange( value );
							}
						} else {
							pops.onChange( value );
						}
					} }
					enableClear={ pops.enableClear }
				/>
				{ pops.showTime && <TimePicker value={ { hours, mins } } onChange={ ( value ) => onChangeValue( { day, hours: value.hours, mins: value.mins } ) } /> }
			</div>
		</div>
	), [ pops.value, pops.minDate, pops.maxDate ] );
}

function TimePicker( { value, onChange } ) {
	const { hours, mins } = value;

	return (
		<Popover className="relative">
			<Popover.Button className="bg-transparent p-0 m-0 border-none shadow-none outline-none">
				<input
					className="px-3 py-1.5 w-[100px] text-gray-900 focus:ring-indigo-500 focus:border-indigo-500 block shadow-sm sm:text-sm border-gray-300 rounded-md"
					type="text"
					value={ `${ hours < 10 ? `0${ hours }` : hours }:${ mins < 10 ? `0${ mins }` : mins }` }
					onChange={ ( e ) => onChange( e.target.value ) }
				/>
			</Popover.Button>
			<Popover.Panel className="absolute z-40 my-3 left-0 px-0">
				<div className="text-center overflow-hidden px-[15px] pb-[15px] mb-5 rounded-md bg-white ring-1 ring-black ring-opacity-5">
					<div className="flex items-center gap-x-2">
						<div>
							<div className="text-sm text-gray-800 mb-1 pt-2">Hour</div>
							<div className="flex flex-col h-[200px] overflow-y-auto border border-solid border-gray-200 rounded">
								{ Array.from( { length: 24 }, ( _, i ) => i ).map( ( i ) => (
									<button
										key={ i }
										onClick={ () => onChange( { hours: i, mins } ) }
										className={ `text-sm w-14 py-2 flex items-center justify-center hover:bg-slate-100 px-0 m-0 border-none shadow-none outline-none cursor-pointer ${ hours == i ? 'text-indigo-600 font-semibold bg-slate-100' : 'text-gray-800  bg-transparent' }` }
									>
										{ i < 10 ? `0${ i }` : i }
									</button>
								) ) }
							</div>
						</div>
						<div>
							<div className="text-sm text-gray-800 mb-1 pt-2">Minute</div>
							<div className="flex flex-col h-[200px] overflow-y-auto border border-solid border-gray-200 rounded">
								{ Array.from( { length: 60 }, ( _, i ) => i ).map( ( i ) => (
									<button
										key={ i }
										onClick={ () => onChange( { hours, mins: i } ) }
										className={ `text-sm w-14 py-2 flex items-center justify-center hover:bg-slate-100 px-0 m-0 border-none shadow-none outline-none cursor-pointer ${ mins == i ? 'text-indigo-600 font-semibold bg-slate-100' : 'text-gray-800  bg-transparent' }` }
									>
										{ i < 10 ? `0${ i }` : i }
									</button>
								) ) }
							</div>
						</div>
					</div>
				</div>
			</Popover.Panel>
		</Popover>
	);
}

function SingleDatePicker( props: SingleDatepickerProps ) {
	const { value, disabled, onChange, minDate, maxDate, dateFormat } = props;

	const onChangePrime = ( date ) => onChange( dateFns.format( date, dateFormat ) );

	const onDateSelected = ( options: { selectable: boolean; date: Date } ) => {
		const { selectable, date } = options;

		if ( ! selectable ) {
			return;
		}

		if ( date !== null && date !== undefined ) {
			onChangePrime( date );
		}
	};

	const dayzedData = useDayzed( {
		showOutsideDays: true,
		onDateSelected,
		selected: dateFns.parse( value, dateFormat, new Date() ),
		minDate: dateFns.parse( minDate, dateFormat, new Date() ),
		maxDate: dateFns.parse( maxDate, dateFormat, new Date() ),
	} );

	return (
		<Popover className="relative">
			<Popover.Button className="bg-transparent p-0 m-0 border-none shadow-none outline-none">
				<input
					className="px-3 py-1.5 w-[120px] text-gray-900 focus:ring-indigo-500 focus:border-indigo-500 block shadow-sm sm:text-sm border-gray-300 rounded-md"
					type="text"
					value={ value }
					disabled={ disabled }
					onChange={ ( e ) => onChange( e.target.value ) }
					onBlur={ () => {
						if ( value ) {
							const d = dateFns.parse( value, dateFormat, new Date() );
							if ( dateFns.isValid( d ) ) {
								onChangePrime( d );
							} else {
								onChangePrime( undefined );
							}
						}
					} }
				/>
			</Popover.Button>
			<Popover.Panel className="absolute z-40 w-[320px] my-3 left-0 px-0">
				<div className="text-center overflow-hidden p-[18px] mb-5 rounded-md bg-white ring-1 ring-black ring-opacity-5">
					<SingleDatepickerCalendar { ...dayzedData } />
					{ props.enableClear && (
						<div className="mt-2 flex justify-end">
							<button className="inline-flex items-center justify-center rounded h-[35px] py-0 px-[15px] text-gray-600 text-[13px] font-medium outline-none cursor-pointer decoration-inherit text-center bg-white border border-gray-200 border-solid shadow-sm" onClick={ () => onChange( '' ) }>Clear</button>
						</div>
					) }
				</div>
			</Popover.Panel>
		</Popover>
	);
}

function SingleDatepickerCalendar( props: RenderProps ) {
	const { calendars, getDateProps, getBackProps, getForwardProps } = props;

	if ( ! calendars || calendars.length === 0 ) {
		return null;
	}

	return (
		<div>
			<div>
				{ calendars.map( ( calendar ) => {
					return (
						<div key={ `${ calendar.month }${ calendar.year }` }>
							<div className="flex justify-between items-center text-sm mb-3">
								<div>
									<button
										className="bg-transparent p-0 m-0 border-none shadow-none outline-none cursor-pointer"
										{ ...getBackProps( { calendars } ) }
									>
										<svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={ 2 }>
											<path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
										</svg>
									</button>
								</div>
								<div className="text-sm font-medium text-gray-700">
									{ MONTH_NAMES[ calendar.month ] } { calendar.year }
								</div>
								<div>
									<button
										className="bg-transparent p-0 m-0 border-none shadow-none outline-none cursor-pointer"
										{ ...getForwardProps( { calendars } ) }
									>
										<svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={ 2 }>
											<path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
										</svg>
									</button>
								</div>
							</div>
							<div className="grid grid-cols-7 gap-[6px] justify-center items-center">
								{ DAY_NAMES.map( ( day ) => (
									<div className="flex justify-center items-center h-[26px]" key={ `${ calendar.month }${ calendar.year }${ day }` }>
										<span className="text-[13px] font-semibold text-gray-600 select-none">
											{ day }
										</span>
									</div>
								) ) }
								{ calendar.weeks.map( ( week, weekIndex ) => {
									return week.map( ( dateObj: DateObj, index ) => {
										const {
											date,
											today,
											selected,
										} = dateObj;

										const key = `${ calendar.month }${ calendar.year }${ weekIndex }${ index }`;

										const datePops = { ...getDateProps( { dateObj } ) };

										return (
											<button
												className={ `bg-transparent p-0 m-0 border-none shadow-none outline-none cursor-pointer text-[13px] h-[26px] w-[26px] rounded mx-auto select-none font-medium ${ selected ? 'bg-gray-200' : '' } ${ datePops.disabled ? 'text-gray-400' : 'text-gray-600' }` }
												{ ...datePops }
												key={ key }
											>
												{ date.getDate() }
											</button>
										);
									} );
								} ) }
							</div>
						</div>
					);
				} ) }
			</div>
		</div>
	);
}
