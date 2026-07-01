import { createSlice } from '@reduxjs/toolkit';
import type { AppState } from './index';

const initialState = {
	id: '',
	title: '',
	description: null,
	status: 'publish',
	publishDate: '',
	price: 0,
	newPriceEnabled: false,
	newPriceType: 'percent',
	newPriceAmount: 0,
	salePrice: 0,
	courses: [],
	featuredImage: {
		id: '',
		url: '',
	},
	tags: [],
	is_elementor: false,
	certificateID: 0,
};

export const packageSlice = createSlice( {
	name: 'package',
	initialState,
	reducers: {
		reset: ( state ) => {
			state.id = '';
			state.title = '';
			state.description = '';
			state.status = 'publish';
			state.publishDate = '';
			state.price = 0;
			state.newPriceEnabled = false;
			state.newPriceType = 'percent';
			state.newPriceAmount = 0;
			state.salePrice = 0;
			state.courses = [];
			state.featuredImage = {
				id: '',
				url: '',
			};
			state.tags = [];
			state.is_elementor = false;
			state.certificateID = 0;
		},
		changeValue: ( state, action ) => {
			if ( action.payload.id !== undefined ) {
				state.id = action.payload.id;
			}
			if ( action.payload.title !== undefined ) {
				state.title = action.payload.title;
			}
			if ( action.payload.description !== undefined ) {
				state.description = action.payload.description;
			}
			if ( action.payload.status !== undefined ) {
				state.status = action.payload.status;
			}
			if ( action.payload.publishDate !== undefined ) {
				state.publishDate = action.payload.publishDate;
			}
			if ( action.payload.newPriceEnabled !== undefined ) {
				state.newPriceEnabled = action.payload.newPriceEnabled;
			}
			if ( action.payload.newPriceType !== undefined ) {
				state.newPriceType = action.payload.newPriceType;

				// change the price.
				if ( state.newPriceType === 'percent' ) {
					state.salePrice = state.price - ( ( state.price * state.newPriceAmount ) / 100 );
					state.salePrice = Number.isInteger( state.salePrice ) ? state.salePrice : Number.parseFloat( state.salePrice ).toFixed( 2 );
				}
			}
			if ( action.payload.newPriceAmount !== undefined ) {
				state.newPriceAmount = action.payload.newPriceAmount;

				// change the price.
				if ( state.newPriceType === 'percent' ) {
					state.salePrice = state.price - ( ( state.price * action.payload.newPriceAmount ) / 100 );
					state.salePrice = Number.isInteger( state.salePrice ) ? state.salePrice : Number.parseFloat( state.salePrice ).toFixed( 2 );
				}
			}
			if ( action.payload.salePrice !== undefined ) {
				state.salePrice = action.payload.salePrice;
			}
			if ( action.payload.courses !== undefined ) {
				state.courses = action.payload.courses;

				state.price = 0;
				action.payload.courses.forEach( ( course ) => {
					state.price += course.price || 0;
				} );

				// change the price.
				if ( state.newPriceType === 'percent' ) {
					state.salePrice = state.price - ( ( state.price * state.newPriceAmount ) / 100 );
					state.salePrice = Number.isInteger( state.salePrice ) ? state.salePrice : Number.parseFloat( state.salePrice ).toFixed( 2 );
				}
			}
			if ( action.payload.featuredImage !== undefined ) {
				state.featuredImage = action.payload.featuredImage;
			}
			if ( action.payload.tags !== undefined ) {
				state.tags = action.payload.tags;
			}
			if ( action.payload.is_elementor !== undefined ) {
				state.is_elementor = action.payload.is_elementor;
			}
			if ( action.payload.certificateID !== undefined ) {
				state.certificateID = action.payload.certificateID;
			}
		},
	},
} );

export const packageSelection = ( state: AppState ) => state.package;

export const { reset, changeValue } = packageSlice.actions;

export default packageSlice.reducer;
