import './index.scss';
import { __ } from '@/utils/i18n';
import { render, useEffect, useState } from '@wordpress/element';
import { Tab } from '@headlessui/react';
import { Provider } from 'react-redux';
import ListPackage from '@/package/ListPackage';
import ListCoupon from '@/coupon/ListCoupon';
import store from './store';
import { ToastContextProvider } from '@/contexts/Toast';

const TABS = [
	{
		id: 'package',
		title: __( 'Packages' ),
		content: <ListPackage />,
	},
	{
		id: 'curriculum',
		title: __( 'Coupons' ),
		content: <ListCoupon />,
	},
];

function App() {
	return (
		<Provider store={ store }>
			<ToastContextProvider>
				<div className="text-[15px] antialiased text-slate-500">
					<header className="text-center m-0 px-8 pt-10 bg-slate-100">
						<h2 className="text-2xl font-bold text-gray-700 m-0">LearnPress Upsell</h2>
					</header>
					<Tab.Group>
						<Tab.List className="m-0 pt-10 flex flex-wrap w-full space-x-2 text-center justify-center bg-slate-100">
							{ TABS.map( ( tab ) => (
								<Tab key={ tab.id } className={ ( { selected } ) => `${ selected ? 'bg-white text-slate-600' : 'text-slate-500 bg-transparent' } flex items-center justify-center text-[15px] outline-none rounded-t-[2px] px-6 h-[48px] border-none decoration-none cursor-pointer focus:outline-none` }>
									{ tab.title }
								</Tab>
							) ) }
						</Tab.List>

						<Tab.Panels className="mx-auto max-w-[1200px] min-h-[400px] relative">
							<div className="py-10">
								{ TABS.map( ( tab ) => (
									<Tab.Panel key={ tab.id }>
										{ tab.content }
									</Tab.Panel>
								) ) }
							</div>
						</Tab.Panels>
					</Tab.Group>
				</div>
			</ToastContextProvider>
		</Provider>
	);
}

render( <App />, document.getElementById( 'learnpress-upsell-root' ) );

console.log( '%c LearnPress Upsell by Nhamdv( daonham95@gmail.com )', 'background: #222; color: #bada55' );
