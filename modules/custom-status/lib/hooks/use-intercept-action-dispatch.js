import { use } from '@wordpress/data';
import { useEffect } from '@wordpress/element';

const functionOverrides = {};

export default function useInterceptActionDispatch( storeName, actionName, callback ) {
	// Create a new entry to track this function override if it doesn't exist
	if ( ! functionOverrides?.[ storeName ]?.[ actionName ] ) {
		if ( ! functionOverrides?.[ storeName ] ) {
			functionOverrides[ storeName ] = {};
		}

		// The purpose of `actionIntercept` is to provide a single unchanging reference to interception logic.
		// Without this, each time useInterceptActionDispatch() is called we will create a new override and trigger
		// rerenders of components that rely on dispatch functions as dependencies.
		// `actionIntercept`'s value is set once on dispatch registration after we have access to the original action.
		//
		// The `callback` key is used to store the latest callback function. When this changes we update it a new
		// value with useEffect below and leave actionIntercept unchanged, making dispatch callers happy.
		// This allows action intercepts with changing callbacks to work as expected.
		functionOverrides[ storeName ][ actionName ] = {
			actionIntercept: null,
			callback,
		};
	}

	useEffect( () => {
		// When a new callback is provided, update the stored dynamic callback
		functionOverrides[ storeName ][ actionName ].callback = callback;
	}, [ storeName, actionName, callback ] );

	use( registry => ( {
		dispatch: namespace => {
			const namespaceName = typeof namespace === 'string' ? namespace : namespace.name;
			const actions = { ...registry.dispatch( namespaceName ) };

			if ( namespaceName !== storeName ) {
				// This is an unrelated registry namespace, return original actions
				return actions;
			}

			if ( ! functionOverrides[ storeName ][ actionName ].actionIntercept ) {
				// Create new function to override actionIntercept, just one time to avoid overrides
				const originalAction = actions[ actionName ];

				functionOverrides[ storeName ][ actionName ].actionIntercept = ( ...args ) => {
					// Call the actual provided callback internally
					functionOverrides[ storeName ][ actionName ].callback( originalAction, args );
				};
			}

			// Add our override into the dispatch registry
			actions[ actionName ] = functionOverrides[ storeName ][ actionName ].actionIntercept;

			return actions;
		},
	} ) );
}
