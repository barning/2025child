/* global childNoteThreadSettings */

( function () {
	'use strict';

	const { ComboboxControl, Notice, TextControl } = wp.components;
	const { useSelect, useDispatch } = wp.data;
	const { PluginDocumentSettingPanel } = wp.editPost;
	const { createElement: el, useState } = wp.element;
	const { __, sprintf } = wp.i18n;
	const { registerPlugin } = wp.plugins;

	function plainText( html ) {
		const wrapper = document.createElement( 'div' );
		wrapper.innerHTML = html || '';
		return ( wrapper.textContent || '' ).trim();
	}

	function noteLabel( note ) {
		const date = note.date
			? new Intl.DateTimeFormat( undefined, {
					dateStyle: 'medium',
					timeStyle: 'short',
			  } ).format( new Date( note.date ) )
			: __( 'Undated Note', 'child' );
		const excerpt = plainText(
			note.excerpt?.rendered || note.content?.rendered || ''
		).replace( /\s+/g, ' ' );

		return excerpt ? `${ date } — ${ excerpt.slice( 0, 90 ) }` : date;
	}

	function NoteThreadPanel() {
		const [ search, setSearch ] = useState( '' );
		const editor = useSelect( ( select ) => {
			const editorStore = select( 'core/editor' );
			const postId = editorStore.getCurrentPostId();
			const postType = editorStore.getCurrentPostType();
			const entity = postId
				? select( 'core' ).getEntityRecord(
						'postType',
						childNoteThreadSettings.postType,
						postId,
						{ context: 'edit' }
				  )
				: null;

			return {
				postId,
				postType,
				status: editorStore.getEditedPostAttribute( 'status' ),
				meta: editorStore.getEditedPostAttribute( 'meta' ) || {},
				thread: entity?.child_note_thread || null,
			};
		}, [] );

		const notes = useSelect(
			( select ) => {
				const query = {
					status: 'publish',
					per_page: 100,
					orderby: 'date',
					order: 'desc',
					context: 'edit',
				};

				if ( search.trim() ) {
					query.search = search.trim();
				}

				return select( 'core' ).getEntityRecords(
					'postType',
					childNoteThreadSettings.postType,
					query
				);
			},
			[ search ]
		);

		const parentId = Number(
			editor.meta[ childNoteThreadSettings.parentMetaKey ] || 0
		);
		const selectedParent = useSelect(
			( select ) =>
				parentId
					? select( 'core' ).getEntityRecord(
							'postType',
							childNoteThreadSettings.postType,
							parentId,
							{ context: 'edit' }
					  )
					: null,
			[ parentId ]
		);
		const { editPost } = useDispatch( 'core/editor' );

		if ( editor.postType !== childNoteThreadSettings.postType ) {
			return null;
		}

		const candidates = Array.isArray( notes )
			? notes.filter( ( note ) => {
					if ( note.id === editor.postId ) {
						return false;
					}

					const state = note.child_note_thread;
					return Boolean(
						state?.appendable || state?.child_id === editor.postId
					);
			  } )
			: [];
		const options = [
			{
				value: '0',
				label: __( 'Start a new thread', 'child' ),
			},
			...candidates.map( ( note ) => ( {
				value: String( note.id ),
				label: noteLabel( note ),
			} ) ),
		];

		if (
			selectedParent &&
			! options.some( ( option ) => option.value === String( parentId ) )
		) {
			options.push( {
				value: String( parentId ),
				label: noteLabel( selectedParent ),
			} );
		}

		const locked = Boolean( editor.thread?.locked );
		const parentState = selectedParent?.child_note_thread;
		const atmospherePending = Boolean(
			parentId &&
				parentState?.atmosphere_active &&
				! parentState?.atmosphere_ready
		);

		return el(
			PluginDocumentSettingPanel,
			{
				name: 'child-note-thread',
				title: __( 'Thread', 'child' ),
				className: 'child-note-thread-panel',
			},
			locked
				? el( TextControl, {
						label: __( 'Previous Note', 'child' ),
						help: __(
							'The thread relationship is locked after publication.',
							'child'
						),
						value: selectedParent
							? noteLabel( selectedParent )
							: __( 'Start a new thread', 'child' ),
						readOnly: true,
				  } )
				: el( ComboboxControl, {
						label: __( 'Previous Note', 'child' ),
						help: __(
							'Choose the latest published Note in a thread, or start a new one.',
							'child'
						),
						value: String( parentId ),
						options,
						isLoading: ! Array.isArray( notes ),
						onFilterValueChange: setSearch,
						onChange: ( value ) => {
							const nextParent = Number( value || 0 );
							editPost( {
								meta: {
									...editor.meta,
									[ childNoteThreadSettings.parentMetaKey ]:
										nextParent,
								},
							} );
						},
				  } ),
			atmospherePending
				? el(
						Notice,
						{
							status: 'warning',
							isDismissible: false,
						},
						sprintf(
							/* translators: %s: Bluesky service name. */
							__(
								'The previous Note has not reached %s yet. Publication will remain blocked until it has synced.',
								'child'
							),
							'Bluesky'
						)
				  )
				: null,
			editor.thread?.child_id
				? el(
						Notice,
						{
							status: 'info',
							isDismissible: false,
						},
						__(
							'This Note already has a continuation, so it is not the end of its thread.',
							'child'
						)
				  )
				: null
		);
	}

	registerPlugin( 'child-note-thread', {
		render: NoteThreadPanel,
		icon: 'share-alt2',
	} );
} )();
