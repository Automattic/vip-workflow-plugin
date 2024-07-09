import apiFetch from '@wordpress/api-fetch';
import {
	Card,
	CardHeader,
	CardBody,
	CardFooter,
	Button,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { useEffect } from 'react';

export default function CustomStatusEditor( { status, isNew, onCancel, onStatusesUpdated } ) {
	const [ name, setName ] = useState( status?.name || '' );
	const [ slug, setSlug ] = useState( status?.slug || '' );
	const [ description, setDescription ] = useState( status?.description || '' );

	useEffect( () => {
		setName( status?.name || '' );
		setSlug( status?.slug || '' );
		setDescription( status?.description || '' );
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
		};

		if ( ! isNew ) {
			data.status_id = status.term_id;
		}

		let result;

		try {
			result = await apiFetch( {
				url: VW_CUSTOM_STATUS_CONFIGURE.url_edit_status,
				method: 'POST',
				data,
			} );
		} catch ( error ) {
			// ToDo: Ensure this turns into a UI status update
			console.error( 'Error saving status:', error );
		}

		onStatusesUpdated( result.updated_statuses );
	};

	return (
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

				{ ! isNew && (
					<TextControl
						help={ __(
							'The slug is the unique ID for the status and is changed when the name is changed.',
							'vip-workflow'
						) }
						label={ __( 'Slug', 'vip-workflow' ) }
						value={ slug }
						disabled
					/>
				) }
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

			<CardFooter justify={ 'end' }>
				<Button variant="secondary" onClick={ onCancel }>
					{ __( 'Cancel', 'vip-workflow' ) }
				</Button>
				<Button variant="primary" onClick={ handleSave }>
					{ saveButtonText }
				</Button>
			</CardFooter>
		</Card>
	);
}
