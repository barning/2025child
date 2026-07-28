import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import {
	AlignmentToolbar,
	BlockControls,
	InspectorControls,
	PanelColorSettings,
	useBlockProps,
} from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl } from '@wordpress/components';
import metadata from './block.json';
import './editor.css';
import './style.css';

const SIZE_CONFIG = {
	sm: {
		fontSize: '0.95rem',
		paddingY: '0.5rem',
		paddingX: '0.9rem',
	},
	md: {
		fontSize: '1rem',
		paddingY: '0.8rem',
		paddingX: '1.3rem',
	},
	lg: {
		fontSize: '1.1rem',
		paddingY: '1rem',
		paddingX: '1.6rem',
	},
};

function Edit( { attributes, setAttributes } ) {
	const {
		buttonBackground,
		buttonBorder,
		buttonText,
		buttonHoverBorder,
		buttonSize,
		buttonAlign,
		buttonLikedBackground,
		buttonFocusOutline,
		buttonErrorBorder,
		ctaText,
		reactionEmoji,
	} = attributes;

	const style = {};
	const sizeConfig = SIZE_CONFIG[ buttonSize ] || SIZE_CONFIG.md;
	if ( buttonBackground ) {
		style[ '--child-post-likes-bg' ] = buttonBackground;
	}
	if ( buttonBorder ) {
		style[ '--child-post-likes-border' ] = buttonBorder;
	}
	if ( buttonText ) {
		style[ '--child-post-likes-text' ] = buttonText;
	}
	if ( buttonHoverBorder ) {
		style[ '--child-post-likes-hover-border' ] = buttonHoverBorder;
	}
	if ( sizeConfig ) {
		style[ '--child-post-likes-font-size' ] = sizeConfig.fontSize;
		style[ '--child-post-likes-padding-y' ] = sizeConfig.paddingY;
		style[ '--child-post-likes-padding-x' ] = sizeConfig.paddingX;
	}
	if ( buttonAlign ) {
		style[ '--child-post-likes-align' ] = buttonAlign;
	}
	if ( buttonLikedBackground ) {
		style[ '--child-post-likes-liked-bg' ] = buttonLikedBackground;
	}
	if ( buttonFocusOutline ) {
		style[ '--child-post-likes-focus' ] = buttonFocusOutline;
	}
	if ( buttonErrorBorder ) {
		style[ '--child-post-likes-error-border' ] = buttonErrorBorder;
	}

	const blockProps = useBlockProps( { style } );

	const ctaTextValue = typeof ctaText === 'string' ? ctaText.trim() : '';

	return (
		<>
			<BlockControls>
				<AlignmentToolbar
					value={ buttonAlign }
					onChange={ ( value ) =>
						setAttributes( { buttonAlign: value || 'left' } )
					}
				/>
			</BlockControls>
			<InspectorControls>
				<PanelBody
					title={ __( 'Layout', 'child' ) }
					initialOpen={ true }
				>
					<SelectControl
						label={ __( 'Schaltflächengröße', 'child' ) }
						value={ buttonSize }
						options={ [
							{ label: __( 'Klein', 'child' ), value: 'sm' },
							{ label: __( 'Mittel', 'child' ), value: 'md' },
							{ label: __( 'Groß', 'child' ), value: 'lg' },
						] }
						onChange={ ( value ) =>
							setAttributes( { buttonSize: value } )
						}
					/>
					<TextControl
						label={ __( 'Aufruftext', 'child' ) }
						value={ ctaText }
						onChange={ ( value ) =>
							setAttributes( { ctaText: value } )
						}
						help={ __(
							'Kurzer Text vor Emoji und Anzahl der Likes.',
							'child'
						) }
					/>
					<TextControl
						label={ __( 'Emoji', 'child' ) }
						value={ reactionEmoji }
						onChange={ ( value ) =>
							setAttributes( { reactionEmoji: value } )
						}
						help={ __( 'Füge ein beliebiges Emoji ein.', 'child' ) }
					/>
				</PanelBody>
				<PanelColorSettings
					title={ __( 'Schaltflächenfarben', 'child' ) }
					colorSettings={ [
						{
							label: __( 'Hintergrund', 'child' ),
							value: buttonBackground,
							onChange: ( value ) =>
								setAttributes( { buttonBackground: value } ),
						},
						{
							label: __( 'Rahmen', 'child' ),
							value: buttonBorder,
							onChange: ( value ) =>
								setAttributes( { buttonBorder: value } ),
						},
						{
							label: __( 'Text', 'child' ),
							value: buttonText,
							onChange: ( value ) =>
								setAttributes( { buttonText: value } ),
						},
						{
							label: __(
								'Rahmen bei Mauszeigerkontakt',
								'child'
							),
							value: buttonHoverBorder,
							onChange: ( value ) =>
								setAttributes( { buttonHoverBorder: value } ),
						},
						{
							label: __( 'Hintergrund nach Like', 'child' ),
							value: buttonLikedBackground,
							onChange: ( value ) =>
								setAttributes( {
									buttonLikedBackground: value,
								} ),
						},
						{
							label: __( 'Fokusumrandung', 'child' ),
							value: buttonFocusOutline,
							onChange: ( value ) =>
								setAttributes( { buttonFocusOutline: value } ),
						},
						{
							label: __( 'Fehlerrahmen', 'child' ),
							value: buttonErrorBorder,
							onChange: ( value ) =>
								setAttributes( { buttonErrorBorder: value } ),
						},
					] }
				/>
			</InspectorControls>
			<div { ...blockProps }>
				<button
					type="button"
					className="child-post-likes__button is-editor-preview"
					disabled
				>
					<span className="child-post-likes__pill">
						{ ctaTextValue !== '' && (
							<span className="child-post-likes__cta">
								{ ctaTextValue }
							</span>
						) }
						<span
							className="child-post-likes__icon"
							aria-hidden="true"
						>
							{ reactionEmoji }
						</span>
						<span className="child-post-likes__count">0</span>
					</span>
				</button>
				<p className="child-post-likes__help">
					{ __(
						'Besucher können diesen Beitrag mit „Gefällt mir“ markieren.',
						'child'
					) }
				</p>
			</div>
		</>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
