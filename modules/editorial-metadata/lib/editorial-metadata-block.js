import { PanelRow, TextControl } from '@wordpress/components';
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

function CustomMetaPanel() {
	return (
		<PluginDocumentSettingPanel name="editorialMetadataPanel" title="Editorial Metadata">
			<PanelRow>
				{ editorialMetadatas.map( editorialMetadata => (
					<TextControl
						key={ editorialMetadata.key }
						label={ editorialMetadata.label }
						value={ editorialMetadata.type }
						className={ editorialMetadata.key }
					/>
				) ) }
			</PanelRow>
		</PluginDocumentSettingPanel>
	);
}

registerPlugin( 'vip-workflow-editorial-metadata', {
	render: CustomMetaPanel,
	icon: 'vip-workflow',
} );
