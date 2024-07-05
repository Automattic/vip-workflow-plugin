import { DragDropContext, Droppable, Draggable } from '@hello-pangea/dnd';
import { useState, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import DraggableCustomStatus from './draggable-custom-status';
import WorkflowArrow, { useRefDimensions } from './workflow-arrow';

export default function WorkflowManager( { customStatuses } ) {
	const [ items, setItems ] = useState( customStatuses );

	const statusContainerRef = useRef( null );
	const [ statusContanerWidth, statusContainerHeight ] = useRefDimensions( statusContainerRef );

	const onDragEnd = result => {
		// Dropped outside the list
		if ( ! result.destination ) {
			return;
		}

		const reorderedItems = reorder( items, result.source.index, result.destination.index );
		setItems( reorderedItems );
		updateCustomStatusOrder( reorderedItems );
	};

	return (
		<div className="workflow-manager">
			<WorkflowArrow
				start={ __( 'Create' ) }
				end={ __( 'Publish' ) }
				referenceDimensions={ { width: statusContanerWidth, height: statusContainerHeight } }
			/>

			<DragDropContext onDragEnd={ onDragEnd }>
				<Droppable droppableId="droppable">
					{ ( provided, snapshot ) => (
						<div
							className="status-container"
							{ ...provided.droppableProps }
							ref={ el => {
								statusContainerRef.current = el;
								provided.innerRef( el );
							} }
							style={ getListStyle( snapshot.isDraggingOver ) }
						>
							{ items.map( ( item, index ) => (
								<Draggable key={ item.term_id } draggableId={ `${ item.term_id }` } index={ index }>
									{ ( provided, snapshot ) => (
										<DraggableCustomStatus
											customStatus={ item }
											index={ index }
											provided={ provided }
											snapshot={ snapshot }
										/>
									) }
								</Draggable>
							) ) }
							{ provided.placeholder }
						</div>
					) }
				</Droppable>
			</DragDropContext>
		</div>
	);
}

const reorder = ( list, startIndex, endIndex ) => {
	const result = Array.from( list );
	const [ removed ] = result.splice( startIndex, 1 );
	result.splice( endIndex, 0, removed );

	return result;
};

const getListStyle = isDraggingOver => ( {
	background: isDraggingOver ? 'lightblue' : 'white',
} );

function updateCustomStatusOrder( reorderedItems ) {
	// Prepare the POST
	const params = {
		action: 'update_status_positions',
		status_positions: reorderedItems.map( item => item.term_id ),
		custom_status_sortable_nonce: VW_CUSTOM_STATUS_CONFIGURE.reorder_nonce,
	};
	// Inform WordPress of our updated positions
	jQuery.post( VW_CUSTOM_STATUS_CONFIGURE.ajax_url, params, function ( retval ) {
		// jQuery( '.edit-flow-admin .edit-flow-message' ).remove();
		// If there's a success message, print it. Otherwise we assume we received an error message
		if ( retval.status == 'success' ) {
			var message =
				'<span class="edit-flow-updated-message edit-flow-message">' + retval.message + '</span>';
		} else {
			var message =
				'<span class="edit-flow-error-message edit-flow-message">' + retval.message + '</span>';
		}
		// jQuery( '.edit-flow-admin h2' ).append( message );
		// // Set a timeout to eventually remove it
		// setTimeout( edit_flow_hide_message, 8000 );
	} );
}
