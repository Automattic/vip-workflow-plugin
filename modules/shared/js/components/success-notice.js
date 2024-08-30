import { Notice } from '@wordpress/components';

export default function SuccessNotice( { successMessage, setSuccess } ) {
	return (
		<div style={ { marginBottom: '1rem' } }>
			<Notice status="success" isDismissible={ true } onRemove={ () => setSuccess( null ) }>
				<p>{ successMessage }</p>
			</Notice>
		</div>
	);
}
