import './editor.scss';

import { compose, useViewportMatch } from '@wordpress/compose';
import { dispatch, withDispatch, withSelect } from '@wordpress/data';
import { PluginSidebar } from '@wordpress/edit-post';
import { store as editorStore } from '@wordpress/editor';
import { useMemo } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import clsx from 'clsx';
import { useEffect } from 'react';

import CustomStatusSidebar from './components/custom-status-sidebar';
import useInterceptPluginSidebar from './hooks/use-intercept-plugin-sidebar';

const pluginName = 'vip-workflow-custom-status';
const sidebarName = 'vip-workflow-sidebar';

// Plugin sidebar
const CustomSaveButtonSidebar = ( {
	postType,
	savedStatus,
	editedStatus,
	metaFields,
	isUnsavedPost,
	isSavingPost,
	onUpdateStatus,
} ) => {
	const isTinyViewport = useViewportMatch( 'small', '<' );
	const isWideViewport = useViewportMatch( 'wide', '>=' );

	const isRestrictedStatus = useMemo( () => {
		const currentStatusTerm = getCurrentStatusTerm( savedStatus );
		return isStatusRestrictedFromMovement( currentStatusTerm, metaFields );
	}, [ savedStatus, metaFields ] );

	const isCustomSaveButtonVisible = useMemo(
		() => isCustomSaveButtonEnabled( isUnsavedPost, postType, savedStatus, isRestrictedStatus ),
		[ isUnsavedPost, postType, savedStatus, isRestrictedStatus ]
	);

	// Selectively remove the native save button when publish guard and workflow statuses are in use
	useEffect( () => {
		if ( VW_CUSTOM_STATUSES.is_publish_guard_enabled ) {
			const editor = document.querySelector( '#editor' );

			// The editor class may not exist in some contexts, e.g. the site editor
			// This disables this whole functionality, so it doesn't break anything.
			if ( ! editor ) {
				return;
			}

			if ( isCustomSaveButtonVisible ) {
				editor.classList.add( 'disable-native-save-button' );
			} else {
				editor.classList.remove( 'disable-native-save-button' );
			}
		} else {
			// Allow both buttons to coexist when publish guard is disabled
		}
	}, [ isCustomSaveButtonVisible ] );

	const nextStatusTerm = useMemo( () => getNextStatusTerm( savedStatus ), [ savedStatus ] );
	const isCustomSaveButtonDisabled = isSavingPost || isRestrictedStatus;

	useInterceptPluginSidebar(
		`${ pluginName }/${ sidebarName }`,
		( _isSidebarActive, _toggleSidebar ) => {
			if ( isCustomSaveButtonDisabled ) {
				return;
			}

			if ( nextStatusTerm ) {
				onUpdateStatus( nextStatusTerm.slug );
				dispatch( editorStore ).savePost();
			}
		}
	);

	const buttonText = getCustomSaveButtonText( nextStatusTerm, isRestrictedStatus, isWideViewport );
	const tooltipText = isRestrictedStatus
		? __( 'Requirements for advancing have not been met.', 'vip-workflow' )
		: buttonText;

	const InnerSaveButton = (
		<CustomInnerSaveButton
			buttonText={ buttonText }
			isSavingPost={ isSavingPost }
			isDisabled={ isCustomSaveButtonDisabled }
			isTinyViewport={ isTinyViewport }
		/>
	);

	return (
		<>
			{ /* "Extended Post Status" in the sidebar */ }
			<CustomStatusSidebar
				postType={ postType }
				status={ editedStatus }
				onUpdateStatus={ onUpdateStatus }
			/>

			{ /* Custom save button in the toolbar */ }
			{ isCustomSaveButtonVisible && (
				<PluginSidebar name={ sidebarName } title={ tooltipText } icon={ InnerSaveButton }>
					{ /* ToDo: Use this space to show approve/reject UI or other sidebar controls */ }
					{ null }
				</PluginSidebar>
			) }
		</>
	);
};

const mapSelectProps = select => {
	const {
		getEditedPostAttribute,
		getCurrentPostAttribute,
		isSavingPost,
		getCurrentPost,
		getCurrentPostType,
	} = select( editorStore );

	const post = getCurrentPost();

	// Brand-new unsaved posts have the 'auto-draft' status.
	const isUnsavedPost = post?.status === 'auto-draft';

	return {
		// The status from the last saved post. Updates when a post has been successfully saved in the backend.
		savedStatus: getCurrentPostAttribute( 'status' ),

		// The status from the current post in the editor. Changes immediately when editPost() is dispatched in the UI,
		// before the post is updated in the backend.
		editedStatus: getEditedPostAttribute( 'status' ),

		metaFields: getEditedPostAttribute( 'meta' ),

		postType: getCurrentPostType(),
		isSavingPost: isSavingPost(),
		isUnsavedPost,
	};
};

