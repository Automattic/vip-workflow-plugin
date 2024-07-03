import { DragDropContext, Droppable, Draggable } from '@hello-pangea/dnd';
import domReady from '@wordpress/dom-ready';
import { createRoot, useState, useRef, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const WorkflowArrow = ( { start, end } ) => {
	const canvasRef = useRef( null );
	const width = 40;
	const height = 350;

	useEffect( () => {
		const canvas = canvasRef.current;
		const context = canvas?.getContext( '2d' );

		if ( ! canvas || ! context ) {
			return;
		}

		const ratio = window.devicePixelRatio;
		canvas.width = width * ratio;
		canvas.height = height * ratio;
		canvas.style.width = `${ width }px`;
		canvas.style.height = `${ height }px`;
		context.scale( ratio, ratio );

		context.fillStyle = 'rgba(0, 0, 0, 0)';
		context.fillRect( 0, 0, canvas.width, canvas.height );

		drawArrow( context, width, height );
	}, [ width, height ] );

	return (
		<div
			style={ {
				display: 'flex',
				flexDirection: 'column',
				alignItems: 'center',
				width: 'fit-content',
			} }
		>
			<h3>{ start }</h3>
			<canvas ref={ canvasRef }></canvas>
			<h3>{ end }</h3>
		</div>
	);
};

function drawArrow( context, width, height ) {
	const x0 = width / 2;
	const y0 = 20;
	let x1 = width / 2;
	let y1 = height - 20;

	context.beginPath();
	const arrowWidth = 3;
	const headLength = 6;
	const headAngle = Math.PI / 6;
	const angle = Math.atan2( y1 - y0, x1 - x0 );

	context.lineWidth = arrowWidth;

	/* Adjust the point */
	x1 -= arrowWidth * Math.cos( angle );
	y1 -= arrowWidth * Math.sin( angle );

	context.beginPath();
	context.moveTo( x0, y0 );
	context.lineTo( x1, y1 );
	context.stroke();

	context.beginPath();
	context.lineTo( x1, y1 );
	context.lineTo(
		x1 - headLength * Math.cos( angle - headAngle ),
		y1 - headLength * Math.sin( angle - headAngle )
	);
	context.lineTo(
		x1 - headLength * Math.cos( angle + headAngle ),
		y1 - headLength * Math.sin( angle + headAngle )
	);
	context.closePath();
	context.stroke();
	context.fill();
}

function WorkflowManager() {
	const [ items, setItems ] = useState( VW_CUSTOM_STATUS_CONFIGURE.custom_statuses );
	console.log( 'Custom statuses:', items );

	const onDragEnd = result => {
		// dropped outside the list
		if ( ! result.destination ) {
			return;
		}

		const reorderedItems = reorder( items, result.source.index, result.destination.index );
		setItems( reorderedItems );
	};

	return (
		<div
			style={ {
				display: 'flex',
			} }
		>
			<WorkflowArrow start={ __( 'Create' ) } end={ __( 'Publish' ) } />

			<DragDropContext onDragEnd={ onDragEnd }>
				<Droppable droppableId="droppable">
					{ ( provided, snapshot ) => (
						<div
							{ ...provided.droppableProps }
							ref={ provided.innerRef }
							style={ getListStyle( snapshot.isDraggingOver ) }
						>
							{ items.map( ( item, index ) => (
								<Draggable key={ item.term_id } draggableId={ `${ item.term_id }` } index={ index }>
									{ ( provided, snapshot ) => (
										<div
											ref={ provided.innerRef }
											{ ...provided.draggableProps }
											{ ...provided.dragHandleProps }
											style={ getItemStyle(
												index,
												snapshot.isDragging,
												provided.draggableProps.style
											) }
										>
											{ item.name }
										</div>
									) }
								</Draggable>
							) ) }
							{ provided.placeholder }
						</div>
					) }
				</Droppable>
			</DragDropContext>
		</div>
	);
}

domReady( () => {
	const root = createRoot( document.getElementById( 'workflow-manager' ) );
	root.render( <WorkflowManager /> );
} );

// a little function to help us with reordering the result
const reorder = ( list, startIndex, endIndex ) => {
	const result = Array.from( list );
	const [ removed ] = result.splice( startIndex, 1 );
	result.splice( endIndex, 0, removed );

	return result;
};

const grid = 8;

const getItemStyle = ( index, isDragging, draggableStyle ) => {
	const defaultBackgroundColor = index % 2 ? 'white' : '#f6f7f7';

	return {
		// some basic styles to make the items look a bit nicer
		userSelect: 'none',
		padding: grid * 2,
		margin: `0 0 ${ grid }px 0`,
		// change background colour if dragging
		background: isDragging ? 'lightgreen' : defaultBackgroundColor,
		border: '1px solid #c3c4c7',

		// styles we need to apply on draggables
		...draggableStyle,
	};
};

const getListStyle = isDraggingOver => ( {
	background: isDraggingOver ? 'lightblue' : 'white',
	padding: grid,
	width: 250,
	height: 'fit-content',
	alignSelf: 'center',
	border: '1px solid #c3c4c7',
	boxShadow: '0 1px 1px rgba(0,0,0,.04)',
} );

( function ( $ ) {
	const inlineEditCustomStatus = {
		init() {
			const t = this;
			const row = $( '#inline-edit' );

			t.what = '#term-';

			$( document ).on( 'click', '.editinline', function () {
				inlineEditCustomStatus.edit( this );
				return false;
			} );

			// prepare the edit row
			row.on( 'keyup', function ( e ) {
				if ( e.which == 27 ) {
					return inlineEditCustomStatus.revert();
				}
			} );

			$( 'a.cancel', row ).on( 'click', function () {
				return inlineEditCustomStatus.revert();
			} );
			$( 'a.save', row ).on( 'click', function () {
				return inlineEditCustomStatus.save( this );
			} );
			$( 'input, select', row ).on( 'keydown', function ( e ) {
				if ( e.which == 13 ) {
					return inlineEditCustomStatus.save( this );
				}
			} );
		},

		toggle( el ) {
			const t = this;
			$( t.what + t.getId( el ) ).css( 'display' ) == 'none' ? t.revert() : t.edit( el );
		},

		edit( id ) {
			const t = this;
			let editRow;
			t.revert();

			if ( typeof id === 'object' ) {
				id = t.getId( id );
			}

			( editRow = $( '#inline-edit' ).clone( true ) ), ( rowData = $( '#inline_' + id ) );
			$( 'td', editRow ).attr( 'colspan', $( '.widefat:first thead th:visible' ).length );

			if ( $( t.what + id ).hasClass( 'alternate' ) ) {
				$( editRow ).addClass( 'alternate' );
			}

			$( t.what + id )
				.hide()
				.after( editRow );

			const name_text = $( '.name', rowData ).text();
			$( ':input[name="name"]', editRow ).val( name_text );
			$( ':input[name="description"]', editRow ).val( $( '.description', rowData ).text() );

			$( editRow )
				.attr( 'id', 'edit-' + id )
				.addClass( 'inline-editor' )
				.show();

			const $name_field = $( '.ptitle', editRow ).eq( 0 );
			if ( 'draft' === name_text.trim().toLowerCase() ) {
				$name_field.attr( 'readonly', 'readonly' );
			} else {
				$name_field.focus();
			}

			return false;
		},

		save( id ) {
			let params;
			let fields;
			const tax = $( 'input[name="taxonomy"]' ).val() || '';

			if ( typeof id === 'object' ) {
				id = this.getId( id );
			}

			$( 'table.widefat .inline-edit-save .waiting' ).show();

			params = {
				action: 'inline_save_status',
				status_id: id,
			};

			fields = $( '#edit-' + id + ' :input' ).serialize();
			params = fields + '&' + $.param( params );

			// make ajax request
			$.post( ajaxurl, params, function ( r ) {
				let row;
				let new_id;
				$( 'table.widefat .inline-edit-save .waiting' ).hide();

				if ( r ) {
					if ( -1 != r.indexOf( '<tr' ) ) {
						$( inlineEditCustomStatus.what + id ).remove();
						new_id = $( r ).attr( 'id' );

						$( '#edit-' + id )
							.before( r )
							.remove();
						row = new_id ? $( '#' + new_id ) : $( inlineEditCustomStatus.what + id );
						row.hide().fadeIn();
					} else {
						$( '#edit-' + id + ' .inline-edit-save .error' )
							.html( r )
							.show();
					}
				} else {
					$( '#edit-' + id + ' .inline-edit-save .error' )
						.html( inlineEditL10n.error )
						.show();
				}
			} );
			return false;
		},

		revert() {
			let id = $( 'table.widefat tr.inline-editor' ).attr( 'id' );

			if ( id ) {
				$( 'table.widefat .inline-edit-save .waiting' ).hide();
				$( '#' + id ).remove();
				id = id.substr( id.lastIndexOf( '-' ) + 1 );
				$( this.what + id ).show();
			}

			return false;
		},

		getId( o ) {
			const id = o.tagName == 'TR' ? o.id : $( o ).parents( 'tr' ).attr( 'id' );
			const parts = id.split( '-' );
			return parts[ parts.length - 1 ];
		},
	};

	$( document ).ready( function () {
		inlineEditCustomStatus.init();
	} );
} )( jQuery );

jQuery( document ).ready( function () {
	jQuery( '.delete-status a' ).on( 'click', function () {
		if ( ! confirm( VW_CUSTOM_STATUS_CONFIGURE.delete_status_string ) ) {
			return false;
		}
	} );
} );
