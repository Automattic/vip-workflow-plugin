import { DragDropContext, Draggable, Droppable } from '@hello-pangea/dnd';
import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	__experimentalConfirmDialog as ConfirmDialog,
	Flex,
	__experimentalHeading as Heading,
	Tooltip,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

import ErrorNotice from '../../../shared/js/components/error-notice';
import SuccessNotice from '../../../shared/js/components/success-notice';
import DraggableCustomStatus from './draggable-custom-status';
import CreateEditCustomStatusModal from './modals/create-edit-custom-status-modal';

export default function WorkflowManager( { customStatuses, editorialMetadatas } ) {
	const [ success, setSuccess ] = useState( null );
	const [ error, setError ] = useState( null );

	const [ statuses, setStatuses ] = useState( customStatuses );
	const [ metadatas, setMetadatas ] = useState( editorialMetadatas );

	const [ status, setStatus ] = useState( null );

	const [ isConfirmingDelete, setIsConfirmingDelete ] = useState( false );
	const [ isCreateEditModalVisible, setIsCreateEditModalVisible ] = useState( false );

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
		<ConfirmDialog
			onConfirm={ handleDelete }
			onCancel={ () => setIsConfirmingDelete( false ) }
			isOpen={ isConfirmingDelete }
			confirmButtonText={ __( 'Proceed with Deletion', 'vip-workflow' ) }
		>
			<p>
				{ sprintf(
					__(
						'Are you sure you want to delete "%1$s"? Any existing posts with this status will be reassigned to the first status.',
						'vip-workflow'
					),
					status?.name
				) }
			</p>
			<strong style={ { display: 'block', marginTop: '1rem' } }>
				{ __( 'This action can not be undone.', 'vip-workflow' ) }
			</strong>
		</ConfirmDialog>
	);

	const createEditModal = (
		<CreateEditCustomStatusModal
			customStatus={ status }
			editorialMetadatas={ metadatas }
			onCancel={ () => setIsCreateEditModalVisible( false ) }
			onSuccess={ handleSuccess }
		/>
	);

	const handleDragEnd = async result => {
		// Dropped outside the list
		if ( ! result.destination ) {
			return;
		}

		// Dropped in the same position
		if ( result.destination.index === result.source.index ) {
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

			await apiFetch( {
				url: VW_CUSTOM_STATUS_CONFIGURE.url_reorder_status,
				method: 'POST',
				data,
			} );

			// Do not show a success message - gracefully do nothing when a reorder succeeds
		} catch ( error ) {
			handleErrorThrown( error.message );
			setStatuses( originalOrder );
		}
	};

	return (
		<>
			{ success && <SuccessNotice successMessage={ success } setSuccess={ setSuccess } /> }
			{ error && <ErrorNotice errorMessage={ error } setError={ setError } /> }
			<div className="status-section">
				<Flex
					className="status-start"
					direction={ [ 'column' ] }
					align="center"
					justify="space-between"
				>
					<Tooltip
						text={ __( 'This is the start point for your publishing workflow', 'vip-workflow' ) }
					>
						<Heading level={ 4 }>{ __( 'Starting Point', 'vip-workflow' ) }</Heading>
					</Tooltip>
				</Flex>

				<DragDropContext onDragEnd={ handleDragEnd }>
					<Droppable droppableId="droppable">
						{ provided => (
							<div
								className="status-container"
								{ ...provided.droppableProps }
								ref={ el => {
									provided.innerRef( el );
								} }
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

								<Tooltip
									text={ __(
										'Add a new status at the end of your publishing workflow',
										'vip-workflow'
									) }
								>
									<Button
										className="add-status"
										variant="secondary"
										icon={ 'plus' }
										onClick={ () => {
											setStatus( null );
											setIsCreateEditModalVisible( true );
										} }
									></Button>
								</Tooltip>
							</div>
						) }
					</Droppable>
				</DragDropContext>

				<Flex
					direction={ [ 'column' ] }
					align="center"
					justify="space-between"
					className="status-end"
				>
					<Tooltip
						text={ __( 'This is the end point for your publishing workflow', 'vip-workflow' ) }
					>
						<Heading level={ 4 }>{ __( 'Publish', 'vip-workflow' ) }</Heading>
					</Tooltip>
				</Flex>
			</div>

			{ deleteModal }
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
