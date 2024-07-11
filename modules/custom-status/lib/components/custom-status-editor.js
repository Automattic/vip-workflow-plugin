import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	Card,
	CardBody,
	CardDivider,
	CardFooter,
	CardHeader,
	TextControl,
	TextareaControl,
	ToggleControl,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { useEffect } from 'react';

import ConfirmStatusDeleteModal from './modals/confirm-status-delete-modal';
import DenyStatusDeleteModal from './modals/deny-status-delete-modal';

export default function CustomStatusEditor( {
	status,
	defaultStatus,
	isNew,
	onCancel,
	onStatusesUpdated,
	onErrorThrown,
	onSuccess,
} ) {
	const [ name, setName ] = useState( status?.name || '' );
	const [ description, setDescription ] = useState( status?.description || '' );
	const [ isDefault, setIsDefault ] = useState( status?.is_default || false );
	const [ isConfirmingDelete, setIsConfirmingDelete ] = useState( false );
	const [ isRequesting, setIsRequesting ] = useState( false );

	useEffect( () => {
		setName( status?.name || '' );
		setDescription( status?.description || '' );
		setIsDefault( status?.is_default || false );
	}, [ status ] );

	let titleText;
	if ( isNew ) {
		titleText = __( 'Add New Custom Status', 'vip-workflow' );
	} else {
		titleText = sprintf( __( 'Edit "%s"', 'vip-workflow' ), status.name );
	}

	let saveButtonText;
	if ( isNew ) {
		saveButtonText = __( 'Add New Status', 'vip-workflow' );
	} else {
		saveButtonText = sprintf( __( 'Update Status', 'vip-workflow' ), status.name );
	}

	const handleSave = async () => {
		let data = {
			name,
			description,
			is_default: isDefault,
		};

		if ( ! isNew ) {
			data.status_id = status.term_id;
		}

		try {
			setIsRequesting( true );
			const result = await apiFetch( {
				url: VW_CUSTOM_STATUS_CONFIGURE.url_edit_status + ( isNew ? '' : status.term_id ),
				method: isNew ? 'POST' : 'PUT',
				data,
			} );

			onSuccess(
				isNew
					? sprintf( __( 'Status "%s" added successfully.', 'vip-workflow' ), name )
					: sprintf( __( 'Status "%s" updated successfully.', 'vip-workflow' ), name )
			);
			onStatusesUpdated( result.updated_statuses );
		} catch ( error ) {
			onErrorThrown( error.message );
		}

		setIsRequesting( false );
	};

	const handleDelete = async () => {
		try {
			const result = await apiFetch( {
				url: VW_CUSTOM_STATUS_CONFIGURE.url_edit_status + status.term_id,
				method: 'DELETE',
			} );

			onSuccess(
				sprintf( __( 'Status "%s" deleted successfully.', 'vip-workflow' ), status.name )
			);
			onStatusesUpdated( result.updated_statuses );
		} catch ( error ) {
			onErrorThrown( error.message );
		}

		setIsConfirmingDelete( false );
	};

	let deleteModal;
	if ( status?.is_default ) {
		deleteModal = (
			<DenyStatusDeleteModal status={ status } onCancel={ () => setIsConfirmingDelete( false ) } />
		);
	} else {
		deleteModal = (
			<ConfirmStatusDeleteModal
				status={ status }
				defaultStatus={ defaultStatus }
				onCancel={ () => setIsConfirmingDelete( false ) }
				onConfirmDelete={ handleDelete }
			/>
		);
	}

	return (
		<>
			<Card className="custom-status-editor">
				<CardHeader>
					<h3>{ titleText }</h3>
				</CardHeader>

				<CardBody>
					<TextControl
						help={ __( 'The name is used to identify the status.', 'vip-workflow' ) }
						label={ __( 'Custom Status', 'vip-workflow' ) }
						onChange={ value => {
							setName( value );
						} }
						value={ name }
					/>
					<TextareaControl
						help={ __(
							'The description is primarily for administrative use, to give you some context on what the custom status is to be used for.',
							'vip-workflow'
						) }
						label={ __( 'Description', 'vip-workflow' ) }
						onChange={ value => {
							setDescription( value );
						} }
						value={ description }
					/>
				</CardBody>
				<CardDivider />
				<CardBody>
					<ToggleControl
						checked={ isDefault }
						label={ __( 'Make default status', 'vip-workflow' ) }
						help={ __(
							'New posts will automatically be assigned to this status.',
							'vip-workflow'
						) }
						onChange={ () => setIsDefault( ! isDefault ) }
					/>
				</CardBody>

				<CardFooter justify={ 'end' }>
					{ ! isNew && (
						<Button
							variant="secondary"
							onClick={ () => setIsConfirmingDelete( true ) }
							style={ {
								color: '#b32d2e',
								boxShadow: 'inset 0 0 0 1px #b32d2e',
								marginRight: 'auto',
							} }
						>
							{ __( 'Delete this status', 'vip-workflow' ) }
						</Button>
					) }
					<Button variant="secondary" onClick={ onCancel }>
						{ __( 'Cancel', 'vip-workflow' ) }
					</Button>
					<Button variant="primary" onClick={ handleSave } disabled={ isRequesting }>
						{ saveButtonText }
					</Button>
				</CardFooter>
			</Card>

			{ isConfirmingDelete && deleteModal }
		</>
	);
}
