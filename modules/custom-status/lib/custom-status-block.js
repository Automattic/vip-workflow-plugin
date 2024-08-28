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
	status,
	isUnsavedPost,
	isSavingPost,
	onUpdateStatus,
} ) => {
	const isCustomSaveButtonVisible = useMemo(
		() => isCustomSaveButtonEnabled( isUnsavedPost, postType, status ),
		[ isUnsavedPost, postType, status ]
	);

	// Selectively disable the native save button when workflow statuses are in effect
	useEffect( () => {
		const editor = document.querySelector( '#editor' );

		if ( isCustomSaveButtonVisible ) {
			editor.classList.add( 'disable-native-save-button' );
		} else {
			editor.classList.remove( 'disable-native-save-button' );
		}
	}, [ isCustomSaveButtonVisible ] );

	const nextStatusTerm = useMemo( () => getNextStatusTerm( status ), [ status ] );

	const handleButtonClick = ( isSidebarActive, toggleSidebar ) => {
		if ( nextStatusTerm ) {
			onUpdateStatus( nextStatusTerm.slug );
			dispatch( editorStore ).savePost();
		}
	};

	useInterceptPluginSidebar( `${ pluginName }/${ sidebarName }`, handleButtonClick );

	let buttonText;

	if ( isUnsavedPost ) {
		buttonText = __( 'Save', 'vip-workflow' );
	} else if ( nextStatusTerm ) {
		buttonText = sprintf( __( 'Move to %s', 'vip-workflow' ), nextStatusTerm.name );
	} else {
		buttonText = __( 'Publish 123 123', 'vip-workflow' );
	}

	const InnerSaveButton = (
		<CustomInnerSaveButton buttonText={ buttonText } isSavingPost={ isSavingPost } />
	);

	return (
		<>
			{ /* "Extended Post Status" in the sidebar */ }
			<CustomStatusSidebar
				postType={ postType }
				status={ status }
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

registerPlugin( pluginName, {
	render: compose(
		withSelect( mapSelectProps ),
		withDispatch( mapDispatchStatusToProps )
	)( CustomSaveButtonSidebar ),
} );

// Components

const CustomInnerSaveButton = ( { buttonText, isSavingPost } ) => {
	const classNames = clsx( 'vip-workflow-save-button', {
		'is-busy': isSavingPost,
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

const getNextStatusTerm = currentStatus => {
	const currentIndex = VW_CUSTOM_STATUSES.status_terms.findIndex( t => t.slug === currentStatus );

	if ( -1 === currentIndex || currentIndex === VW_CUSTOM_STATUSES.status_terms.length - 1 ) {
		return false;
	}

	return VW_CUSTOM_STATUSES.status_terms[ currentIndex + 1 ];
};
