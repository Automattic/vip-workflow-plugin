import './editor.scss';

import { Button } from '@wordpress/components';
import { PluginPostStatusInfo } from '@wordpress/edit-post';
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Custom status component
 * @param object props
 */
const VIPWorkflowSecurePreview = () => (
	<PluginPostStatusInfo className={ `vip-workflow-secure-preview` }>
		<div className="vip-workflow-secure-preview-row">
			<div className="vip-workflow-secure-preview-label">
				{ __( 'Secure Preview', 'vip-workflow' ) }
			</div>

			<Button className="vip-workflow-secure-preview-button" variant="secondary">
				{ __( 'Generate Link', 'vip-workflow' ) }
			</Button>
		</div>
	</PluginPostStatusInfo>
);

/**
 * Kick it off
 */
registerPlugin( 'vip-workflow-secure-preview', {
	icon: 'vip-workflow',
	render: VIPWorkflowSecurePreview,
} );
