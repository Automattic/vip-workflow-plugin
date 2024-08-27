import { select } from '@wordpress/data';
import { store as interfaceStore } from '@wordpress/interface';

import useInterceptActionDispatch from './use-intercept-action-dispatch';

export default function useInterceptPluginSidebar( sidebarName, onButtonClick ) {
	const isPluginSidebarActive = () =>
		select( interfaceStore ).getActiveComplementaryArea( /* scope */ 'core' ) === sidebarName;

	useInterceptActionDispatch(
		interfaceStore.name,
		'enableComplementaryArea',
		( originalAction, args ) => {
			if ( args[ 0 ] === 'core' && args[ 1 ] === sidebarName ) {
				const isSidebarActive = isPluginSidebarActive();
				const toggleSidebar = () => originalAction( ...args );

				onButtonClick( isSidebarActive, toggleSidebar );
			} else {
				// Otherwise, delegate to the original action
				originalAction( ...args );
			}
		}
	);

	useInterceptActionDispatch(
		interfaceStore.name,
		'disableComplementaryArea',
		( originalAction, args ) => {
			const isSidebarActive = isPluginSidebarActive();

			if ( isSidebarActive ) {
				const toggleSidebar = () => originalAction( ...args );
				onButtonClick( isSidebarActive, toggleSidebar );
			} else {
				// A different area is active, delegate to the original action
				originalAction( ...args );
			}
		}
	);
}
