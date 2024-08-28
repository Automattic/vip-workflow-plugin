import apiFetch from '@wordpress/api-fetch';
import { Button, Modal, SelectControl, TextControl, TextareaControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

import ErrorNotice from '../../../../shared/js/components/error-notice';

export default function CreateEditEditorialMetadataModal( {
	availableMetadataTypes,
	metadata,
	onCancel,
	onSuccess,
} ) {
	const [ error, setError ] = useState( null );
	const [ name, setName ] = useState( metadata?.name || '' );
	const [ description, setDescription ] = useState( metadata?.description || '' );
	const [ type, setType ] = useState( metadata?.type || availableMetadataTypes[ 0 ].value );
	const [ isRequesting, setIsRequesting ] = useState( false );

	let titleText;
	if ( metadata ) {
		titleText = sprintf( __( 'Edit "%s"', 'vip-workflow' ), metadata.name );
	} else {
		titleText = __( 'Add New Editorial Metadata', 'vip-workflow' );
	}

	const handleSave = async () => {
		const data = {
			name,
			description,
			type,
		};

		try {
			setIsRequesting( true );
			const result = await apiFetch( {
				url:
					VW_EDITORIAL_METADATA_CONFIGURE.url_edit_editorial_metadata +
					( metadata ? metadata.term_id : '' ),
				method: metadata ? 'PUT' : 'POST',
				data,
			} );

			onSuccess(
				metadata
					? sprintf( __( 'Editorial Metadata "%s" updated successfully.', 'vip-workflow' ), name )
					: sprintf( __( 'Editorial Metadata "%s" added successfully.', 'vip-workflow' ), name ),
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
				help={ __( 'The name is used to identify the editorial metadata field.', 'vip-workflow' ) }
				label={ __( 'Name', 'vip-workflow' ) }
				onChange={ setName }
				value={ name }
			/>
			<TextareaControl
				help={ __(
					'The description is primarily for your team to provide context on how the editorial metadata field should be used.',
					'vip-workflow'
				) }
				label={ __( 'Description', 'vip-workflow' ) }
				onChange={ setDescription }
				value={ description }
			/>
			<SelectControl
				help={ __(
					'This is to identify the type for the editorial metadata field.',
					'vip-workflow'
				) }
				label={ __( 'Type', 'vip-workflow' ) }
				value={ type }
				options={ availableMetadataTypes }
				onChange={ setType }
			/>
			<Button variant="primary" onClick={ handleSave } disabled={ isRequesting }>
				{ metadata ? __( 'Update', 'vip-workflow' ) : __( 'Save', 'vip-workflow' ) }
			</Button>
		</Modal>
	);
}
