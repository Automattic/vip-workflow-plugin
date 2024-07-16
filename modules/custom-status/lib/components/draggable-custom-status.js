import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Icon, dragHandle } from '@wordpress/icons';
import clsx from 'clsx';

export default function DraggableCustomStatus( {
	customStatus,
	index,
	provided,
	snapshot,
	handleEditStatus,
} ) {
	const className = clsx(
		{
			dragging: snapshot.isDragging,
		},
		'custom-status-item'
	);

	const handleEditClick = () => {
		handleEditStatus( customStatus );
	};

	return (
		<>
			<div
				className={ className }
				ref={ provided.innerRef }
				{ ...provided.draggableProps }
				{ ...provided.dragHandleProps }
				style={ getItemStyle( index, snapshot.isDragging, provided.draggableProps.style ) }
			>
				<div className="name">{ customStatus.name }</div>

				<div className="edit">
					<Button variant="primary" size="small" onClick={ handleEditClick }>
						{ __( 'Edit', 'vip-workflow' ) }
					</Button>
				</div>

				<div className="drag-handle">
					<Icon icon={ dragHandle } size={ 20 } />
				</div>
			</div>
		</>
	);
}

const getItemStyle = ( index, isDragging, draggableStyle ) => {
	const defaultBackgroundColor = index % 2 ? 'white' : '#f6f7f7';

	return {
		background: isDragging ? 'lightgreen' : defaultBackgroundColor,
		...draggableStyle,
	};
};
