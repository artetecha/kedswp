import { motion, AnimatePresence } from 'framer-motion';
import { useState, createContext, useEffect, useCallback } from '@wordpress/element';

export const ToastContext = createContext( null );

interface ToastProps {
	children: React.ReactNode;
}

type ToastType = 'success' | 'error' | 'info';

const COLOR = {
	success: {
		bg: 'bg-slate-700',
		text: 'text-white',
		border: 'border-slate-700',
	},
	error: {
		bg: 'bg-red-700',
		text: 'text-white',
		border: 'border-red-700',
	},
	info: {
		bg: 'bg-slate-700',
		text: 'text-white',
		border: 'border-slate-700',
	},
};

export const ToastContextProvider = ( { children }: ToastProps ) => {
	const [ toasts, setToasts ] = useState( [] );

	useEffect( () => {
		if ( toasts.length > 0 ) {
			const timer = setTimeout( () => {
				setToasts( toasts.slice( 1 ) );
			}, 3000 );

			return () => clearTimeout( timer );
		}
	}, [ toasts ] );

	const addToast = useCallback(
		( message: string, type: ToastType = 'success' ) => {
			setToasts( [ ...toasts, { message, type } ] );
		},
		[ toasts ]
	);

	return (
		<ToastContext.Provider value={ addToast }>
			{ children }
			<div className="fixed bottom-10 transform -translate-x-1/2 left-1/2 text-[17px] z-[9999999] pointer-events-none">
				<AnimatePresence initial={ false }>
					{ toasts.length > 0 && toasts.map( ( toast, index ) => (
						<motion.div
							layout
							initial={ { y: 100, opacity: 0 } }
							animate={ { y: 0, opacity: 1 } }
							exit={ { y: 20, opacity: 0 } }
							transition={ {
								opacity: { duration: 0.3 },
							} }
							key={ index }
							className={ `${ COLOR[ toast.type ].bg } ${ COLOR[ toast.type ].text } border border-solid ${ COLOR[ toast.type ].border } py-4 px-5 mb-4 rounded shadow-lg` }
						>
							{ toast.message }
						</motion.div>
					) ) }
				</AnimatePresence>
			</div>
		</ToastContext.Provider>
	);
};
