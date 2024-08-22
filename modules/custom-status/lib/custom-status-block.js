import './editor.scss';

import { SelectControl } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { dispatch, select, subscribe, withDispatch, withSelect } from '@wordpress/data';
import { PluginPostStatusInfo, PluginSidebar } from '@wordpress/edit-post';
import { store as editorStore } from '@wordpress/editor';
import { useMemo } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import clsx from 'clsx';

import useInterceptPluginSidebar from './hooks/use-intercept-plugin-sidebar';

/**
 * Map Custom Statuses as options for SelectControl
 */
const statusOptions = VW_CUSTOM_STATUSES.status_terms.map( customStatus => ( {
	label: customStatus.name,
	value: customStatus.slug,
} ) );

// This is necessary to prevent a call stack exceeded problem within Gutenberg, as our code is called several times for some reason.
let postLocked = false;

/**
 * Subscribe to changes so we can set a default status.
 */
subscribe( function () {
	const postId = select( 'core/editor' ).getCurrentPostId();
	if ( ! postId ) {
		// Post isn't ready yet so don't do anything.
		return;
	}

	// For new posts, we need to force the default custom status which is the first entry.
	const isCleanNewPost = select( 'core/editor' ).isCleanNewPost();
	if ( isCleanNewPost ) {
		dispatch( 'core/editor' ).editPost( {
			status: statusOptions[ 0 ].value,
		} );
	}

	const selectedStatus = select( 'core/editor' ).getEditedPostAttribute( 'status' );

	// check if the post status is in the list of custom statuses, and only then issue the notices
	if (
		typeof vw_publish_guard_enabled !== 'undefined' &&
		vw_publish_guard_enabled &&
		statusOptions.find( status => status.value === selectedStatus )
	) {
		const has_publish_capability =
			select( 'core/editor' ).getCurrentPost()?._links?.[ 'wp:action-publish' ] ?? false;

		if ( postLocked && has_publish_capability ) {
			postLocked = false;
			dispatch( 'core/notices' ).removeNotice( 'publish-guard-lock' );
		} else if ( ! postLocked && ! has_publish_capability ) {
			postLocked = true;
			dispatch( 'core/notices' ).createInfoNotice( __( 'This post is locked from publishing.' ), {
				id: 'publish-guard-lock',
				type: 'snackbar',
			} );
		}
	}
} );

/**
 * Custom status component
 * @param object props
 */
const VIPWorkflowCustomPostStati = ( { onUpdate, status } ) => (
	<PluginPostStatusInfo
		className={ `vip-workflow-extended-post-status vip-workflow-extended-post-status-${ status }` }
	>
		<h4>
			{ status !== 'publish'
				? __( 'Extended Post Status', 'vip-workflow' )
				: __( 'Extended Post Status Disabled.', 'vip-workflow' ) }
		</h4>

		{ status !== 'publish' ? (
			<SelectControl label="" value={ status } options={ statusOptions } onChange={ onUpdate } />
		) : null }

		<small className="vip-workflow-extended-post-status-note">
			{ status !== 'publish'
				? __( 'Note: this will override all status settings above.', 'vip-workflow' )
				: __( 'To select a custom status, please unpublish the content first.', 'vip-workflow' ) }
		</small>
	</PluginPostStatusInfo>
);

const mapSelectToProps = select => {
	return {
		status: select( 'core/editor' ).getEditedPostAttribute( 'status' ),
	};
};

const mapDispatchToProps = dispatch => {
	return {
		onUpdate( status ) {
			dispatch( 'core/editor' ).editPost( { status } );
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
const CustomSaveButtonSidebar = ( { status, isUnsavedPost, isSavingPost, onStatusChange } ) => {
	const isCleanNewPost = select( 'core/editor' ).isCleanNewPost();
	console.log( 'isCleanNewPost:', isCleanNewPost );

	const handleButtonClick = ( isSidebarActive, toggleSidebar ) => {
		console.log( 'Custom save button clicked! isSidebarActive' );
	};

	useInterceptPluginSidebar( `${ saveButtonPlugin }/${ saveButtonSidebar }`, handleButtonClick );

	const extraProps = {
		closeLabel: 'Close it!',
	};

	const nextStatusTerm = useMemo( () => getNextStatusTerm( status ), [ status ] );

	let buttonText;

	if ( isUnsavedPost ) {
		buttonText = __( 'Save', 'vip-workflow' );
	} else if ( nextStatusTerm ) {
		buttonText = sprintf( __( 'Move to %s', 'vip-workflow' ), nextStatusTerm.name );
	} else {
		buttonText = __( 'Save', 'vip-workflow' );
	}

	const SaveButton = <CustomSaveButton buttonText={ buttonText } isSavingPost={ isSavingPost } />;

	return (
		<PluginSidebar
			name={ saveButtonSidebar }
			title={ buttonText }
			className={ 'custom-class-name' }
			icon={ SaveButton }
			{ ...extraProps }
		>
			{ /* Sidebar contents */ }
			{ null }
		</PluginSidebar>
	);
};

const mapSelectProps = select => {
	const { getEditedPostAttribute, isSavingPost, getCurrentPost } = select( editorStore );

	const post = getCurrentPost();

	// Brand-new unsaved posts have the 'auto-draft' status.
	const isUnsavedPost = post?.status === 'auto-draft';

	return {
		status: getEditedPostAttribute( 'status' ),
		isSavingPost: isSavingPost(),
		isUnsavedPost,
	};
};

const mapDispatchStatusToProps = dispatch => {
	return {
		onStatusChange( status ) {
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
