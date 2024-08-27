import './editor.scss';

import { Button, SelectControl } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { dispatch, select, subscribe, withDispatch, withSelect } from '@wordpress/data';
import { PluginPostStatusInfo, PluginSidebar } from '@wordpress/edit-post';
import { store as editorStore } from '@wordpress/editor';
import { useMemo, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import clsx from 'clsx';
import { useEffect } from 'react';

import useInterceptPluginSidebar from './hooks/use-intercept-plugin-sidebar';

/**
 * Map Custom Statuses as options for SelectControl
 */
const statusOptions = VW_CUSTOM_STATUSES.status_terms.map( customStatus => ( {
	label: customStatus.name,
	value: customStatus.slug,
} ) );

const isUsingWorkflowStatus = ( postType, statusSlug ) => {
	const isSupportedPostType = VW_CUSTOM_STATUSES.supported_post_types.includes( postType );
	const isSupportedStatusTerm = VW_CUSTOM_STATUSES.status_terms
		.map( t => t.slug )
		.includes( statusSlug );

	return isSupportedPostType && isSupportedStatusTerm;
};

/**
 * Subscribe to changes so we can set a default status.
 */
subscribe( function () {
	const postId = select( editorStore ).getCurrentPostId();
	if ( ! postId ) {
		// Post isn't ready yet so don't do anything.
		return;
	}

	// For new posts, we need to force the default custom status which is the first entry.
	const isCleanNewPost = select( editorStore ).isCleanNewPost();
	if ( isCleanNewPost ) {
		dispatch( editorStore ).editPost( {
			status: statusOptions[ 0 ].value,
		} );
	}

	// const selectedStatus = select( editorStore ).getEditedPostAttribute( 'status' );

	// // check if the post status is in the list of custom statuses, and only then issue the notices
	// if (
	// 	typeof vw_publish_guard_enabled !== 'undefined' &&
	// 	vw_publish_guard_enabled &&
	// 	statusOptions.find( status => status.value === selectedStatus )
	// ) {
	// 	const has_publish_capability =
	// 		select( editorStore ).getCurrentPost()?._links?.[ 'wp:action-publish' ] ?? false;

	// 	if ( postLocked && has_publish_capability ) {
	// 		postLocked = false;
	// 		dispatch( 'core/notices' ).removeNotice( 'publish-guard-lock' );
	// 	} else if ( ! postLocked && ! has_publish_capability ) {
	// 		postLocked = true;
	// 		dispatch( 'core/notices' ).createInfoNotice( __( 'This post is locked from publishing.' ), {
	// 			id: 'publish-guard-lock',
	// 			type: 'snackbar',
	// 		} );
	// 	}
	// }
} );

/**
 * Custom status component
 * @param object props
 */
const VIPWorkflowCustomPostStati = ( { onUpdate, postType, status } ) => {
	const [ isEditingStatus, setIsEditingStatus ] = useState( false );

	const customStatusName = VW_CUSTOM_STATUSES.status_terms.find( t => t.slug === status )?.name;

	const handleChangeStatus = statusSlug => {
		onUpdate( statusSlug );
		setIsEditingStatus( false );
	};

	if ( ! isUsingWorkflowStatus( postType, status ) ) {
		return null;
	}

	return (
		<PluginPostStatusInfo
			className={ `vip-workflow-extended-post-status vip-workflow-extended-post-status-${ status }` }
		>
			<h4>{ __( 'Extended Post Status', 'vip-workflow' ) }</h4>

			<div className="vip-workflow-extended-post-status-edit">
				{ ! isEditingStatus && (
					<>
						{ customStatusName }
						<Button size="compact" variant="link" onClick={ () => setIsEditingStatus( true ) }>
							{ __( 'Edit', 'vip-workflow' ) }
						</Button>
					</>
				) }

				{ isEditingStatus && (
					<SelectControl
						label=""
						value={ status }
						options={ statusOptions }
						onChange={ handleChangeStatus }
					/>
				) }
			</div>
		</PluginPostStatusInfo>
	);
};

const mapSelectToProps = select => {
	const { getEditedPostAttribute, getCurrentPostType } = select( editorStore );

	return {
		status: getEditedPostAttribute( 'status' ),
		postType: getCurrentPostType(),
	};
};

const mapDispatchToProps = dispatch => {
	return {
		onUpdate( status ) {
			dispatch( editorStore ).editPost( { status } );
		},
	};
};

const plugin = compose(
	withSelect( mapSelectToProps ),
	withDispatch( mapDispatchToProps )
)( VIPWorkflowCustomPostStati );

/**
 * Kick it off
 */
registerPlugin( 'vip-workflow-custom-status', {
	icon: 'vip-workflow',
	render: plugin,
} );

const isCustomSaveButtonEnabled = ( isUnsavedPost, postType, statusSlug ) => {
	if ( isUnsavedPost ) {
		// Show native "Save" for new posts
		return false;
	}

	const isSupportedPostType = VW_CUSTOM_STATUSES.supported_post_types.includes( postType );

	// Exclude the last custom status. Show the regular editor button on the last step.
	const allButLastStatusTerm = VW_CUSTOM_STATUSES.status_terms.slice( 0, -1 );
	const isSupportedStatusTerm = allButLastStatusTerm.map( t => t.slug ).includes( statusSlug );

	return isSupportedPostType && isSupportedStatusTerm;
};

const getNextStatusTerm = currentStatus => {
	const currentIndex = VW_CUSTOM_STATUSES.status_terms.findIndex( t => t.slug === currentStatus );

	if ( -1 === currentIndex || currentIndex === VW_CUSTOM_STATUSES.status_terms.length - 1 ) {
		return false;
	}

	return VW_CUSTOM_STATUSES.status_terms[ currentIndex + 1 ];
};

const CustomSaveButton = ( { buttonText, isSavingPost } ) => {
	const classNames = clsx( 'vip-workflow-save-button', {
		'is-busy': isSavingPost,
	} );

	return <div className={ classNames }>{ buttonText }</div>;
};

const saveButtonSidebar = 'vip-workflow-save-button-sidebar';

// Plugin sidebar
const CustomSaveButtonSidebar = ( {
	postType,
	status,
	isUnsavedPost,
	isSavingPost,
	onUpdateStatus,
} ) => {
	const isShowingCustomSaveButton = useMemo(
		() => isCustomSaveButtonEnabled( isUnsavedPost, postType, status ),
		[ isUnsavedPost, postType, status ]
	);

	useEffect( () => {
		// Selectively disable the native save button when workflow statuses are being used
		const editor = document.querySelector( '#editor' );

		if ( isShowingCustomSaveButton ) {
			editor.classList.add( 'disable-native-save-button' );
		} else {
			editor.classList.remove( 'disable-native-save-button' );
		}
	}, [ isShowingCustomSaveButton ] );

	const nextStatusTerm = useMemo( () => getNextStatusTerm( status ), [ status ] );
	const handleButtonClick = ( isSidebarActive, toggleSidebar ) => {
		if ( nextStatusTerm ) {
			onUpdateStatus( nextStatusTerm.slug );
			dispatch( editorStore ).savePost();
		}

		// toggleSidebar();
	};

	useInterceptPluginSidebar( `${ saveButtonPlugin }/${ saveButtonSidebar }`, handleButtonClick );

	let buttonText;

	if ( isUnsavedPost ) {
		buttonText = __( 'Save', 'vip-workflow' );
	} else if ( nextStatusTerm ) {
		buttonText = sprintf( __( 'Move to %s', 'vip-workflow' ), nextStatusTerm.name );
	} else {
		buttonText = __( 'Publish 123 123', 'vip-workflow' );
	}

	if ( ! isShowingCustomSaveButton ) {
		return null;
	}

	const SaveButton = <CustomSaveButton buttonText={ buttonText } isSavingPost={ isSavingPost } />;

	return (
		<PluginSidebar
			name={ saveButtonSidebar }
			title={ buttonText }
			className={ 'custom-class-name' }
			icon={ SaveButton }
		>
			{ /* Use this space to show approve/reject UI or other sidebar controls */ }
			{ null }
		</PluginSidebar>
	);
};

const mapSelectProps = select => {
	const { getEditedPostAttribute, isSavingPost, getCurrentPost, getCurrentPostType } =
		select( editorStore );

	const post = getCurrentPost();

	// Brand-new unsaved posts have the 'auto-draft' status.
	const isUnsavedPost = post?.status === 'auto-draft';

	return {
		postType: getCurrentPostType(),
		status: getEditedPostAttribute( 'status' ),
		isSavingPost: isSavingPost(),
		isUnsavedPost,
	};
};

const mapDispatchStatusToProps = dispatch => {
	return {
		onUpdateStatus( status ) {
			dispatch( editorStore ).editPost( { status } );
		},
	};
};

const saveButtonPlugin = 'vip-workflow-save-button-plugin';

registerPlugin( saveButtonPlugin, {
	render: compose(
		withSelect( mapSelectProps ),
		withDispatch( mapDispatchStatusToProps )
	)( CustomSaveButtonSidebar ),
} );
