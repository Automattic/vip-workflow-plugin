import { Button, Modal, Flex } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

export default function ConfirmDeleteDialog( { status, onCancel, onConfirmDelete } ) {
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
						'Are you sure you want to delete "%1$s"? Any existing posts with this status will be reassigned to the default status "%2$s".',
						'vip-workflow'
					),
					status.name,
					VW_CUSTOM_STATUS_CONFIGURE.default_status_name
				) }
			</p>
			<strong style={ { display: 'block', marginTop: '1rem' } }>
				{ __( 'This action can not be undone.', 'vip-workflow' ) }
			</strong>

			<Flex direction="row" justify="flex-end">
				<Button variant="tertiary" onClick={ onCancel }>
					{ __( 'Cancel', 'vip-workflow' ) }
				</Button>

				<Button variant="primary" onClick={ onConfirmDelete }>
					{ __( 'Delete this status', 'vip-workflow' ) }
				</Button>
			</Flex>
		</Modal>
	);
}
