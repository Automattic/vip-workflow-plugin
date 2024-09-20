import {
	BaseControl,
	Button,
	DateTimePicker,
	Dropdown,
	Flex,
	__experimentalText as Text,
	TextControl,
	ToggleControl,
	Tooltip,
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
	if ( editorialMetadata.type === 'checkbox' ) {
		return (
			<CheckboxComponent
				editorialMetadata={ editorialMetadata }
				metaFields={ metaFields }
				setMetaFields={ setMetaFields }
			/>
		);
	} else if ( editorialMetadata.type === 'text' ) {
		return (
			<TextComponent
				editorialMetadata={ editorialMetadata }
				metaFields={ metaFields }
				setMetaFields={ setMetaFields }
			/>
		);
	} else {
		return (
			<DateComponent
				editorialMetadata={ editorialMetadata }
				metaFields={ metaFields }
				setMetaFields={ setMetaFields }
			/>
		);
	}
};

const CheckboxComponent = ( { editorialMetadata, metaFields, setMetaFields } ) => {
	return (
		<Flex direction={ [ 'row' ] } justify={ 'start' } align={ 'start' }>
			<BaseControl label={ editorialMetadata.label }></BaseControl>
			<Tooltip text={ editorialMetadata.description }>
				<ToggleControl
					key={ editorialMetadata.key }
					checked={ metaFields?.[ editorialMetadata.key ] }
					onChange={ value =>
						setMetaFields( {
							...metaFields,
							[ editorialMetadata.key ]: value,
						} )
					}
				/>
			</Tooltip>
		</Flex>
	);
};

const TextComponent = ( { editorialMetadata, metaFields, setMetaFields } ) => {
	return (
		<Flex direction={ [ 'row' ] } justify={ 'start' } align={ 'start' }>
			<BaseControl label={ editorialMetadata.label }></BaseControl>
			<Tooltip text={ editorialMetadata.description }>
				<TextControl
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
			</Tooltip>
		</Flex>
	);
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
	const label = metaFields?.[ editorialMetadata.key ];

	return (
		<Dropdown
			key={ editorialMetadata.key }
			ref={ setPopoverAnchor }
			popoverProps={ popoverProps }
			focusOnMount
			renderToggle={ ( { onToggle, isOpen } ) => (
				<Flex direction={ [ 'row' ] } justify={ 'start' } align={ 'start' }>
					<BaseControl label={ editorialMetadata.label }></BaseControl>
					<Tooltip text={ editorialMetadata.description }>
						<Button
							size="compact"
							variant="tertiary"
							tooltipPosition="middle left"
							onClick={ onToggle }
							aria-label={ editorialMetadata.label }
							aria-expanded={ isOpen }
						>
							{ label }
						</Button>
					</Tooltip>
				</Flex>
			) }
			renderContent={ ( { onClose } ) => (
				<DateTimePicker
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
