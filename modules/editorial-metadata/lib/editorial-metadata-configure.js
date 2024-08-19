import './configure.scss';

import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';

import EditorialMetadataManager from './components/editorial-metadata-manager';

domReady( () => {
	const editorialMetadataManagerRoot = document.getElementById( 'editorial-metadata-manager' );

	if ( editorialMetadataManagerRoot ) {
		const root = createRoot( editorialMetadataManagerRoot );
		root.render(
			<EditorialMetadataManager/>
		);
	}
} );

if ( module.hot ) {
	module.hot.accept();
}
