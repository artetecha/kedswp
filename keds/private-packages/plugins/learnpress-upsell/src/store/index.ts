import { configureStore, ThunkAction, Action, combineReducers, AnyAction } from '@reduxjs/toolkit';
import packageReducer from './package';
import couponReducer from './coupon';

const combinedReducers = combineReducers( {
	package: packageReducer,
	coupon: couponReducer,
} );

const rootReducer = ( state: ReturnType<typeof combinedReducers>, action: AnyAction ) => {
	return combinedReducers( state, action );
};

export function makeStore() {
	return configureStore( {
		reducer: rootReducer,
	} );
}

const store = makeStore();

export type AppStore = ReturnType<typeof makeStore>;

export type AppState = ReturnType<typeof store.getState>

export type AppDispatch = typeof store.dispatch

export type AppThunk<ReturnType = void> = ThunkAction<
  ReturnType,
  AppState,
  unknown,
  Action<string>
>

export default store;
