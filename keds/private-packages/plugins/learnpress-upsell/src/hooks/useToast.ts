import { useContext } from '@wordpress/element';
import { ToastContext } from 'src/contexts/Toast';

export default function useToast() {
	return useContext( ToastContext );
}
