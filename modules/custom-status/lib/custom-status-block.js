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

/**
 * Subscribe to changes so we can set a default status and update a button's text.
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
