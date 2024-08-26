import { Notice } from '@wordpress/components';

export default function ErrorNotice( { errorMessage, setError } ) {
	return (
		<div style={ { marginBottom: '1rem' } }>
			<Notice status="error" isDismissible={ true } onRemove={ () => setError( null ) }>
				<p>{ errorMessage }</p>
			</Notice>
		</div>
	);
}
