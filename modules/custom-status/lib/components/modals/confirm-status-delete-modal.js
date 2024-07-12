import { Button, Flex, Modal } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

export default function ConfirmStatusDeleteModal( { status, onCancel, onConfirmDelete } ) {
	return (
		<Modal
			title={ sprintf( __( 'Delete %s?', 'vip-workflow' ), status.name ) }
			size="medium"
			onRequestClose={ onCancel }
			closeButtonLabel={ __( 'Cancel', 'vip-workflow' ) }
		>
			<p>
				{ sprintf(
					__(
						'Are you sure you want to delete "%1$s"? Any existing posts with this status will be reassigned to the starting status.',
						'vip-workflow'
					),
					status.name
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
					{ __( 'Delete this status', 'vip-workflow' ) }
				</Button>
			</Flex>
		</Modal>
	);
}
