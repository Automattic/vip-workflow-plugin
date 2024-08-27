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

import VIPWorkflowCustomStatusSidebar from './components/custom-status-sidebar';
import useInterceptPluginSidebar from './hooks/use-intercept-plugin-sidebar';

const vipWorkflowPluginName = 'vip-workflow-custom-status';
const vipWorkflowSidebarName = 'vip-workflow-sidebar';

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

const CustomInnerSaveButton = ( { buttonText, isSavingPost } ) => {
	const classNames = clsx( 'vip-workflow-save-button', {
		'is-busy': isSavingPost,
	} );

	return <div className={ classNames }>{ buttonText }</div>;
};

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
	};

	useInterceptPluginSidebar(
		`${ vipWorkflowPluginName }/${ vipWorkflowSidebarName }`,
		handleButtonClick
	);

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

	const SaveButton = (
		<CustomInnerSaveButton buttonText={ buttonText } isSavingPost={ isSavingPost } />
	);

	return (
		<>
			<VIPWorkflowCustomStatusSidebar
				postType={ postType }
				status={ status }
				onUpdateStatus={ onUpdateStatus }
			/>

			<PluginSidebar
				name={ vipWorkflowSidebarName }
				title={ buttonText }
				className={ 'custom-class-name' }
				icon={ SaveButton }
			>
				{ /* Use this space to show approve/reject UI or other sidebar controls */ }
				{ null }
			</PluginSidebar>
		</>
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

registerPlugin( vipWorkflowPluginName, {
	render: compose(
		withSelect( mapSelectProps ),
		withDispatch( mapDispatchStatusToProps )
	)( CustomSaveButtonSidebar ),
} );
