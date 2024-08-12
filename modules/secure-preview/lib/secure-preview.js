import './editor.scss';

import apiFetch from '@wordpress/api-fetch';
import { Button } from '@wordpress/components';
import { dispatch } from '@wordpress/data';
import { PluginPostStatusInfo } from '@wordpress/edit-post';
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';

const handleGenerateSecureUrl = async () => {
	let result;

	try {
		result = await apiFetch( {
			url: VW_SECURE_PREVIEW.url_generate_preview,
			method: 'POST',
		} );
	} catch ( error ) {
		const errorMessage = VW_SECURE_PREVIEW.text_preview_error + ' ' + error.message;

		dispatch( 'core/notices' ).createErrorNotice( errorMessage, {
			id: 'vw-secure-preview',
			isDismissible: true,
			type: 'snackbar',
		} );
		return;
	}

	console.log( 'result:', result );
};

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

			<Button
				className="vip-workflow-secure-preview-button"
				variant="secondary"
				onClick={ handleGenerateSecureUrl }
			>
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
