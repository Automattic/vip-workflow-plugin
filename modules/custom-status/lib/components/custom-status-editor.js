import { Card, CardHeader, CardBody, CardFooter, CardDivider, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function CustomStatusEditor( { status } ) {
	return (
		<Card className="custom-status-editor">
			<CardHeader>
				<h3>{ __( 'Add new custom status', 'vip-workflow' ) }</h3>
			</CardHeader>
			<CardBody>CardBody</CardBody>
			<CardBody>CardBody (before CardDivider)</CardBody>
			<CardDivider />
			<CardBody>CardBody (after CardDivider)</CardBody>
			<CardFooter>
				CardFooter
				<Button variant="secondary">Action Button</Button>
			</CardFooter>
		</Card>
	);
}
