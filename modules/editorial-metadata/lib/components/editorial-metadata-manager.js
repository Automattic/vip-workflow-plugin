import { Button, Card, CardHeader, Flex, FlexBlock, __experimentalHeading as Heading, __experimentalText as Text } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

export default function EditorialMetadataManager({ supportedMetadataTypes, editorialMetadataTerms }) {
	const [availableMetadataTypes, setAvailableMetadataTypes] = useState( supportedMetadataTypes );
	const [eMetadataTerms, setEMetadataTerms] = useState(editorialMetadataTerms);

	console.log('EditorialMetadataManager', eMetadataTerms);

	return (<>
		<Flex direction={ [ 'column', 'row' ] } justify={ 'start' } align={ 'start' }>
			{eMetadataTerms.map((eMetadataTerm) => {
				return (
					<FlexBlock>
						<Card>
							<CardHeader>
								<Flex direction={['column']} justify={'start'} align={'start'}>
									<Heading level={4}>{eMetadataTerm.name}</Heading>
									<Text>{eMetadataTerm.description}</Text>
								</Flex>
								<Flex className='emetadata-item' direction={['column']} justify={'start'} align={'end'}>
									<div className="crud-emetadata">
										<Button
											className="delete-emetadata"
											variant="secondary"
											style={ {
												color: '#b32d2e',
												boxShadow: 'inset 0 0 0 1px #b32d2e',
											} }
											>
												{__('Delete this field', 'vip-workflow')}
										</Button>
										<Button
											variant="primary"
											>
											{ __( 'Edit', 'vip-workflow' ) }
										</Button>
									</div>
								</Flex>
							</CardHeader>
						</Card>
					</FlexBlock>
				);
			})}
		</Flex>
		<div className="add-emetadata">
			<Button variant="primary">
				{__('Add', 'vip-workflow')}
			</Button>
		</div>
	</>);
}
