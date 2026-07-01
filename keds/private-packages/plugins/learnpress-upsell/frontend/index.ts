/* eslint-disable @wordpress/no-global-event-listener */
import './index.scss';

import { AddToCart, AddShowMoreButton, Slider } from './ts/package';
import Orderby from './ts/orderby';
import CourseTab from './ts/coursetab';

document.addEventListener( 'DOMContentLoaded', () => {
	AddToCart();
	Orderby();
	CourseTab();
	Slider();
} );

AddShowMoreButton();
