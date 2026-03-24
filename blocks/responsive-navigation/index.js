import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	InnerBlocks,
	useBlockProps,
} from '@wordpress/block-editor';
import { PanelBody, TextControl, Notice } from '@wordpress/components';
import metadata from './block.json';
import './editor.css';
import './style.css';

const ALLOWED_BLOCKS = [ 'core/list' ];

const TEMPLATE = [
	[
		'core/list',
		{
			className: 'child-responsive-navigation__list',
			ordered: false,
		},
		[
			[ 'core/list-item', { content: '<a href="#">Home</a>' } ],
			[ 'core/list-item', { content: '<a href="#">About</a>' } ],
			[ 'core/list-item', { content: '<a href="#">Contact</a>' } ],
		],
	],
];

function Edit( { attributes, setAttributes } ) {
	const { ariaLabel, toggleLabel } = attributes;
	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Navigation settings', 'child' ) }>
					<TextControl
						label={ __( 'Navigation aria-label', 'child' ) }
						value={ ariaLabel }
						onChange={ ( value ) => setAttributes( { ariaLabel: value } ) }
					/>
					<TextControl
						label={ __( 'Burger label', 'child' ) }
						value={ toggleLabel }
						onChange={ ( value ) => setAttributes( { toggleLabel: value } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<nav { ...blockProps } aria-label={ ariaLabel }>
				<Notice status="info" isDismissible={ false }>
					{ __(
						'This block is CSS-only on the frontend: links are shown inline on wide screens and in a burger disclosure on narrow screens.',
						'child'
					) }
				</Notice>
				<InnerBlocks
					allowedBlocks={ ALLOWED_BLOCKS }
					template={ TEMPLATE }
					templateLock={ false }
				/>
			</nav>
		</>
	);
}

function Save( { attributes } ) {
	const { ariaLabel, toggleLabel } = attributes;
	const blockProps = useBlockProps.save();

	return (
		<nav { ...blockProps } aria-label={ ariaLabel }>
			<div className="child-responsive-navigation__inline" aria-hidden="true">
				<InnerBlocks.Content />
			</div>
			<details className="child-responsive-navigation__disclosure">
				<summary>{ `☰ ${ toggleLabel }` }</summary>
				<div className="child-responsive-navigation__panel">
					<InnerBlocks.Content />
				</div>
			</details>
		</nav>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: Save,
} );
