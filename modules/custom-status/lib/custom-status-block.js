import './editor.scss';

import { SelectControl } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { dispatch, select, subscribe, withDispatch, withSelect } from '@wordpress/data';
import { PluginPostStatusInfo } from '@wordpress/edit-post';
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Map Custom Statuses as options for SelectControl
 */
const statuses = window.VipWorkflowCustomStatuses.map( customStatus => ( {
	label: customStatus.name,
	value: customStatus.slug,
} ) );

// This is necessary to prevent a call stack exceeded problem within Gutenberg, as our code is called several times for some reason.
let postLocked = false;

/**
 * Subscribe to changes so we can set a default status and issue a notice when we lock/unlock the publishing capability.
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
			status: statuses[ 0 ].value,
		} );
	}

	const has_publish_capability =
		select( 'core/editor' ).getCurrentPost()?._links?.[ 'wp:action-publish' ] ?? false;

	const selectedStatus = select( 'core/editor' ).getEditedPostAttribute( 'status' );
	// check if the post status is in the list of custom statuses, and only then issue the notices
	if ( statuses.find( status => status.value === selectedStatus ) ) {
		if ( postLocked && has_publish_capability ) {
			postLocked = false;
			dispatch( 'core/notices' ).removeNotice( 'publish-guard-lock' );
		} else if ( ! postLocked && ! has_publish_capability ) {
			postLocked = true;
			dispatch( 'core/notices' ).createInfoNotice( __( 'This post is locked from publishing.' ), {
				id: 'publish-guard-lock',
				type: 'snackbar',
				explicitDismiss: true,
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
			<SelectControl label="" value={ status } options={ statuses } onChange={ onUpdate } />
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
