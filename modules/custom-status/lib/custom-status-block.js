import './editor.scss';

import { compose } from '@wordpress/compose';
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
	isUnsavedPost,
	isSavingPost,
	onUpdateStatus,
} ) => {
	const isCustomSaveButtonVisible = useMemo(
		() => isCustomSaveButtonEnabled( isUnsavedPost, postType, savedStatus ),
		[ isUnsavedPost, postType, savedStatus ]
	);

	const isCustomSaveButtonDisabled = isSavingPost;

	// Selectively disable the native save button when workflow statuses are in effect
	useEffect( () => {
		const editor = document.querySelector( '#editor' );

		if ( isCustomSaveButtonVisible ) {
			editor.classList.add( 'disable-native-save-button' );
		} else {
			editor.classList.remove( 'disable-native-save-button' );
		}
	}, [ isCustomSaveButtonVisible ] );

	const nextStatusTerm = useMemo( () => getNextStatusTerm( savedStatus ), [ savedStatus ] );

	useInterceptPluginSidebar(
		`${ pluginName }/${ sidebarName }`,
		( isSidebarActive, toggleSidebar ) => {
			if ( isCustomSaveButtonDisabled ) {
				return;
			}

			if ( nextStatusTerm ) {
				onUpdateStatus( nextStatusTerm.slug );
				dispatch( editorStore ).savePost();
			}
		}
	);

	const buttonText = getCustomSaveButtonText( nextStatusTerm );
	const InnerSaveButton = (
		<CustomInnerSaveButton
			buttonText={ buttonText }
			isSavingPost={ isSavingPost }
			isDisabled={ isCustomSaveButtonDisabled }
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
				<PluginSidebar name={ sidebarName } title={ buttonText } icon={ InnerSaveButton }>
					{ /* Use this space to show approve/reject UI or other sidebar controls */ }
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

const CustomInnerSaveButton = ( { buttonText, isSavingPost, isDisabled } ) => {
	const classNames = clsx( 'vip-workflow-save-button', {
		'is-busy': isSavingPost,
		'is-disabled': isDisabled,
	} );

	return <div className={ classNames }>{ buttonText }</div>;
};

// Utility methods

const isCustomSaveButtonEnabled = ( isUnsavedPost, postType, statusSlug ) => {
	if ( isUnsavedPost ) {
		// Show native "Save" for new posts
		return false;
	} else if ( ! VW_CUSTOM_STATUSES.is_publish_guard_enabled ) {
		return false;
	}

	const isSupportedPostType = VW_CUSTOM_STATUSES.supported_post_types.includes( postType );

	// Exclude the last custom status. Show the regular editor button on the last step.
	const allButLastStatusTerm = VW_CUSTOM_STATUSES.status_terms.slice( 0, -1 );
	const isSupportedStatusTerm = allButLastStatusTerm.map( t => t.slug ).includes( statusSlug );

	return isSupportedPostType && isSupportedStatusTerm;
};

const getCustomSaveButtonText = nextStatusTerm => {
	if ( nextStatusTerm ) {
		// translators: %s: Next custom status name, e.g. "Draft"
		return sprintf( __( 'Move to %s', 'vip-workflow' ), nextStatusTerm.name );
	}

	return __( 'Save', 'vip-workflow' );
};

const getNextStatusTerm = currentStatus => {
	const currentIndex = VW_CUSTOM_STATUSES.status_terms.findIndex( t => t.slug === currentStatus );

	if ( -1 === currentIndex || currentIndex === VW_CUSTOM_STATUSES.status_terms.length - 1 ) {
		return false;
	}

	return VW_CUSTOM_STATUSES.status_terms[ currentIndex + 1 ];
};
