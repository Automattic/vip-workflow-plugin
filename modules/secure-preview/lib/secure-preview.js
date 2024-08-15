import './editor.scss';

import apiFetch from '@wordpress/api-fetch';
import { __experimentalInspectorPopoverHeader as InspectorPopoverHeader } from '@wordpress/block-editor';
import {
	Button,
	Dropdown,
	ExternalLink,
	Modal,
	SelectControl,
	Spinner,
	__experimentalTruncate as Truncate,
	Flex,
	CheckboxControl,
} from '@wordpress/components';
import { compose, useCopyToClipboard, useMergeRefs } from '@wordpress/compose';
import { dispatch, withSelect } from '@wordpress/data';
import { PluginPostStatusInfo } from '@wordpress/edit-post';
import { store as editorStore } from '@wordpress/editor';
import { useLayoutEffect, useMemo, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { copySmall } from '@wordpress/icons';
import { store as noticesStore } from '@wordpress/notices';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Custom component to generate and copy a secure preview link in the post sidebar.
 */
const VIPWorkflowSecurePreview = ( { status, postType } ) => {
	const [ isModalVisible, setIsModalVisible ] = useState( false );
	const [ securePreviewUrl, setSecurePreviewUrl ] = useState( null );

	const isSecurePreviewAvailable = useMemo( () => {
		return (
			VW_SECURE_PREVIEW.custom_status_slugs.includes( status ) &&
			VW_SECURE_PREVIEW.custom_post_types.includes( postType )
		);
	}, [ status, postType ] );

	const openModal = () => {
		setIsModalVisible( true );
	};

	return (
		<>
			{ isSecurePreviewAvailable && (
				<PluginPostStatusInfo className={ `vip-workflow-secure-preview` }>
					<div className="vip-workflow-secure-preview-row">
						<div className="vip-workflow-secure-preview-label">
							{ __( 'Secure Preview', 'vip-workflow' ) }
						</div>

						{ ! securePreviewUrl && (
							<Button
								className="vip-workflow-secure-preview-button"
								variant="tertiary"
								onClick={ openModal }
							>
								{ __( 'Generate Link', 'vip-workflow' ) }
							</Button>
						) }

						{ isModalVisible && (
							<SecurePreviewModal
								onUrl={ setSecurePreviewUrl }
								onCloseModal={ () => setIsModalVisible( false ) }
							/>
						) }

						{ securePreviewUrl && <SecurePreviewDropdown url={ securePreviewUrl } /> }
					</div>
				</PluginPostStatusInfo>
			) }
		</>
	);
};

const SecurePreviewModal = ( { onUrl, onCloseModal } ) => {
	const [ isLoading, setIsLoading ] = useState( false );
	const [ isOneTimeUse, setIsOneTimeUse ] = useState( false );

	const expirationOptions = VW_SECURE_PREVIEW.expiration_options;
	const defaultOption =
		expirationOptions.find( option => option.default === true )?.value ||
		expirationOptions?.[ 0 ]?.value;

	const [ expiration, setExpiration ] = useState( defaultOption );

	const getSecureUrl = async () => {
		let result = {};

		try {
			setIsLoading( true );

			result = await apiFetch( {
				url: VW_SECURE_PREVIEW.url_generate_preview,
				method: 'POST',
				data: { expiration, is_one_time_use: isOneTimeUse },
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
			return result.url;
		}
	};

	const handleUrlCopied = url => {
		dispatch( noticesStore ).createNotice( 'info', __( 'Link copied to clipboard.' ), {
			isDismissible: true,
			type: 'snackbar',
		} );

		onCloseModal();
		onUrl( url );
	};

	const selectOptions = expirationOptions.map( ( { label, value } ) => {
		return { label, value };
	} );

	return (
		<Modal title="Generate secure preview" size="medium" onRequestClose={ onCloseModal }>
			<SelectControl
				label={ __( 'Link expiration', 'vip-workflow' ) }
				value={ expiration }
				onChange={ setExpiration }
				options={ selectOptions }
			/>

			<CheckboxControl
				className="vip-workflow-one-time-use-checkbox"
				label={ __( 'One-time use', 'vip-workflow' ) }
				help={ __( 'The link will expire after one visit.', 'vip-workflow' ) }
				checked={ isOneTimeUse }
				onChange={ () => setIsOneTimeUse( isOneTimeUse => ! isOneTimeUse ) }
			/>

			<Flex justify="flex-end">
				{ isLoading && <Spinner /> }

				<CopyFromAsyncFunctionButton
					variant="primary"
					asyncFunction={ getSecureUrl }
					onCopied={ handleUrlCopied }
				>
					{ __( 'Copy Link', 'vip-workflow' ) }
				</CopyFromAsyncFunctionButton>
			</Flex>
		</Modal>
	);
};

const CopyFromAsyncFunctionButton = props => {
	const { asyncFunction, onCopied, children, ...buttonProps } = props;

	const [ textToCopy, setTextToCopy ] = useState( null );

	const copyButtonRef = useRef( null );
	const copyToClipboardRef = useCopyToClipboard( textToCopy, () => {
		onCopied( textToCopy );
	} );

	useLayoutEffect( () => {
		if ( textToCopy ) {
			copyButtonRef.current?.click();
		}
	}, [ textToCopy ] );

	const handleCopyClick = async () => {
		const text = await asyncFunction();
		setTextToCopy( text );
	};

	return (
		<>
			{ /* This invisible button holds the text to be copied */ }
			<Button
				style={ { display: 'none' } }
				ref={ useMergeRefs( [ copyButtonRef, copyToClipboardRef ] ) }
			></Button>

			{ /* This is the visible button */ }
			<Button { ...buttonProps } onClick={ handleCopyClick }>
				{ children }
			</Button>
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
					// Translators: %s is a placeholder for the link URL, e.g. "Copy link: https://my-site.com/?p=123".
					__( 'Copy link: %s' ),
					url
				) }
				ref={ clipboardRef }
				size="compact"
			/>
		</div>
	);
};

const getPostProperties = select => {
	return {
		status: select( editorStore ).getEditedPostAttribute( 'status' ),
		postType: select( editorStore ).getCurrentPostType(),
	};
};

const plugin = compose( withSelect( getPostProperties ) )( VIPWorkflowSecurePreview );

// Register secure preview row in the sidebar
registerPlugin( 'vip-workflow-secure-preview', {
	icon: 'vip-workflow',
	render: plugin,
} );
