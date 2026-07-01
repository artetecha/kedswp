import { __ } from '@wordpress/i18n';
import { Placeholder } from '@wordpress/components';
import { useBlockProps } from '@wordpress/block-editor';

const Edit = ( props ) => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<Placeholder label={ __( 'Single Package (Legacy)', 'learnpress-upsell' ) }>
				<div>
					{ __(
						'Display full content of Single Package, can not edit.',
						'learnpress-upsell'
					) }
				</div>
			</Placeholder>
		</div>
	);
};

export default Edit;
