import { PanelBody, TextControl } from '@wordpress/components';
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

const CustomMetaPanel = ( { metaFields, setMetaFields } ) => (
	<PluginDocumentSettingPanel name="editorialMetadataPanel" title="Editorial Metadata">
		<PanelBody>
			{ editorialMetadatas
				.filter( editorialMetadata => editorialMetadata.type === 'text' )
				.map( editorialMetadata => (
					<TextControl
						key={ editorialMetadata.key }
						label={ editorialMetadata.label }
						className={ editorialMetadata.key }
						onChange={ value =>
							setMetaFields( {
								...metaFields,
								[ editorialMetadata.key ]: value,
							} )
						}
					/>
				) ) }
		</PanelBody>
	</PluginDocumentSettingPanel>
);

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
