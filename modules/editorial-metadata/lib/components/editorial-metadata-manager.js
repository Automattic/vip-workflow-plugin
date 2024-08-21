import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	Card,
	CardHeader,
	Flex,
	FlexItem,
	__experimentalHeading as Heading,
	Notice,
	__experimentalText as Text,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

import CreateEditEditorialMetadataModal from './modals/create-edit-editorial-metadata-modal';
import ConfirmDeleteModal from '../../../shared/js/components/modals/confirm-delete-modal';
import SuccessNotice from '../../../shared/js/components/success-notice';

export default function EditorialMetadataManager( {
	supportedMetadataTypes,
	editorialMetadataTerms,
} ) {
	const [ success, setSuccess ] = useState( null );
	const [ error, setError ] = useState( null );
	const [ availableMetadataTypes ] = useState( supportedMetadataTypes );
	const [ eMetadataTerms, setEMetadataTerms ] = useState( editorialMetadataTerms );
	const [ eMetadataTerm, setEMetadataTerm ] = useState( null );
	const [ isConfirmingDelete, setIsConfirmingDelete ] = useState( false );
	const [ isModalVisible, setIsModalVisible ] = useState( false );

	const handleErrorThrown = error => {
		setSuccess( null );
		setError( error );
		setIsModalVisible( false );
		setIsConfirmingDelete( false );
		setEMetadataTerm( null );
	};

	const handleSuccess = ( message, eMetadataTermResult ) => {
		setError( null );
		setSuccess( message );
		if ( eMetadataTerm && ! isConfirmingDelete ) {
			setEMetadataTerms(
				eMetadataTerms.map( eMetadataTerm => {
					if ( eMetadataTerm.term_id === eMetadataTermResult.term_id ) {
						return eMetadataTermResult;
					}
					return eMetadataTerm;
				} )
			);
		} else if ( isConfirmingDelete ) {
			setEMetadataTerms(
				eMetadataTerms.filter(
					eMetadataTerm => eMetadataTerm.term_id !== eMetadataTermResult.term_id
				)
			);
		} else {
			setEMetadataTerms( [ ...eMetadataTerms, eMetadataTermResult ] );
		}
		setIsModalVisible( false );
		setIsConfirmingDelete( false );
	};

	const handleDelete = async () => {
		try {
			await apiFetch( {
				url: VW_EDITORIAL_METADATA_CONFIGURE.url_edit_editorial_metadata + eMetadataTerm.term_id,
				method: 'DELETE',
			} );

			handleSuccess(
				sprintf(
					__( 'Editorial Metadata "%s" deleted successfully.', 'vip-workflow' ),
					eMetadataTerm.name
				),
				eMetadataTerm
			);
		} catch ( error ) {
			handleErrorThrown( error.message );
		}
	};

	const deleteModal = (
		<ConfirmDeleteModal
			confirmationMessage={ '' }
			dataType={ 'metadata field' }
			name={ eMetadataTerm?.name }
			onCancel={ () => setIsConfirmingDelete( false ) }
			onConfirmDelete={ handleDelete }
		/>
	);

	const createEditModal = (
		<CreateEditEditorialMetadataModal
			availableMetadataTypes={ availableMetadataTypes.map( availableMetadataType => {
				return {
					value: availableMetadataType,
					label: availableMetadataType,
				};
			} ) }
			metadata={ eMetadataTerm }
			onCancel={ () => setIsModalVisible( false ) }
			onSuccess={ handleSuccess }
			onErrorThrown={ handleErrorThrown }
		/>
	);

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
			<Flex direction={ [ 'column' ] } justify={ 'start' } align={ 'start' }>
				<FlexItem>
					<Button
						variant="secondary"
						onClick={ () => {
							setEMetadataTerm( null );
							setIsModalVisible( true );
						} }
					>
						{ __( 'Add New Metadata', 'vip-workflow' ) }
					</Button>
				</FlexItem>
				<Flex className="emetadata-items" direction={ [ 'column', 'row' ] } justify={ 'start' }>
					{ eMetadataTerms.map( eMetadataTerm => {
						return (
							<FlexItem className="emetadata-item" key={ eMetadataTerm.term_id }>
								<Card>
									<CardHeader>
										<Flex direction={ [ 'column' ] } justify={ 'start' } align={ 'start' }>
											<Heading level={ 4 }>{ eMetadataTerm.name }</Heading>
											<Text>
												<i>{ eMetadataTerm.description }</i>
											</Text>
										</Flex>
										<Flex direction={ [ 'column', 'row' ] } justify={ 'end' } align={ 'end' }>
											<div className="crud-emetadata">
												<Button
													className="delete-emetadata"
													variant="secondary"
													onClick={ () => {
														setEMetadataTerm( eMetadataTerm );
														setIsConfirmingDelete( true );
													} }
													style={ {
														color: '#b32d2e',
														boxShadow: 'inset 0 0 0 1px #b32d2e',
													} }
												>
													{ __( 'Delete', 'vip-workflow' ) }
												</Button>
												<Button
													variant="primary"
													onClick={ () => {
														setEMetadataTerm( eMetadataTerm );
														setIsModalVisible( true );
													} }
												>
													{ __( 'Edit', 'vip-workflow' ) }
												</Button>
											</div>
										</Flex>
									</CardHeader>
								</Card>
							</FlexItem>
						);
					} ) }
				</Flex>
			</Flex>

			{ isConfirmingDelete && deleteModal }
			{ isModalVisible && createEditModal }
		</>
	);
}
