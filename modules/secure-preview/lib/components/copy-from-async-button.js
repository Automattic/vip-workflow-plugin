import { Button } from '@wordpress/components';
import { useCopyToClipboard, useMergeRefs } from '@wordpress/compose';
import { useLayoutEffect, useRef, useState } from '@wordpress/element';

/**
 * Custom <Button> that when provided an async function, will run that function and copy the result to the clipboard.
 * Accepts regular <Button> props in addition to `asyncFunction` and `onCopied`.
 *
 * This is implemented as a regular button, and invisible button, and useCopyToClipboard().
 * When the visible button is clicked, it runs the async function and sets the copyable text on the invisible button.
 * Next, the invisible button is programatically clicked, which invokes the useCopyToClipboard() hook and
 * sets the clipboard text.
 *
 * This component is used because useCopyToClipboard() does not support async functions.
 */
export default function CopyFromAsyncButton( props ) {
	const { asyncFunction, onCopied, children, ...buttonProps } = props;

	const [ textToCopy, setTextToCopy ] = useState( null );

	// Use copyButtonRef to programmatically access the invisible button that holds the text to be copied.
	const copyButtonRef = useRef( null );

	const copyToClipboardRef = useCopyToClipboard( textToCopy, () => {
		// After clipboard copy is complete, callback onCopied() with the text that was copied.
		onCopied( textToCopy );
	} );

	useLayoutEffect( () => {
		if ( textToCopy ) {
			// Once textToCopy has been set and layout is complete, activate the invisible button to copy to clipboard copy.
			copyButtonRef.current?.click();
		}
	}, [ textToCopy ] );

	const handleCopyClick = async () => {
		// When the visible button is clicked, run the async function and set the text to be copied.
		const text = await asyncFunction();
		setTextToCopy( text );
	};

	return (
		<>
			{ /* This is the visible button */ }
			<Button { ...buttonProps } onClick={ handleCopyClick }>
				{ children }
			</Button>

			{ /* This invisible button holds the text to be copied */ }
			<Button
				style={ { display: 'none' } }
				// useMergeRefs allows copyToClipboard functionality to work and
				// allow access to programaically click the button.
				ref={ useMergeRefs( [ copyButtonRef, copyToClipboardRef ] ) }
			></Button>
		</>
	);
}
