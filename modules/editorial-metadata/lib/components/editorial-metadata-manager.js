import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	Card,
	CardFooter,
	CardHeader,
	__experimentalConfirmDialog as ConfirmDialog,
	Flex,
	FlexItem,
	__experimentalHeading as Heading,
	__experimentalText as Text,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

import ErrorNotice from '../../../shared/js/components/error-notice';
import SuccessNotice from '../../../shared/js/components/success-notice';
import CreateEditEditorialMetadataModal from './modals/create-edit-editorial-metadata-modal';

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
	const [ isCreateEditModalVisible, setIsCreateEditModalVisible ] = useState( false );

	const handleErrorThrown = error => {
		setSuccess( null );
		setError( error );
		setIsConfirmingDelete( false );
		setEMetadataTerm( null );
	};

	const handleSuccess = ( message, eMetadataTermResult ) => {
		setError( null );
		setSuccess( message );

		if ( eMetadataTerm && ! isConfirmingDelete ) {
			setEMetadataTerms(
				eMetadataTerms
					.map( eMetadataTerm => {
						if ( eMetadataTerm.term_id === eMetadataTermResult.term_id ) {
							return eMetadataTermResult;
						}
						return eMetadataTerm;
					} )
					.sort( ( termA, termB ) => termA.name.localeCompare( termB.name ) )
			);
		} else if ( isConfirmingDelete ) {
			setEMetadataTerms(
				eMetadataTerms
					.filter( eMetadataTerm => eMetadataTerm.term_id !== eMetadataTermResult.term_id )
					.sort( ( termA, termB ) => termA.name.localeCompare( termB.name ) )
			);
		} else {
			// Add new term to the list and sort it
			setEMetadataTerms(
				[ ...eMetadataTerms, eMetadataTermResult ].sort( ( termA, termB ) =>
					termA.name.localeCompare( termB.name )
				)
			);
		}

		setIsCreateEditModalVisible( false );
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

	// Convert metadata type to a label, that's in sentence case
	const convertMetadataTypeToLabel = metadataType => {
		return metadataType.charAt( 0 ).toUpperCase() + metadataType.slice( 1 );
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
					__( 'Are you sure you want to delete "%1$s"?', 'vip-workflow' ),
					eMetadataTerm?.name
				) }
			</p>
			<strong style={ { display: 'block', marginTop: '1rem' } }>
				{ __( 'This action can not be undone.', 'vip-workflow' ) }
			</strong>
		</ConfirmDialog>
	);

	const createEditModal = (
		<CreateEditEditorialMetadataModal
			availableMetadataTypes={ availableMetadataTypes.map( availableMetadataType => {
				return {
					value: availableMetadataType,
					label: convertMetadataTypeToLabel( availableMetadataType ),
				};
			} ) }
			metadata={ eMetadataTerm }
			onCancel={ () => setIsCreateEditModalVisible( false ) }
			onSuccess={ handleSuccess }
		/>
	);

	return (
		<>
			{ success && <SuccessNotice successMessage={ success } setSuccess={ setSuccess } /> }
			{ error && <ErrorNotice errorMessage={ error } setError={ setError } /> }
			<Flex direction={ [ 'column' ] } justify={ 'start' } align={ 'start' }>
				<FlexItem>
					<Button
						variant="secondary"
						icon={ 'plus' }
						onClick={ () => {
							setEMetadataTerm( null );
							setIsCreateEditModalVisible( true );
						} }
					></Button>
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
											<Text>{ convertMetadataTypeToLabel( eMetadataTerm.meta.type ) }</Text>
										</Flex>
									</CardHeader>
									<CardFooter>
										<Flex direction={ [ 'column', 'row' ] } justify={ 'end' } align={ 'end' }>
											<div className="crud-emetadata">
												<Button
													size="compact"
													className="delete-emetadata"
													variant="secondary"
													icon={ 'trash' }
													onClick={ () => {
														setEMetadataTerm( eMetadataTerm );
														setIsConfirmingDelete( true );
													} }
													style={ {
														color: '#b32d2e',
														boxShadow: 'inset 0 0 0 1px #b32d2e',
													} }
												></Button>
												<Button
													size="compact"
													variant="primary"
													icon={ 'edit' }
													onClick={ () => {
														setEMetadataTerm( eMetadataTerm );
														setIsCreateEditModalVisible( true );
													} }
												></Button>
											</div>
										</Flex>
									</CardFooter>
								</Card>
							</FlexItem>
						);
					} ) }
				</Flex>
			</Flex>

			{ deleteModal }
			{ isCreateEditModalVisible && createEditModal }
		</>
	);
}
