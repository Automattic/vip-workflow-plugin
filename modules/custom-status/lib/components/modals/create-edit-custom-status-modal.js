import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	__experimentalHStack as HStack,
	Modal,
	RadioControl,
	__experimentalSpacer as Spacer,
	TextControl,
	TextareaControl,
	Tooltip,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

import ErrorNotice from '../../../../shared/js/components/error-notice';
import MetadataSelectFormTokenField from '../metadata-select-form-token-field';
import UserSelectFormTokenField from '../user-select-form-token-field';

export default function CreateEditCustomStatusModal( {
	customStatus,
	editorialMetadatas,
	onCancel,
	onSuccess,
} ) {
	// Custom status properties
	const [ name, setName ] = useState( customStatus?.name || '' );
	const [ description, setDescription ] = useState( customStatus?.description || '' );
	const [ requiredUsers, setRequiredUsers ] = useState( customStatus?.meta?.required_users || [] );

	// Taxonomy conflicts arise if this is done server side, so this transient field is only set here.
	const [ requiredMetadatas, setRequiredMetadatas ] = useState( () => {
		if (
			customStatus?.meta?.required_metadata_ids &&
			customStatus?.meta?.required_metadata_ids.length > 0 &&
			editorialMetadatas.length > 0
		) {
			// Get the required metadata fields from the custom status meta and find the corresponding editorial metadata.
			const required_metadatas = customStatus.meta.required_metadata_ids.map( metadata => {
				return editorialMetadatas.find(
					editorialMetadata => editorialMetadata.term_id === metadata
				);
			} );

			// Filter out any undefined values.
			return required_metadatas.filter( metadata => metadata );
		}

		return [];
	} );

	const [ metadatas, setMetadatas ] = useState( editorialMetadatas );

	// Modal properties
	const [ error, setError ] = useState( null );
	const [ isRequesting, setIsRequesting ] = useState( false );
	const [ areRestrictedUsersSet, setAreRestrictedUsersSet ] = useState(
		requiredUsers.length > 0 ? 'specific' : 'all'
	);

	let titleText;
	if ( customStatus ) {
		titleText = sprintf( __( 'Edit "%s"', 'vip-workflow' ), customStatus.name );
	} else {
		titleText = __( 'Add New Custom Status', 'vip-workflow' );
	}

	const handleSave = async () => {
		const data = { name, description };

		if ( areRestrictedUsersSet === 'specific' ) {
			const userIds = requiredUsers.map( user => user.id );
			data.required_user_ids = userIds;
		}

		if ( requiredMetadatas.length > 0 ) {
			const metadataIds = requiredMetadatas.map( metadata => metadata.term_id );
			data.required_metadata_ids = metadataIds;
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
				help={ __( 'Visible to all users involved in the publishing process.', 'vip-workflow' ) }
				onChange={ setName }
				value={ name }
			/>
			<TextareaControl
				label={ __( 'Description', 'vip-workflow' ) }
				help={ __( 'Only visible to you and other administrators.', 'vip-workflow' ) }
				onChange={ setDescription }
				value={ description }
			/>
			<Spacer />
			<MetadataSelectFormTokenField
				label={ __(
					'What editorial fields are required to advance to the next status?',
					'vip-workflow'
				) }
				editorialMetadatas={ metadatas }
				requiredMetadatas={ requiredMetadatas }
				onMetadatasChanged={ setRequiredMetadatas }
			/>
			<RadioControl
				label="Who can advance to the next status?"
				selected={ areRestrictedUsersSet }
				options={ [
					{ label: 'All users', value: 'all' },
					{ label: 'Only specific users', value: 'specific' },
				] }
				onChange={ value => {
					setAreRestrictedUsersSet( value );
					if ( value === 'all' ) {
						setRequiredUsers( [] );
					}
				} }
			/>
			<Spacer />
			{ areRestrictedUsersSet !== 'all' && (
				<UserSelectFormTokenField
					label={ '' }
					requiredUsers={ requiredUsers }
					onUsersChanged={ setRequiredUsers }
				/>
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
