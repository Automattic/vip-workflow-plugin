import {
	Card,
	CardHeader,
	CardBody,
	CardFooter,
	Button,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function CustomStatusEditor( { status } ) {
	return (
		<Card className="custom-status-editor">
			<CardHeader>
				<h3>{ __( 'Add new custom status', 'vip-workflow' ) }</h3>
			</CardHeader>

			<CardBody>
				<TextControl
					help={ __( 'The name is used to identify the status.', 'vip-workflow' ) }
					label={ __( 'Custom Status', 'vip-workflow' ) }
					onChange={ function noRefCheck() {} }
					value=""
				/>
				<TextControl
					help={ __(
						'The slug is the unique ID for the status and is changed when the name is changed.',
						'vip-workflow'
					) }
					label={ __( 'Slug', 'vip-workflow' ) }
					onChange={ function noRefCheck() {} }
					value=""
				/>
				<TextareaControl
					help={ __(
						'The description is primarily for administrative use, to give you some context on what the custom status is to be used for.',
						'vip-workflow'
					) }
					label={ __( 'Description', 'vip-workflow' ) }
					onChange={ function noRefCheck() {} }
					value=""
				/>
			</CardBody>

			<CardFooter justify={ 'end' }>
				<Button variant="secondary">{ __( 'Cancel', 'vip-workflow' ) }</Button>
				<Button variant="primary">{ __( 'Update Status', 'vip-workflow' ) }</Button>
			</CardFooter>
		</Card>
	);
}
