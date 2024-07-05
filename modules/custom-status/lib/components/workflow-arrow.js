import { useRef, useLayoutEffect, useState } from '@wordpress/element';

export default function WorkflowArrow( { start, end, referenceDimensions } ) {
	const canvasRef = useRef( null );

	useLayoutEffect( () => {
		const canvas = canvasRef.current;
		const context = canvas?.getContext( '2d' );

		if ( ! canvas || ! context ) {
			return;
		}

		const width = 40;

		let height = 100;
		if ( referenceDimensions?.height ) {
			height = referenceDimensions.height + 70;
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
	}, [ referenceDimensions ] );

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
}

export function useRefDimensions( ref ) {
	const [ width, setWidth ] = useState( 0 );
	const [ height, setHeight ] = useState( 0 );

	useLayoutEffect( () => {
		let sizeObserver = new ResizeObserver( entries => {
			entries.forEach( entry => {
				setWidth( Math.floor( entry.contentRect.width ) );
				setHeight( Math.floor( entry.contentRect.height ) );
			} );
		} );

		if ( ref.current ) {
			const { current } = ref;
			const boundingRect = current.getBoundingClientRect();
			const { width, height } = boundingRect;

			setWidth( Math.floor( width ) );
			setHeight( Math.floor( height ) );

			sizeObserver.observe( ref.current );
		}

		return () => {
			sizeObserver.disconnect();
		};
	}, [ ref ] );

	return [ width, height ];
}

function drawArrow( context, width, height ) {
	const x0 = width / 2;
	const y0 = 20;
	let x1 = width / 2;
	let y1 = height - 20;

	const arrowWidth = 4;
	const headLength = 10;
	const headAngle = Math.PI / 6;
	const angle = Math.atan2( y1 - y0, x1 - x0 );

	context.beginPath();

	// Adjust point
	x1 -= arrowWidth * Math.cos( angle );
	y1 -= arrowWidth * Math.sin( angle );

	// Draw line
	context.beginPath();
	context.moveTo( x0, y0 );
	context.lineTo( x1, y1 );

	context.lineWidth = arrowWidth;
	context.stroke();

	// Draw arrow head
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
	context.fillStyle = 'black';
	context.stroke();
	context.fill();
}
