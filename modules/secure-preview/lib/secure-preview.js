import './editor.scss';

import apiFetch from '@wordpress/api-fetch';
import { __experimentalInspectorPopoverHeader as InspectorPopoverHeader } from '@wordpress/block-editor';
import {
	Button,
	Dropdown,
	ExternalLink,
	__experimentalTruncate as Truncate,
} from '@wordpress/components';
import { dispatch } from '@wordpress/data';
import { PluginPostStatusInfo } from '@wordpress/edit-post';
import { useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Custom status component
 * @param object props
 */
const VIPWorkflowSecurePreview = () => {
	const [ securePreviewUrl, setSecurePreviewUrl ] = useState( null );

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
			} );
		}

		setSecurePreviewUrl( result.url );
	};

	return (
		<PluginPostStatusInfo className={ `vip-workflow-secure-preview` }>
			<div className="vip-workflow-secure-preview-row">
				<div className="vip-workflow-secure-preview-label">
					{ __( 'Secure Preview', 'vip-workflow' ) }
				</div>

				{ ! securePreviewUrl && (
					<Button
						className="vip-workflow-secure-preview-button"
						variant="secondary"
						onClick={ handleGenerateSecureUrl }
					>
						{ __( 'Generate Link', 'vip-workflow' ) }
					</Button>
				) }

				{ securePreviewUrl && <SecurePreviewDropdown url={ securePreviewUrl } /> }
			</div>
		</PluginPostStatusInfo>
	);
};

const SecurePreviewDropdown = ( { url } ) => {
	const anchorRef = useRef( null );

	const popoverProps = {
		anchorRef,
		placement: 'left-start',
		offset: 36,
		shift: true,
	};

	// Grab everything from the last '/' in the URL
	const urlTail = '/' + url.split( '/' ).pop();

	return (
		<div ref={ anchorRef }>
			<Dropdown
				popoverProps={ popoverProps }
				focusOnMount
				renderToggle={ ( { onToggle } ) => (
					<Button size="compact" variant="tertiary" onClick={ onToggle }>
						<Truncate limit={ 15 } ellipsizeMode="tail">
							{ urlTail }
						</Truncate>
					</Button>
				) }
				renderContent={ ( { onClose } ) => (
					<div className="vip-workflow-secure-link-dropdown-content">
						<InspectorPopoverHeader title={ __( 'Secure Preview Link' ) } onClose={ onClose } />
						<ExternalLink className="editor-post-url__link" href={ url } target="_blank">
							{ url }
						</ExternalLink>
					</div>
				) }
			/>
		</div>
	);
};

/**
 * Kick it off
 */
registerPlugin( 'vip-workflow-secure-preview', {
	icon: 'vip-workflow',
	render: VIPWorkflowSecurePreview,
} );
