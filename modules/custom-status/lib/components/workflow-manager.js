import { DragDropContext, Draggable, Droppable } from '@hello-pangea/dnd';
import apiFetch from '@wordpress/api-fetch';
import { Button, Flex, FlexBlock, FlexItem } from '@wordpress/components';
import { useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import DraggableCustomStatus from './draggable-custom-status';
import CreateEditCustomStatusModal from './modals/create-edit-custom-status-modal';
import WorkflowArrow, { useRefDimensions } from './workflow-arrow';
import ErrorNotice from '../../../shared/js/components/error-notice';
import ConfirmDeleteModal from '../../../shared/js/components/modals/confirm-delete-modal';
import SuccessNotice from '../../../shared/js/components/success-notice';

export default function WorkflowManager( { customStatuses } ) {
	const [ success, setSuccess ] = useState( null );
	const [ error, setError ] = useState( null );

	const [ statuses, setStatuses ] = useState( customStatuses );
	const [ status, setStatus ] = useState( null );

	const [ isConfirmingDelete, setIsConfirmingDelete ] = useState( false );
	const [ isCreateEditModalVisible, setIsCreateEditModalVisible ] = useState( false );

	const statusContainerRef = useRef( null );
	const [ statusContanerWidth, statusContainerHeight ] = useRefDimensions( statusContainerRef );

	const handleErrorThrown = error => {
		setSuccess( null );
		setError( error );
		setIsConfirmingDelete( false );
		setStatus( null );
	};

	const handleSuccess = ( message, statusResult ) => {
		setError( null );
		setSuccess( message );

		if ( Array.isArray( statusResult ) ) {
			setStatuses( statusResult );
		} else if ( status && ! isConfirmingDelete ) {
			setStatuses(
				statuses.map( status => {
					if ( status.term_id === statusResult.term_id ) {
						return statusResult;
					}
					return status;
				} )
			);
		} else if ( isConfirmingDelete ) {
			setStatuses( statuses.filter( status => status.term_id !== statusResult.term_id ) );
		} else {
			setStatuses( [ ...statuses, statusResult ] );
		}

		setIsCreateEditModalVisible( false );
		setIsConfirmingDelete( false );
	};

	const handleDelete = async () => {
		try {
			await apiFetch( {
				url: VW_CUSTOM_STATUS_CONFIGURE.url_edit_status + status.term_id,
				method: 'DELETE',
			} );

			handleSuccess(
				sprintf( __( 'Status "%s" deleted successfully.', 'vip-workflow' ), status.name ),
				status
			);
		} catch ( error ) {
			handleErrorThrown( error.message );
		}
	};

	const deleteModal = (
		<ConfirmDeleteModal
			confirmationMessage={
				'Any existing posts with this status will be reassigned to the first status.'
			}
			name={ status?.name }
			onCancel={ () => setIsConfirmingDelete( false ) }
			onConfirmDelete={ handleDelete }
		/>
	);

	const createEditModal = (
		<CreateEditCustomStatusModal
			customStatus={ status }
			onCancel={ () => setIsCreateEditModalVisible( false ) }
			onSuccess={ handleSuccess }
		/>
	);

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

			handleSuccess( __( 'Statuses reordered successfully.', 'vip-workflow' ), result );
		} catch ( error ) {
			handleErrorThrown( error.message );
			setStatuses( originalOrder );
		}
	};

	return (
		<>
			{ <SuccessNotice success={ success } /> }
			{ error && <ErrorNotice errorMessage={ error } setError={ setError } /> }
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
															handleEditStatus={ () => {
																setStatus( item );
																setIsCreateEditModalVisible( true );
															} }
															handleDeleteStatus={ () => {
																setStatus( item );
																setIsConfirmingDelete( true );
															} }
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
								<Button
									variant="secondary"
									icon={ 'plus' }
									onClick={ () => {
										setStatus( null );
										setIsCreateEditModalVisible( true );
									} }
								></Button>
							</div>
						</div>
					</Flex>
				</FlexItem>
				<FlexBlock></FlexBlock>
			</Flex>

			{ isConfirmingDelete && deleteModal }
			{ isCreateEditModalVisible && createEditModal }
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
