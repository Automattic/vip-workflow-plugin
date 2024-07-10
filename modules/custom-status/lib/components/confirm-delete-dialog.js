import { Button, Modal, Flex } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

export default function ConfirmDeleteDialog( { status, onCancel, onConfirmDelete } ) {
	let defaultStatus = VW_CUSTOM_STATUS_CONFIGURE.custom_statuses.filter(
		( { is_default } ) => is_default
	)?.[ 0 ];

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
						'Are you sure you want to delete "%1$s"? Any existing posts with this status will be reassigned to the default status%2$s.',
						'vip-workflow'
					),
					status.name,
					defaultStatus ? ` "${ defaultStatus.name }"` : ''
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
