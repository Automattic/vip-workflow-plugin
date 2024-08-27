import { __experimentalText as Text, TextControl, ToggleControl } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { registerPlugin } from '@wordpress/plugins';
import './editor.scss';

const editorialMetadatas = window.VipWorkflowEditorialMetadatas.map( editorialMetadata => ( {
	key: editorialMetadata.meta_key,
	label: editorialMetadata.name,
	type: editorialMetadata.type,
	term_id: editorialMetadata.term_id,
	description: editorialMetadata.description,
} ) );

const noEditorialMetadatasToShow = editorialMetadatas.length === 0;

const CustomMetaPanel = ( { metaFields, setMetaFields } ) => (
	<PluginDocumentSettingPanel name="editorialMetadataPanel" title="Editorial Metadata">
		{ noEditorialMetadatasToShow && (
			<Text>Configure your editorial metadata, within the VIP Workflow Plugin Settings.</Text>
		) }
		{ editorialMetadatas.map( editorialMetadata =>
			getComponentByType( editorialMetadata, metaFields, setMetaFields )
		) }
	</PluginDocumentSettingPanel>
);

function getComponentByType( editorialMetadata, metaFields, setMetaFields ) {
	if ( editorialMetadata.type === 'checkbox' ) {
		return (
			<ToggleControl
				key={ editorialMetadata.key }
				help={ editorialMetadata.description }
				label={ editorialMetadata.label }
				checked={ metaFields?.[ editorialMetadata.key ] }
				onChange={ value =>
					setMetaFields( {
						...metaFields,
						[ editorialMetadata.key ]: value,
					} )
				}
			/>
		);
	}
	return (
		<TextControl
			key={ editorialMetadata.key }
			help={ editorialMetadata.description }
			label={ editorialMetadata.label }
			value={ metaFields?.[ editorialMetadata.key ] }
			className={ editorialMetadata.key }
			onChange={ value =>
				setMetaFields( {
					...metaFields,
					[ editorialMetadata.key ]: value,
				} )
			}
		/>
	);
}

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
