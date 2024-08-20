import './editor.scss';

import { Button, PanelBody, SelectControl, TextControl } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { dispatch, select, subscribe, use, withDispatch, withSelect } from '@wordpress/data';
import {
	PluginPostStatusInfo,
	PluginSidebar,
	__experimentalMainDashboardButton as MainDashboardButton,
} from '@wordpress/edit-post';
import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { store as interfaceStore } from '@wordpress/interface';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Map Custom Statuses as options for SelectControl
 */
const statuses = window.VipWorkflowCustomStatuses.map( customStatus => ( {
	label: customStatus.name,
	value: customStatus.slug,
} ) );

// This is necessary to prevent a call stack exceeded problem within Gutenberg, as our code is called several times for some reason.
const postLocked = false;

// /**
//  * Subscribe to changes so we can set a default status and issue a notice when we lock/unlock the publishing capability.
//  */
// subscribe( function () {
// 	const postId = select( 'core/editor' ).getCurrentPostId();
// 	if ( ! postId ) {
// 		// Post isn't ready yet so don't do anything.
// 		return;
// 	}

// 	// For new posts, we need to force the default custom status which is the first entry.
// 	const isCleanNewPost = select( 'core/editor' ).isCleanNewPost();
// 	if ( isCleanNewPost ) {
// 		dispatch( 'core/editor' ).editPost( {
// 			status: statuses[ 0 ].value,
// 		} );
// 	}

// 	const selectedStatus = select( 'core/editor' ).getEditedPostAttribute( 'status' );
// 	// check if the post status is in the list of custom statuses, and only then issue the notices
// 	if (
// 		typeof vw_publish_guard_enabled !== 'undefined' &&
// 		vw_publish_guard_enabled &&
// 		statuses.find( status => status.value === selectedStatus )
// 	) {
// 		const has_publish_capability =
// 			select( 'core/editor' ).getCurrentPost()?._links?.[ 'wp:action-publish' ] ?? false;

// 		console.log( 'has_publish_capability: ', has_publish_capability, 'postLocked: ', postLocked );
// 		if ( postLocked && has_publish_capability ) {
// 			postLocked = false;
// 			dispatch( 'core/notices' ).removeNotice( 'publish-guard-lock' );
// 		} else if ( ! postLocked && ! has_publish_capability ) {
// 			postLocked = true;
// 			dispatch( 'core/notices' ).createInfoNotice( __( 'This post is locked from publishing.' ), {
// 				id: 'publish-guard-lock',
// 				type: 'snackbar',
// 			} );
// 		}
// 	}
// } );

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

const useInterceptPluginSidebarOpen = ( pluginSidebarIdentifier, callback ) => {
	use( registry => ( {
		dispatch: namespace => {
			const actions = { ...registry.dispatch( namespace ) };

			if ( namespace.name === interfaceStore.name ) {
				// Intercept enableComplementaryArea, which is used to open the sidebar for PluginSidebar
				const original_enableComplementaryArea = actions.enableComplementaryArea;

				actions.enableComplementaryArea = function ( scope, identifier ) {
					if ( 'core' === scope && pluginSidebarIdentifier === identifier ) {
						// If we're about to open the passed in sidebar, delegate back to the component.
						// Pass a callback to open the sidebar if desired.
						callback( () => original_enableComplementaryArea( scope, identifier ) );
					} else {
						original_enableComplementaryArea( scope, identifier );
					}
				};
			}

			return actions;
		},
	} ) );
};

// Plugin sidebar button

const CustomSaveButton = () => {
	return <div className="vip-workflow-save-button">{ __( 'Custom Save' ) }</div>;
};

const saveButtonSidebar = 'vip-workflow-save-button-sidebar';

// Plugin sidebar
const PluginSidebarExample = () => {
	const handleClick = openSidebar => {
		console.log( 'Custom save button clicked!' );

		// Optionally open a sidebar:
		// openSidebar();
	};

	useInterceptPluginSidebarOpen( `${ saveButtonPlugin }/${ saveButtonSidebar }`, handleClick );

	const extraProps = {
		closeLabel: 'Close it!',
	};

	return (
		<PluginSidebar
			name={ saveButtonSidebar }
			title={ __( 'Custom save button tooltip', 'vip-workflow' ) }
			className={ 'custom-class-name' }
			icon={ CustomSaveButton }
			{ ...extraProps }
		>
			{ /* Don't actually show anything in the sidebar */ }
			{ null }
		</PluginSidebar>
	);
};

const saveButtonPlugin = 'vip-workflow-save-button-plugin';
registerPlugin( saveButtonPlugin, { render: PluginSidebarExample } );
