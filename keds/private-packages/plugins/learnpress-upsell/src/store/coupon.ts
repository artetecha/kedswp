import { createSlice } from '@reduxjs/toolkit';
import type { AppState } from './index';

const initialState = {
	id: '',
	title: '',
	description: '',
	status: 'publish',
	publishDate: '',
	discountType: 'percent',
	discountAmount: 0,
	discountStartDate: '',
	discountEndDate: '',
	limitPerCoupon: 0,
	limitPerUser: 0,
	includePackages: [],
	excludePackages: [],
	includeCourses: [],
	excludeCourses: [],
	includeCourseCategories: [],
	excludeCourseCategories: [],
	allowEmails: [],
};

export const couponSlice = createSlice( {
	name: 'coupon',
	initialState,
	reducers: {
		reset: ( state ) => {
			state.id = '';
			state.title = '';
			state.description = '';
			state.status = 'publish';
			state.publishDate = '';
			state.discountType = 'percent';
			state.discountAmount = 0;
			state.discountStartDate = '';
			state.discountEndDate = '';
			state.limitPerCoupon = 0;
			state.limitPerUser = 0;
			state.includePackages = [];
			state.excludePackages = [];
			state.includeCourses = [];
			state.excludeCourses = [];
			state.includeCourseCategories = [];
			state.excludeCourseCategories = [];
			state.allowEmails = [];
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
			if ( action.payload.discountType !== undefined ) {
				state.discountType = action.payload.discountType;

				if ( action.payload.discountType === 'percent' ) {
					if ( state.discountAmount > 100 ) {
						state.discountAmount = 100;
					}
				}
			}
			if ( action.payload.discountAmount !== undefined ) {
				state.discountAmount = action.payload.discountAmount;
			}
			if ( action.payload.discountStartDate !== undefined ) {
				state.discountStartDate = action.payload.discountStartDate;
			}
			if ( action.payload.discountEndDate !== undefined ) {
				state.discountEndDate = action.payload.discountEndDate;
			}
			if ( action.payload.limitPerCoupon !== undefined ) {
				state.limitPerCoupon = action.payload.limitPerCoupon;
			}
			if ( action.payload.limitPerUser !== undefined ) {
				state.limitPerUser = action.payload.limitPerUser;
			}
			if ( action.payload.includePackages !== undefined ) {
				state.includePackages = action.payload.includePackages;
			}
			if ( action.payload.excludePackages !== undefined ) {
				state.excludePackages = action.payload.excludePackages;
			}
			if ( action.payload.includeCourses !== undefined ) {
				state.includeCourses = action.payload.includeCourses;
			}
			if ( action.payload.excludeCourses !== undefined ) {
				state.excludeCourses = action.payload.excludeCourses;
			}
			if ( action.payload.includeCourseCategories !== undefined ) {
				state.includeCourseCategories = action.payload.includeCourseCategories;
			}
			if ( action.payload.excludeCourseCategories !== undefined ) {
				state.excludeCourseCategories = action.payload.excludeCourseCategories;
			}
			if ( action.payload.allowEmails !== undefined ) {
				state.allowEmails = action.payload.allowEmails;
			}
		},
	},
} );

export const { reset, changeValue } = couponSlice.actions;

export const couponSelection = ( state: AppState ) => state.coupon;

export default couponSlice.reducer;
