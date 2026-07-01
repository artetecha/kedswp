import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
	SelectControl,
} from '@wordpress/components';

import {
	useBlockProps,
	BlockControls,
	InspectorControls,
	AlignmentToolbar,
	JustifyToolbar,
	BlockVerticalAlignmentToolbar,
} from '@wordpress/block-editor';

const Edit = ( props ) => {
	const { attributes, setAttributes } = props;
	const isIconOnly = attributes.layout === 'icon-only';

	const mapAlignItems = {
		top: 'flex-start',
		center: 'center',
		bottom: 'flex-end',
	};

	// Outer wrapper style (flex container)
	const wrapperStyle = {
		display: 'flex',
		justifyContent: attributes.justifyContent,
		width: '100%',
	};

	// Button style - includes width
	const buttonStyle = {
		width: isIconOnly ? 'auto' : ( attributes.width ? `${ attributes.width }%` : '100%' ),
		justifyContent: attributes.textAlign,
		alignItems: mapAlignItems[ attributes.alignItems ] || 'center',
	};

	// Apply blockProps to button element (Gutenberg styles like color, typography)
	// This matches PHP render behavior where wrapper classes are merged onto button
	const blockProps = useBlockProps( {
		className: `lp-button lp-button-wishlist-action ${ attributes.layout || 'modern' }`,
		style: buttonStyle,
	} );

	// Render button content based on layout
	const renderButtonContent = () => {
		switch ( attributes.layout ) {
			case 'icon-only':
				return (
					<svg width="20" height="20" viewBox="0 0 20 20" fill="none">
						<path
							fillRule="evenodd"
							clipRule="evenodd"
							d="M12.0516 2.07769C12.7089 1.80532 13.4135 1.66513 14.125 1.66513C14.8366 1.66513 15.5411 1.80532 16.1985 2.07769C16.8557 2.35002 17.4528 2.74914 17.9558 3.25226C18.4589 3.75522 18.8583 4.35264 19.1306 5.00986C19.403 5.66721 19.5432 6.37178 19.5432 7.08332C19.5432 7.79486 19.403 8.49943 19.1306 9.15677C18.8583 9.81406 18.4591 10.4113 17.9559 10.9142C17.9559 10.9143 17.956 10.9142 17.9559 10.9142L10.5893 18.2809C10.2638 18.6063 9.7362 18.6063 9.41076 18.2809L2.0441 10.9142C1.02807 9.89822 0.457275 8.52019 0.457275 7.08332C0.457275 5.64644 1.02807 4.26842 2.0441 3.2524C3.06012 2.23637 4.43814 1.66558 5.87502 1.66558C7.31189 1.66558 8.68992 2.23637 9.70594 3.2524L10 3.54647L10.294 3.25253C10.2939 3.25258 10.294 3.25249 10.294 3.25253C10.7969 2.74935 11.3943 2.35004 12.0516 2.07769ZM14.125 3.3318C13.6324 3.3318 13.1446 3.42885 12.6895 3.61742C12.2345 3.80598 11.821 4.08236 11.4727 4.43077L10.5893 5.31424C10.2638 5.63968 9.7362 5.63968 9.41076 5.31424L8.52743 4.43091C7.82397 3.72744 6.86986 3.33224 5.87502 3.33224C4.88017 3.33224 3.92607 3.72744 3.22261 4.43091C2.51914 5.13437 2.12394 6.08847 2.12394 7.08332C2.12394 8.07817 2.51914 9.03227 3.22261 9.73573L10 16.5131L16.7774 9.73573C17.1258 9.38749 17.4024 8.97387 17.5909 8.51879C17.7795 8.0637 17.8765 7.57592 17.8765 7.08332C17.8765 6.59071 17.7795 6.10294 17.5909 5.64785C17.4024 5.19276 17.126 4.77929 16.7776 4.43105C16.4293 4.08264 16.0156 3.80598 15.5605 3.61742C15.1054 3.42885 14.6176 3.3318 14.125 3.3318Z"
							fill="currentColor"
						/>
					</svg>
				);
			case 'classic':
				return (
					<>
						<i className="lp-icon-heart-o" style={ { color: 'inherit' } }></i>
						{ __( 'Add to Wishlist', 'learnpress-wishlist' ) }
					</>
				);
			case 'modern':
			default:
				return (
					<>
						<i className="lp-icon-heart-o" style={ { color: 'inherit' } }></i>
						{ __( 'Wishlist', 'learnpress-wishlist' ) }
					</>
				);
		}
	};

	return (
		<>
			<BlockControls>
				<AlignmentToolbar
					value={ attributes.textAlign }
					onChange={ ( newAlign ) => setAttributes( { textAlign: newAlign } ) }
				/>
				<JustifyToolbar
					value={ attributes.justifyContent }
					onChange={ ( newJustify ) => setAttributes( { justifyContent: newJustify } ) }
				/>
				<BlockVerticalAlignmentToolbar
					value={ attributes.alignItems }
					onChange={ ( newAlign ) => setAttributes( { alignItems: newAlign } ) }
				/>
			</BlockControls>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'learnpress-wishlist' ) }>
					<SelectControl
						label={ __( 'Layout', 'learnpress-wishlist' ) }
						value={ attributes.layout }
						options={ [
							{ label: __( 'Classic', 'learnpress-wishlist' ), value: 'classic' },
							{ label: __( 'Modern', 'learnpress-wishlist' ), value: 'modern' },
							{ label: __( 'Icon Only', 'learnpress-wishlist' ), value: 'icon-only' },
						] }
						onChange={ ( value ) => setAttributes( { layout: value } ) }
					/>
					{ ! isIconOnly && (
						<ToggleGroupControl
							label={ __( 'Width', 'learnpress-wishlist' ) }
							value={ attributes.width || '100' }
							onChange={ ( value ) => {
								setAttributes( {
									width: value || '100',
								} );
							} }
							isBlock={ true }
						>
							<ToggleGroupControlOption value="25" label="25%" />
							<ToggleGroupControlOption value="50" label="50%" />
							<ToggleGroupControlOption value="75" label="75%" />
							<ToggleGroupControlOption value="100" label="100%" />
						</ToggleGroupControl>
					) }
				</PanelBody>
			</InspectorControls>
			<div className="course-button-wishlist__wrapper" style={ wrapperStyle }>
				{ /* Button receives blockProps for Gutenberg color/typography */ }
				<button { ...blockProps }>
					{ renderButtonContent() }
				</button>
			</div>
		</>
	);
};

export default Edit;
