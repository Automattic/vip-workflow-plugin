import './configure.scss';

import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';

import WorkflowManager from './components/workflow-manager';

domReady( () => {
	const workflowManagerRoot = document.getElementById( 'workflow-manager-root' );

	if ( workflowManagerRoot ) {
		const root = createRoot( workflowManagerRoot );
		root.render(
			<WorkflowManager customStatuses={ VW_CUSTOM_STATUS_CONFIGURE.custom_statuses } />
		);
	}
} );

if ( module.hot ) {
	module.hot.accept();
}
