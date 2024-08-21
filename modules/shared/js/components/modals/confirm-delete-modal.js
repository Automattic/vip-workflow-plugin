import { Button, Flex, Modal } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

export default function ConfirmDeleteModal( {
	confirmationMessage,
	dataType,
	name,
	onCancel,
	onConfirmDelete,
} ) {
	return (
		<Modal
			title={ sprintf( __( 'Delete %s?', 'vip-workflow' ), name ) }
			size="medium"
			onRequestClose={ onCancel }
			closeButtonLabel={ __( 'Cancel', 'vip-workflow' ) }
		>
			<p>
				{ sprintf(
					__( 'Are you sure you want to delete "%1$s"? %2$s', 'vip-workflow' ),
					name,
					confirmationMessage
				) }
			</p>
			<strong style={ { display: 'block', marginTop: '1rem' } }>
				{ __( 'This action can not be undone.', 'vip-workflow' ) }
			</strong>

			<Flex direction="row" justify="flex-end">
				<Button variant="tertiary" onClick={ onCancel }>
					{ __( 'Cancel', 'vip-workflow' ) }
				</Button>

				<Button variant="primary" onClick={ onConfirmDelete } style={ { background: '#b32d2e' } }>
					{ sprintf( __( 'Delete this %1$s', 'vip-workflow' ), dataType ) }
				</Button>
			</Flex>
		</Modal>
	);
}
