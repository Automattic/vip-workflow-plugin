import { Button, Modal, Flex } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

export default function DenyStatusDeleteModal( { status, onCancel } ) {
	return (
		<Modal
			title={ sprintf( __( 'Cannot delete %s', 'vip-workflow' ), status.name ) }
			size="medium"
			onRequestClose={ onCancel }
			closeButtonLabel={ __( 'Cancel', 'vip-workflow' ) }
		>
			<p>
				{ __(
					'The default status cannot be deleted. Set another status as default first.',
					'vip-workflow'
				) }
			</p>

			<Flex direction="row" justify="flex-end">
				<Button variant="tertiary" onClick={ onCancel }>
					{ __( 'Close', 'vip-workflow' ) }
				</Button>
			</Flex>
		</Modal>
	);
}
