import { Button, Card, CardHeader, Flex, FlexBlock, __experimentalHeading as Heading, __experimentalText as Text } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

export default function EditorialMetadataManager({ supportedMetadataTypes, editorialMetadataTerms }) {
	const [availableMetadataTypes, setAvailableMetadataTypes] = useState( supportedMetadataTypes );
	const [eMetadataTerms, setEMetadataTerms] = useState(editorialMetadataTerms);

	console.log('EditorialMetadataManager', eMetadataTerms);

	return (<>
		<Flex direction={['column']} justify={'start'} align={'end'}>
			<FlexBlock>
				<Button variant="secondary">
					{__('Add New Metadata', 'vip-workflow')}
				</Button>
			</FlexBlock>
			<Flex className='emetadata-item' direction={ [ 'column', 'row' ] } justify={ 'start' } align={ 'start' }>
				{eMetadataTerms.map((eMetadataTerm) => {
					return (
						<FlexBlock key={eMetadataTerm.term_id}>
							<Card>
								<CardHeader>
									<Flex direction={['column']} justify={'start'} align={'start'}>
										<Heading level={4}>{eMetadataTerm.name}</Heading>
										<Text>{eMetadataTerm.description}</Text>
									</Flex>
									<Flex direction={['column']} justify={'start'} align={'end'}>
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
		</Flex>
	</>);
}
