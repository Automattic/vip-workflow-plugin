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
import { compose, useCopyToClipboard } from '@wordpress/compose';
import { dispatch, withSelect } from '@wordpress/data';
import { PluginPostStatusInfo } from '@wordpress/edit-post';
import { store as editorStore } from '@wordpress/editor';
import { useMemo, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { copySmall } from '@wordpress/icons';
import { store as noticesStore } from '@wordpress/notices';
import { registerPlugin } from '@wordpress/plugins';

import CopyFromAsyncButton from './components/copy-from-async-button';

/**
 * Custom component to generate and copy a preview link in the post sidebar.
 */
const VIPWorkflowPreview = ( { status, postType, isUnsavedPost } ) => {
	const [ isModalVisible, setIsModalVisible ] = useState( false );
	const [ previewUrl, setPreviewUrl ] = useState( null );

	const isPreviewAvailable = useMemo( () => {
		return (
			VW_PREVIEW.custom_status_slugs.includes( status ) &&
			VW_PREVIEW.custom_post_types.includes( postType ) &&
			! isUnsavedPost
		);
	}, [ status, postType, isUnsavedPost ] );

	const openModal = () => {
		setIsModalVisible( true );
	};

	return (
		<>
			{ isPreviewAvailable && (
				<PluginPostStatusInfo className={ `vip-workflow-preview` }>
					<div className="vip-workflow-preview-row">
						<div className="vip-workflow-preview-label">{ __( 'Preview', 'vip-workflow' ) }</div>

						{ ! previewUrl && (
							<Button
								className="vip-workflow-preview-button"
								variant="tertiary"
								size="compact"
								onClick={ openModal }
							>
								{ __( 'Generate Link', 'vip-workflow' ) }
							</Button>
						) }

						{ isModalVisible && (
							<PreviewModal
								onUrl={ setPreviewUrl }
								onCloseModal={ () => setIsModalVisible( false ) }
							/>
						) }

						{ previewUrl && <PreviewDropdown url={ previewUrl } /> }
					</div>
				</PluginPostStatusInfo>
			) }
		</>
	);
};

const PreviewModal = ( { onUrl, onCloseModal } ) => {
	const [ isLoading, setIsLoading ] = useState( false );
	const [ isOneTimeUse, setIsOneTimeUse ] = useState( false );

	const expirationOptions = VW_PREVIEW.expiration_options;
	const defaultOption =
		expirationOptions.find( option => option.default === true )?.value ||
		expirationOptions?.[ 0 ]?.value;

	const [ expiration, setExpiration ] = useState( defaultOption );

	const getPreviewUrl = async () => {
		let result = {};

		try {
			setIsLoading( true );

			result = await apiFetch( {
				url: VW_PREVIEW.url_generate_preview,
				method: 'POST',
				data: { expiration, is_one_time_use: isOneTimeUse },
			} );
		} catch ( error ) {
			const errorMessage = VW_PREVIEW.text_preview_error + ' ' + error.message;

			dispatch( noticesStore ).createErrorNotice( errorMessage, {
				id: 'vw-preview',
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
		<Modal title="Generate preview link" size="medium" onRequestClose={ onCloseModal }>
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

				<CopyFromAsyncButton
					variant="primary"
					asyncFunction={ getPreviewUrl }
					onCopied={ handleUrlCopied }
				>
					{ __( 'Copy Link', 'vip-workflow' ) }
				</CopyFromAsyncButton>
			</Flex>
		</Modal>
	);
};

const PreviewDropdown = ( { url } ) => {
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
		<div className="vip-workflow-preview-dropdown" ref={ anchorRef }>
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
					<div className="vip-workflow-preview-dropdown-content">
						<InspectorPopoverHeader title={ __( 'Preview Link' ) } onClose={ onClose } />
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
	const { getEditedPostAttribute, getCurrentPostType, getCurrentPost } = select( editorStore );

	const post = getCurrentPost();

	// Brand-new unsaved posts have the 'auto-draft' status. We can not generate preview links for unsaved posts.
	const isUnsavedPost = post?.status === 'auto-draft';

	return {
		status: getEditedPostAttribute( 'status' ),
		postType: getCurrentPostType(),
		isUnsavedPost,
	};
};

const plugin = compose( withSelect( getPostProperties ) )( VIPWorkflowPreview );

// Register preview row in the sidebar
registerPlugin( 'vip-workflow-preview', {
	icon: 'vip-workflow',
	render: plugin,
} );
