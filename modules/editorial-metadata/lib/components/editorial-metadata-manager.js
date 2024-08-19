import { useState } from '@wordpress/element';

export default function EditorialMetadataManager({ supportedMetadataTypes, editorialMetadataTerms }) {
	const [availableMetadataTypes, setAvailableMetadataTypes] = useState( supportedMetadataTypes );
	const [eMetadataTerms, setEMetadataTerms] = useState(editorialMetadataTerms);

	console.log(eMetadataTerms);

	return (<>
		<p>{availableMetadataTypes}</p>
	</>);
}
