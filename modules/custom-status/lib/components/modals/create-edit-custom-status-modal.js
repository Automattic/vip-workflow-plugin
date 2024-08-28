import apiFetch from '@wordpress/api-fetch';
import { Button, Modal, TextControl, TextareaControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

import ErrorNotice from '../../../../shared/js/components/error-notice';

export default function CreateEditCustomStatusModal( { customStatus, onCancel, onSuccess } ) {
	const [ error, setError ] = useState( null );
	const [ name, setName ] = useState( customStatus?.name || '' );
	const [ description, setDescription ] = useState( customStatus?.description || '' );
	const [ isRequesting, setIsRequesting ] = useState( false );

	let titleText;
	if ( customStatus ) {
		titleText = sprintf( __( 'Edit "%s"', 'vip-workflow' ), customStatus.name );
	} else {
		titleText = __( 'Add New Custom Status', 'vip-workflow' );
	}

	const handleSave = async () => {
		const data = {
			name,
			description,
		};

		try {
			setIsRequesting( true );
			const result = await apiFetch( {
				url:
					VW_CUSTOM_STATUS_CONFIGURE.url_edit_status + ( customStatus ? customStatus.term_id : '' ),
				method: customStatus ? 'PUT' : 'POST',
				data,
			} );

			onSuccess(
				customStatus
					? sprintf( __( 'Status "%s" updated successfully.', 'vip-workflow' ), name )
					: sprintf( __( 'Status "%s" added successfully.', 'vip-workflow' ), name ),
				result
			);
		} catch ( error ) {
			setError( error.message );
		}

		setIsRequesting( false );
	};

	return (
		<Modal
			title={ titleText }
			size="medium"
			onRequestClose={ onCancel }
			closeButtonLabel={ __( 'Cancel', 'vip-workflow' ) }
		>
			{ error && <ErrorNotice errorMessage={ error } setError={ setError } /> }
			<TextControl
				help={ __( 'The name is used to identify the custom status.', 'vip-workflow' ) }
				label={ __( 'Name', 'vip-workflow' ) }
				onChange={ setName }
				value={ name }
			/>
			<TextareaControl
				help={ __(
					'The description is primarily for administrative use, to give you some context on what the custom status is to be used for.',
					'vip-workflow'
				) }
				label={ __( 'Description', 'vip-workflow' ) }
				onChange={ setDescription }
				value={ description }
			/>
			<Button variant="primary" onClick={ handleSave } disabled={ isRequesting }>
				{ customStatus ? __( 'Update', 'vip-workflow' ) : __( 'Save', 'vip-workflow' ) }
			</Button>
		</Modal>
	);
}
