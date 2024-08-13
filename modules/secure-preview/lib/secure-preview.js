import './editor.scss';

import apiFetch from '@wordpress/api-fetch';
import { __experimentalInspectorPopoverHeader as InspectorPopoverHeader } from '@wordpress/block-editor';
import {
	Button,
	Dropdown,
	ExternalLink,
	Spinner,
	__experimentalTruncate as Truncate,
} from '@wordpress/components';
import { useCopyToClipboard } from '@wordpress/compose';
import { dispatch } from '@wordpress/data';
import { PluginPostStatusInfo } from '@wordpress/edit-post';
import { useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { copySmall } from '@wordpress/icons';
import { store as noticesStore } from '@wordpress/notices';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Custom status component
 * @param object props
 */
const VIPWorkflowSecurePreview = () => {
	const [ securePreviewUrl, setSecurePreviewUrl ] = useState( null );

	return (
		<PluginPostStatusInfo className={ `vip-workflow-secure-preview` }>
			<div className="vip-workflow-secure-preview-row">
				<div className="vip-workflow-secure-preview-label">
					{ __( 'Secure Preview', 'vip-workflow' ) }
				</div>

				{ ! securePreviewUrl && (
					<GenerateSecurePreviewButton onUrl={ url => setSecurePreviewUrl( url ) } />
				) }

				{ securePreviewUrl && <SecurePreviewDropdown url={ securePreviewUrl } /> }
			</div>
		</PluginPostStatusInfo>
	);
};

const GenerateSecurePreviewButton = ( { onUrl } ) => {
	const [ isLoading, setIsLoading ] = useState( false );

	const handleGenerateSecureUrl = async () => {
		let result = {};

		try {
			setIsLoading( true );

			result = await apiFetch( {
				url: VW_SECURE_PREVIEW.url_generate_preview,
				method: 'POST',
			} );
		} catch ( error ) {
			const errorMessage = VW_SECURE_PREVIEW.text_preview_error + ' ' + error.message;

			dispatch( noticesStore ).createErrorNotice( errorMessage, {
				id: 'vw-secure-preview',
				isDismissible: true,
			} );
		} finally {
			setIsLoading( false );
		}

		if ( result?.url ) {
			onUrl( result.url );
		}
	};

	return (
		<>
			{ isLoading && <Spinner /> }
			{ ! isLoading && (
				<Button
					className="vip-workflow-secure-preview-button"
					variant="secondary"
					onClick={ handleGenerateSecureUrl }
				>
					{ __( 'Generate Link', 'vip-workflow' ) }
				</Button>
			) }
		</>
	);
};

const SecurePreviewDropdown = ( { url } ) => {
	const anchorRef = useRef( null );

	const clipboardRef = useCopyToClipboard( url, () => {
		dispatch( noticesStore ).createNotice( 'info', __( 'Link copied to clipboard.' ), {
			isDismissible: true,
			type: 'snackbar',
		} );
	} );
	const popoverProps = {
		anchorRef,
		placement: 'left-start',
		offset: 36,
		shift: true,
	};

	// Grab everything from the last '/' in the URL
	const urlTail = '/' + url.split( '/' ).pop();

	return (
		<div className="vip-workflow-secure-preview-dropdown" ref={ anchorRef }>
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

			<Button
				icon={ copySmall }
				label={ sprintf(
					// Translators: %s is a placeholder for the link URL, e.g. "Copy link: https://example.com".
					__( 'Copy link: %s' ),
					url
				) }
				ref={ clipboardRef }
				size="compact"
			/>
		</div>
	);
};

// Register secure preview row in the sidebar
registerPlugin( 'vip-workflow-secure-preview', {
	icon: 'vip-workflow',
	render: VIPWorkflowSecurePreview,
} );
