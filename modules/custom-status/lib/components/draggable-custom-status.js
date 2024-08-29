import { Button } from '@wordpress/components';
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
				<div className="name">{ customStatus.name }</div>

				<div className="delete">
					<Button
						size="compact"
						className="delete-emetadata"
						variant="secondary"
						icon={ 'trash' }
						onClick={ handleDeleteStatus }
						style={ {
							color: '#b32d2e',
							boxShadow: 'inset 0 0 0 1px #b32d2e',
						} }
					></Button>
				</div>

				<div className="edit">
					<Button
						size="compact"
						variant="primary"
						icon={ 'edit' }
						onClick={ handleEditStatus }
					></Button>
				</div>

				<div className="drag-handle">
					<Icon icon={ dragHandle } size={ 20 } />
				</div>
			</div>
		</>
	);
}

const getItemStyle = draggableStyle => {
	return {
		background: '#ECECEC',
		...draggableStyle,
	};
};
