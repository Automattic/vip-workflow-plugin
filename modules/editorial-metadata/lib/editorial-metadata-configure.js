import './configure.scss';

import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';

import EditorialMetadataManager from './components/editorial-metadata-manager';

domReady( () => {
	const editorialMetadataManagerRoot = document.getElementById( 'editorial-metadata-manager' );

	if (editorialMetadataManagerRoot) {
		const root = createRoot(editorialMetadataManagerRoot);
		root.render(
			<EditorialMetadataManager supportedMetadataTypes={ VW_EDITORIAL_METADATA_CONFIGURE.supported_metadata_types } editorialMetadataTerms={ VW_EDITORIAL_METADATA_CONFIGURE.editorial_metadata_terms } />
		);
	}
} );

if ( module.hot ) {
	module.hot.accept();
}
