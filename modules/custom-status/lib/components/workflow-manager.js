import { DragDropContext, Draggable, Droppable } from '@hello-pangea/dnd';
import { Button, Flex, FlexBlock, FlexItem, Notice } from '@wordpress/components';
import { useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { plusCircle } from '@wordpress/icons';

import CustomStatusEditor from './custom-status-editor';
import DraggableCustomStatus from './draggable-custom-status';
import WorkflowArrow, { useRefDimensions } from './workflow-arrow';

export default function WorkflowManager( { customStatuses } ) {
	const [ error, setError ] = useState( null );

	const [ statuses, setStatuses ] = useState( customStatuses );

	const [ editStatus, setEditStatus ] = useState( null );
	const [ isNewStatus, setIsNewStatus ] = useState( false );

	const statusContainerRef = useRef( null );
	const [ statusContanerWidth, statusContainerHeight ] = useRefDimensions( statusContainerRef );

	const handleNewStatus = () => {
		setIsNewStatus( true );
		setEditStatus( {} );
	};

	const handleEditStatus = customStatus => {
		setIsNewStatus( false );
		setEditStatus( customStatus );
	};

	const handleCancelEditStatus = () => {
		setEditStatus( null );
	};

	const handleErrorThrown = error => {
		setError( error );
	};

	const handleStatusesUpdated = newStatuses => {
		// ToDo: Show a success message
		setStatuses( newStatuses );
		setEditStatus( null );
	};

	const handleDragEnd = result => {
		// Dropped outside the list
		if ( ! result.destination ) {
			return;
		}

		const reorderedItems = reorder( statuses, result.source.index, result.destination.index );
		setStatuses( reorderedItems );
		updateCustomStatusOrder( reorderedItems );
	};

	return (
		<>
			{ error && (
				<div style={ { marginBottom: '1rem' } }>
					<Notice status="error" isDismissible={ true }>
						<p>{ error }</p>
					</Notice>
				</div>
			) }
			<Flex direction={ [ 'column', 'row' ] } justify={ 'start' } align={ 'start' }>
				<FlexItem>
					<Flex align={ 'start' } justify={ 'start' }>
						<WorkflowArrow
							start={ __( 'Create', 'vip-workflow' ) }
							end={ __( 'Publish', 'vip-workflow' ) }
							referenceDimensions={ { width: statusContanerWidth, height: statusContainerHeight } }
						/>

						<div className="status-section">
							<DragDropContext onDragEnd={ handleDragEnd }>
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
											{ statuses.map( ( item, index ) => (
												<Draggable
													key={ item.term_id }
													draggableId={ `${ item.term_id }` }
													index={ index }
												>
													{ ( provided, snapshot ) => (
														<DraggableCustomStatus
															customStatus={ item }
															index={ index }
															provided={ provided }
															snapshot={ snapshot }
															handleEditStatus={ handleEditStatus }
															onStatusesUpdated={ handleStatusesUpdated }
															onErrorThrown={ handleErrorThrown }
														/>
													) }
												</Draggable>
											) ) }
											{ provided.placeholder }
										</div>
									) }
								</Droppable>
							</DragDropContext>

							<div className="add-status">
								<Button variant="secondary" icon={ plusCircle } onClick={ handleNewStatus }>
									{ __( 'Add new', 'vip-workflow' ) }
								</Button>
							</div>
						</div>
					</Flex>
				</FlexItem>
				<FlexBlock>
					{ editStatus && (
						<CustomStatusEditor
							status={ editStatus }
							isNew={ isNewStatus }
							onCancel={ handleCancelEditStatus }
							onStatusesUpdated={ handleStatusesUpdated }
							onErrorThrown={ handleErrorThrown }
						/>
					) }
				</FlexBlock>
			</Flex>
		</>
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
		custom_status_sortable_nonce: VW_CUSTOM_STATUS_CONFIGURE.nonce_reorder,
	};
	// Inform WordPress of our updated positions
	jQuery.post( VW_CUSTOM_STATUS_CONFIGURE.url_ajax, params, function ( retval ) {
		// ToDo: Ensure there's a message shown to the user on success/failure. Use Gutenberg Snackbar/Notice components?

		// jQuery( '.edit-flow-admin .edit-flow-message' ).remove();
		// If there's a success message, print it. Otherwise we assume we received an error message
		if ( retval.status === 'success' ) {
			let message =
				'<span class="edit-flow-updated-message edit-flow-message">' + retval.message + '</span>';
		} else {
			let message =
				'<span class="edit-flow-error-message edit-flow-message">' + retval.message + '</span>';
		}

		// jQuery( '.edit-flow-admin h2' ).append( message );
		// // Set a timeout to eventually remove it
		// setTimeout( edit_flow_hide_message, 8000 );
	} );
}
