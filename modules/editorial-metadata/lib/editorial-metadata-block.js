import {
	BaseControl,
	Button,
	DateTimePicker,
	Dropdown,
	__experimentalHStack as HStack,
	__experimentalText as Text,
	TextControl,
	ToggleControl,
	__experimentalVStack as VStack,
} from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import './editor.scss';

const editorialMetadatas = window.VW_EDITORIAL_METADATA.editorial_metadata_terms.map(
	editorialMetadata => ( {
		key: editorialMetadata.meta_key,
		label: editorialMetadata.name,
		type: editorialMetadata.type,
		term_id: editorialMetadata.term_id,
		description: editorialMetadata.description,
	} )
);

const noEditorialMetadatasToShow = editorialMetadatas.length === 0;

const CustomMetaPanel = ( { metaFields, setMetaFields } ) => (
	<PluginDocumentSettingPanel name="editorialMetadataPanel" title="Editorial Metadata">
		{ noEditorialMetadatasToShow && (
			<Text>
				{ __(
					'Configure your editorial metadata, within the VIP Workflow Plugin Settings.',
					'vip-workflow'
				) }
			</Text>
		) }
		{ editorialMetadatas.map( editorialMetadata =>
			getComponentByType( editorialMetadata, metaFields, setMetaFields )
		) }
	</PluginDocumentSettingPanel>
);

const getComponentByType = ( editorialMetadata, metaFields, setMetaFields ) => {
	switch ( editorialMetadata.type ) {
		case 'checkbox':
			return (
				<VStack __nextHasNoMarginBottom>
					<HStack __nextHasNoMarginBottom>
						<BaseControl __nextHasNoMarginBottom label={ editorialMetadata.label } />
						<ToggleControl
							__nextHasNoMarginBottom
							key={ editorialMetadata.key }
							checked={ metaFields?.[ editorialMetadata.key ] }
							onChange={ value =>
								setMetaFields( {
									...metaFields,
									[ editorialMetadata.key ]: value,
								} )
							}
						/>
					</HStack>
					<BaseControl help={ editorialMetadata.description } />
				</VStack>
			);
		case 'text':
			return (
				<VStack __nextHasNoMarginBottom>
					<BaseControl __nextHasNoMarginBottom label={ editorialMetadata.label } />
					<TextControl
						__nextHasNoMarginBottom
						key={ editorialMetadata.key }
						value={ metaFields?.[ editorialMetadata.key ] }
						className={ editorialMetadata.key }
						onChange={ value =>
							setMetaFields( {
								...metaFields,
								[ editorialMetadata.key ]: value,
							} )
						}
					/>
					<BaseControl help={ editorialMetadata.description } />
				</VStack>
			);
		case 'date':
			return (
				<DateComponent
					editorialMetadata={ editorialMetadata }
					metaFields={ metaFields }
					setMetaFields={ setMetaFields }
				/>
			);
		default:
			return null;
	}
};

const DateComponent = ( { editorialMetadata, metaFields, setMetaFields } ) => {
	const [ popoverAnchor, setPopoverAnchor ] = useState( null );
	// Memoize popoverProps to avoid returning a new object every time.
	const popoverProps = useMemo(
		() => ( {
			// Anchor the popover to the middle of the entire row so that it doesn't
			// move around when the label changes.
			anchor: popoverAnchor,
			'aria-label': __( 'Select date' ),
			placement: 'left-start',
			offset: 36,
			shift: true,
		} ),
		[ popoverAnchor ]
	);

	let label = metaFields?.[ editorialMetadata.key ];

	// format the datetime string in a human-readable format
	if ( label ) {
		const date = new Date( label );
		label = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
	} else {
		label = __( 'Select date' );
	}

	return (
		<Dropdown
			key={ editorialMetadata.key }
			ref={ setPopoverAnchor }
			popoverProps={ popoverProps }
			focusOnMount
			renderToggle={ ( { onToggle, isOpen } ) => (
				<VStack __nextHasNoMarginBottom>
					<HStack __nextHasNoMarginBottom>
						<BaseControl __nextHasNoMarginBottom label={ editorialMetadata.label } />
						<Button
							size="compact"
							variant="tertiary"
							onClick={ onToggle }
							aria-label={ editorialMetadata.label }
							aria-expanded={ isOpen }
						>
							{ label }
						</Button>
					</HStack>
					<BaseControl help={ editorialMetadata.description } />
				</VStack>
			) }
			renderContent={ ( { onClose } ) => (
				<DateTimePicker
					currentDate={ metaFields?.[ editorialMetadata.key ] ?? undefined }
					onClose={ onClose }
					value={ metaFields?.[ editorialMetadata.key ] }
					onChange={ value =>
						setMetaFields( {
							...metaFields,
							[ editorialMetadata.key ]: value,
						} )
					}
				/>
			) }
		/>
	);
};

const applyWithSelect = select => {
	return {
		metaFields: select( 'core/editor' ).getEditedPostAttribute( 'meta' ),
	};
};

const applyWithDispatch = dispatch => {
	return {
		setMetaFields( newValue ) {
			dispatch( 'core/editor' ).editPost( { meta: newValue } );
		},
	};
};

const plugin = compose(
	withSelect( applyWithSelect ),
	withDispatch( applyWithDispatch )
)( CustomMetaPanel );

registerPlugin( 'vip-workflow-editorial-metadata', {
	render: plugin,
	icon: 'vip-workflow',
} );
