import {
	Button,
	Flex,
	__experimentalHeading as Heading,
	__experimentalText as Text,
	Tooltip,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Icon, dragHandle } from '@wordpress/icons';
import clsx from 'clsx';

export default function DraggableCustomStatus( {
	customStatus,
	provided,
	snapshot,
	handleEditStatus,
	handleDeleteStatus,
} ) {
	const className = clsx(
		{
			dragging: snapshot.isDragging,
		},
		'custom-status-item'
	);

	return (
		<>
			<div
				className={ className }
				ref={ provided.innerRef }
				{ ...provided.draggableProps }
				{ ...provided.dragHandleProps }
				style={ getItemStyle( provided.draggableProps.style ) }
			>
				<div className="drag-handle">
					<Icon icon={ dragHandle } size={ 20 } />
				</div>
				<Flex
					direction={ [ 'column' ] }
					justify={ 'end' }
					align={ 'start' }
					className="status-info"
				>
					<Heading level={ 4 }>{ customStatus.name }</Heading>
					<Text>
						<i>{ customStatus?.description }</i>
					</Text>
				</Flex>

				<Flex
					direction={ [ 'column', 'row' ] }
					justify={ 'end' }
					align={ 'end' }
					className={ 'custom-status-buttons' }
				>
					<Tooltip text={ __( 'Delete the status', 'vip-workflow' ) }>
						<Button
							size="compact"
							className="delete"
							variant="secondary"
							icon={ 'trash' }
							onClick={ handleDeleteStatus }
							style={ {
								color: '#b32d2e',
								boxShadow: 'inset 0 0 0 1px #b32d2e',
							} }
						></Button>
					</Tooltip>
					<Tooltip text={ __( 'Edit the status', 'vip-workflow' ) }>
						<Button
							size="compact"
							className="edit"
							variant="primary"
							icon={ 'edit' }
							onClick={ handleEditStatus }
						></Button>
					</Tooltip>
				</Flex>
			</div>
		</>
	);
}

const getItemStyle = draggableStyle => {
	return {
		background: '#FFFFFF',
		...draggableStyle,
	};
};
