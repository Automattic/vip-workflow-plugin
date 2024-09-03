import { BaseControl, Button, SelectControl } from '@wordpress/components';
import { dispatch, select, subscribe } from '@wordpress/data';
import { PluginPostStatusInfo } from '@wordpress/edit-post';
import { store as editorStore } from '@wordpress/editor';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { isUsingWorkflowStatus } from '../util';

/**
 * Map Custom Statuses as options for SelectControl
 */
const statusOptions = VW_CUSTOM_STATUSES.status_terms.map( customStatus => ( {
	label: customStatus.name,
	value: customStatus.slug,
} ) );

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
} );

/**
 * Custom status component
 * @param object props
 */
export default function CustomStatusSidebar( { onUpdateStatus, postType, status } ) {
	const [ isEditingStatus, setIsEditingStatus ] = useState( false );

	const handleChangeStatus = statusSlug => {
		onUpdateStatus( statusSlug );
		dispatch( editorStore ).savePost();
		setIsEditingStatus( false );
	};

	if ( ! isUsingWorkflowStatus( postType, status ) ) {
		return null;
	}

	const customStatusName = VW_CUSTOM_STATUSES.status_terms.find( t => t.slug === status )?.name;

	return (
		<PluginPostStatusInfo
			className={ `vip-workflow-extended-post-status vip-workflow-extended-post-status-${ status }` }
		>
			<div className="vip-workflow-extended-post-status-section">
				<h4>{ __( 'Extended Post Status', 'vip-workflow' ) }</h4>

				{ ! isEditingStatus && (
					<div className="vip-workflow-extended-post-status-edit">
						{ customStatusName }
						<Button size="compact" variant="link" onClick={ () => setIsEditingStatus( true ) }>
							{ __( 'Edit', 'vip-workflow' ) }
						</Button>
					</div>
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

			<div className="vip-workflow-edit-help-text">
				<BaseControl
					help={ __(
						'Click "Edit" to skip a custom status, or rollback to a previous state.',
						'vip-workflow'
					) }
				></BaseControl>
			</div>
		</PluginPostStatusInfo>
	);
}
