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

	// Keeping this anywhere else causes the labels to not be available.
	if ( VW_CUSTOM_STATUSES.is_publish_guard_enabled ) {
		const editorialMetadataLabels = document.querySelectorAll(
			'#vip-workflow-editorial-metadata-label'
		);

		if ( ! editorialMetadataLabels || editorialMetadataLabels.length === 0 ) {
			return;
		}

		const customStatus = VW_CUSTOM_STATUSES.status_terms.find(
			customStatus =>
				customStatus.slug === select( editorStore ).getCurrentPostAttribute( 'status' )
		);

		if ( ! customStatus ) {
			return;
		}

		// Add a class to the editorial metadata labels so we can style them.
		editorialMetadataLabels.forEach( label => {
			if ( ! label?.classList ) {
				return;
			}

			if (
				customStatus?.meta?.required_metadatas &&
				customStatus?.meta?.required_metadatas.length > 0 &&
				customStatus?.meta?.required_metadatas.some( requiredMetadata =>
					label.classList.contains( requiredMetadata.slug )
				)
			) {
				label.classList.add( 'vip-workflow-editorial-metadata-required' );
			} else {
				label.classList.remove( 'vip-workflow-editorial-metadata-required' );
			}
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

	// filter out all statuses after the current status in the list
	const statusIndex = statusOptions.findIndex( option => option.value === status );
	const filteredStatusOptions = statusOptions.slice( 0, statusIndex + 1 );

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
						options={ filteredStatusOptions }
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
