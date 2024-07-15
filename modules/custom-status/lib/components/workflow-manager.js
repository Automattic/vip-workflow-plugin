import { DragDropContext, Draggable, Droppable } from '@hello-pangea/dnd';
import apiFetch from '@wordpress/api-fetch';
import { Button, Flex, FlexBlock, FlexItem, Notice } from '@wordpress/components';
import { useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { plusCircle } from '@wordpress/icons';

import CustomStatusEditor from './custom-status-editor';
import DraggableCustomStatus from './draggable-custom-status';
import SuccessNotice from './success-notice';
import WorkflowArrow, { useRefDimensions } from './workflow-arrow';

export default function WorkflowManager( { customStatuses } ) {
	const [ success, setSuccess ] = useState( null );
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
		setSuccess( null );
		setError( error );
	};

	const handleSuccess = message => {
		setError( null );
		setSuccess( message );
	};

	const handleStatusesUpdated = newStatuses => {
		setStatuses( newStatuses );
		setEditStatus( null );
	};

	const handleDragEnd = async result => {
		// Dropped outside the list
		if ( ! result.destination ) {
			return;
		}

		const originalOrder = statuses;
		const reorderedItems = reorder( statuses, result.source.index, result.destination.index );

		// Optimistically reorder to avoid status jumping when the request completes
		setStatuses( reorderedItems );

		try {
			const data = {
				status_positions: reorderedItems.map( item => item.term_id ),
			};

			const result = await apiFetch( {
				url: VW_CUSTOM_STATUS_CONFIGURE.url_reorder_status,
				method: 'POST',
				data,
			} );

			handleSuccess( __( 'Statuses reordered successfully.', 'vip-workflow' ) );
			setStatuses( result.updated_statuses );
		} catch ( error ) {
			handleErrorThrown( error.message );
			setStatuses( originalOrder );
		}
	};

	return (
		<>
			{ <SuccessNotice success={ success } /> }
			{ error && (
				<div style={ { marginBottom: '1rem' } }>
					<Notice status="error" isDismissible={ true } onRemove={ () => setError( null ) }>
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
							onSuccess={ handleSuccess }
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