const mapDispatchStatusToProps = dispatch => {
	return {
		onUpdateStatus( status ) {
			const editPostOptions = {
				// When we change post status, don't add this change to the undo stack.
				// We don't want ctrl-z or the undo button in toolbar to rollback a post status change.
				undoIgnore: true,
			};

			dispatch( editorStore ).editPost( { status }, editPostOptions );
		},
	};
};

registerPlugin( pluginName, {
	render: compose(
		withSelect( mapSelectProps ),
		withDispatch( mapDispatchStatusToProps )
	)( CustomSaveButtonSidebar ),
} );

// Components

const CustomInnerSaveButton = ( { buttonText, isSavingPost, isDisabled, isTinyViewport } ) => {
	const classNames = clsx( 'vip-workflow-save-button', {
		'is-busy': isSavingPost,
		'is-disabled': isDisabled,
		'is-tiny': isTinyViewport,
	} );

	return <div className={ classNames }>{ buttonText }</div>;
};

// Utility methods

const isCustomSaveButtonEnabled = ( isUnsavedPost, postType, statusSlug, isRestrictedStatus ) => {
	if ( isUnsavedPost ) {
		// Show native "Save" for new posts
		return false;
	}

	const isSupportedPostType = VW_CUSTOM_STATUSES.supported_post_types.includes( postType );

	// Exclude the last custom status. Show the regular editor button on the last step.
	const allButLastStatusTerm = VW_CUSTOM_STATUSES.status_terms.slice( 0, -1 );
	const isSupportedStatusTerm = allButLastStatusTerm.map( t => t.slug ).includes( statusSlug );

	return isSupportedPostType && ( isSupportedStatusTerm || isRestrictedStatus );
};

const getCustomSaveButtonText = ( nextStatusTerm, isRestrictedStatus, isWideViewport ) => {
	let buttonText = __( 'Save', 'vip-workflow' );

	if ( nextStatusTerm ) {
		const nextStatusName = nextStatusTerm.name;

		if ( isWideViewport ) {
			// translators: %s: Next custom status name, e.g. "Draft"
			buttonText = sprintf( __( 'Move to %s', 'vip-workflow' ), nextStatusName );
		} else {
			const truncatedStatus = truncateText( nextStatusName, 7 );

			// translators: %s: Next custom status name, possibly truncated with an ellipsis. e.g. "Draft" or "Pendi…"
			buttonText = sprintf( __( 'Move to %s', 'vip-workflow' ), truncatedStatus );
		}
	} else if ( ! nextStatusTerm && isRestrictedStatus ) {
		// Awaiting a privileged user to approve publishing.
		// Show disabled "Publish" button as a placeholder.
		buttonText = __( 'Publish', 'vip-workflow' );
	}

	return buttonText;
};

const isStatusRestrictedFromMovement = ( status, metaFields ) => {
	let isStatusRestricted = false;

	if ( status?.meta?.required_user_ids && status.meta.required_user_ids.length > 0 ) {
		const requiredUserIds = status.meta.required_user_ids;
		const currentUserId = parseInt( VW_CUSTOM_STATUSES.current_user_id, /* radix */ 10 );
		isStatusRestricted = ! requiredUserIds.includes( currentUserId );
	}

	if (
		! isStatusRestricted &&
		status?.meta?.required_metadatas &&
		status.meta.required_metadatas.length > 0
	) {
		const requiredMetadas = status.meta.required_metadatas;
		const hasMissingMetaFields = requiredMetadas.some( field => {
			const postmeta_key = field.meta.postmeta_key;

			// Insert the rules engine check here, once that's implemented. Right now, it's just the default values for a boolean and a text field.
			if ( field.meta.type === 'checkbox' ) {
				return ! metaFields[ postmeta_key ];
			} else {
				return ! metaFields[ postmeta_key ] || metaFields[ postmeta_key ].length === 0;
			}
		} );
		isStatusRestricted = hasMissingMetaFields;
	}

	return isStatusRestricted;
};

const getCurrentStatusTerm = currentStatus => {
	const statusTerm = VW_CUSTOM_STATUSES.status_terms.find( term => term.slug === currentStatus );
	return statusTerm ? statusTerm : false;
};

const getNextStatusTerm = currentStatus => {
	const currentIndex = VW_CUSTOM_STATUSES.status_terms.findIndex(
		term => term.slug === currentStatus
	);

	if ( -1 === currentIndex || currentIndex === VW_CUSTOM_STATUSES.status_terms.length - 1 ) {
		return false;
	}

	return VW_CUSTOM_STATUSES.status_terms[ currentIndex + 1 ];
};

const truncateText = ( text, length ) => {
	if ( text.length > length ) {
		return text.slice( 0, length ) + '…';
	}
	return text;
};
