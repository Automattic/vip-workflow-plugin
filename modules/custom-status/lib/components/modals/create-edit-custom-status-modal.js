import apiFetch from '@wordpress/api-fetch';
import {
	Card,
	Button,
	Modal,
	TextControl,
	TextareaControl,
	Tooltip,
	ToggleControl,
	__experimentalDivider as Divider,
	__experimentalHStack as HStack,
	CardBody,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

import ErrorNotice from '../../../../shared/js/components/error-notice';
import UserSelectFormTokenField from '../user-select-form-token-field';

export default function CreateEditCustomStatusModal( { customStatus, onCancel, onSuccess } ) {
	// Custom status properties
	const [ name, setName ] = useState( customStatus?.name || '' );
	const [ description, setDescription ] = useState( customStatus?.description || '' );
	const [ requiredUsers, setRequiredUsers ] = useState( customStatus?.required_users || [] );

	// Modal properties
	const [ error, setError ] = useState( null );
	const [ isRequesting, setIsRequesting ] = useState( false );
	const [ isRestrictedSectionVisible, setIsRestrictedSectionVisible ] = useState(
		requiredUsers.length > 0
	);

	let titleText;
	if ( customStatus ) {
		titleText = sprintf( __( 'Edit "%s"', 'vip-workflow' ), customStatus.name );
	} else {
		titleText = __( 'Add New Custom Status', 'vip-workflow' );
	}

	const handleSave = async () => {
		const data = { name, description };

		if ( isRestrictedSectionVisible ) {
			const userIds = requiredUsers.map( user => user.id );
			data.required_user_ids = userIds;
		}

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
				label={ __( 'Name', 'vip-workflow' ) }
				help={ __( 'The name is used to identify the custom status.', 'vip-workflow' ) }
				onChange={ setName }
				value={ name }
			/>
			<TextareaControl
				label={ __( 'Description', 'vip-workflow' ) }
				help={ __(
					'The description is primarily for administrative use, to give you some context on what the custom status is to be used for.',
					'vip-workflow'
				) }
				onChange={ setDescription }
				value={ description }
			/>
			<Divider margin="1rem" />

			<ToggleControl
				label={ __( 'This status is restricted', 'vip-workflow' ) }
				help={ __(
					'Require a specific user or role to advance to the next status.',
					'vip-workflow'
				) }
				checked={ isRestrictedSectionVisible }
				onChange={ setIsRestrictedSectionVisible }
			/>

			{ isRestrictedSectionVisible && (
				<>
					<Card>
						<CardBody>
							<UserSelectFormTokenField
								label={ __( 'Allowed users', 'vip-workflow' ) }
								help={ __( 'These users are allowed to advance this status.', 'vip-workflow' ) }
								requiredUsers={ requiredUsers }
								onUsersChanged={ setRequiredUsers }
							/>
						</CardBody>
					</Card>
				</>
			) }

			<HStack justify="right" style={ { marginTop: '16px' } }>
				<Tooltip
					text={
						customStatus
							? __( 'Update the custom status', 'vip-workflow' )
							: __( 'Save the new custom status', 'vip-workflow' )
					}
				>
					<Button variant="primary" onClick={ handleSave } disabled={ isRequesting }>
						{ customStatus ? __( 'Update', 'vip-workflow' ) : __( 'Save', 'vip-workflow' ) }
					</Button>
				</Tooltip>
			</HStack>
		</Modal>
	);
}
