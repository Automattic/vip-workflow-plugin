import { useEffect, useRef } from '@wordpress/element';

// ToDo: Move this to a common components folder.
export default function SuccessNotice( { success } ) {
	const messageDivRef = useRef( null );

	useEffect( () => {
		const pageTitle = document.querySelector( '.vip-workflow-admin h2' );

		const messageDiv = document.createElement( 'span' );
		messageDiv.classList.add( 'vip-workflow-updated-message', 'vip-workflow-message' );
		messageDiv.style.opacity = '0';
		pageTitle.append( messageDiv );

		messageDivRef.current = messageDiv;
	}, [] );

	useEffect( () => {
		if ( success ) {
			messageDivRef.current.textContent = success;
			messageDivRef.current.style.opacity = '1';
		} else {
			messageDivRef.current.style.opacity = '0';
		}
	}, [ success ] );

	return null;
}
