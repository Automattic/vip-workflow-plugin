import { useLayoutEffect, useRef, useState } from '@wordpress/element';

export default function WorkflowArrow( { referenceDimensions } ) {
	const canvasRef = useRef( null );

	useLayoutEffect( () => {
		const canvas = canvasRef.current;
		const context = canvas?.getContext( '2d' );

		if ( ! canvas || ! context ) {
			return;
		}

		const width = 2;

		let height = 5;
		if ( referenceDimensions?.height ) {
			height = referenceDimensions.height + 3;
		}

		console.log( 'og width', width, 'og height', height );

		const ratio = 0.25;
		canvas.width = width * ratio;
		canvas.height = height * ratio;
		canvas.style.width = `${ width }px`;
		canvas.style.height = `${ height }px`;
		context.scale( ratio, ratio );

		context.fillStyle = 'rgba(0, 0, 0, 0)';
		context.fillRect( 0, 0, canvas.width, canvas.height );

		console.log( 'width', width, 'height', height );

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
			<canvas ref={ canvasRef }></canvas>
		</div>
	);
}

// Given a reference to a DOM element, hook and return the [ width, height ] of the element
export function useRefDimensions( ref ) {
	const [ width, setWidth ] = useState( 0 );
	const [ height, setHeight ] = useState( 0 );

	useLayoutEffect( () => {
		const sizeObserver = new ResizeObserver( entries => {
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

// Given a canvas context, draw an arrow from the top to the bottom of the canvas, pointing down
function drawArrow( context, width, height ) {
	// Arrow properties
	const arrowWidthPx = 4;

	// Set (x0, y0) to 20px below the top center of the canvas
	const x0 = width / 2;
	const y0 = 5;

	// Set (x1, y1) to 20px above the bottom center of the canvas
	let x1 = width / 2;
	let y1 = height - 5;

	// Adjust point upward to account for the arrow width
	const angle = Math.atan2( y1 - y0, x1 - x0 );
	x1 -= arrowWidthPx * Math.cos( angle );
	y1 -= arrowWidthPx * Math.sin( angle );

	// Draw line part of arrow
	context.beginPath();
	context.moveTo( x0, y0 );

	console.log( x0, y0, x1, y1 );

	context.lineTo( x1, y1 );
	context.lineWidth = arrowWidthPx;
	context.stroke();
}
