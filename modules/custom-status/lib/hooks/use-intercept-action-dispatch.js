import { use } from '@wordpress/data';

const functionOverrides = {};

export default function useInterceptActionDispatch( storeName, actionName, callback ) {
	use( registry => ( {
		dispatch: namespace => {
			const namespaceName = typeof namespace === 'string' ? namespace : namespace.name;
			const actions = { ...registry.dispatch( namespaceName ) };

			if ( namespaceName !== storeName ) {
				// This is an unrelated registry namespace, return original actions.
				return actions;
			}

			// If we haven't pregenerated an override for this action, do so now.
			if ( ! functionOverrides?.[ storeName ]?.[ actionName ] ) {
				if ( ! functionOverrides?.[ storeName ] ) {
					functionOverrides[ storeName ] = {};
				}

				const originalAction = actions[ actionName ];

				functionOverrides[ storeName ][ actionName ] = ( ...args ) => {
					callback( originalAction, args );
				};
			}

			// Reassign the action to the override.
			actions[ actionName ] = functionOverrides[ storeName ][ actionName ];
			return actions;
		},
	} ) );
}
