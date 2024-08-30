import { use } from '@wordpress/data';
import { useEffect } from '@wordpress/element';

const functionOverrides = {};

/* eslint-disable security/detect-object-injection */
// This code is called from a Gutenberg plugin context, where provided store and action names are fixed strings.

export default function useInterceptActionDispatch( storeName, actionName, callback ) {
	// Create a new entry to track this function override if it doesn't exist
	if ( ! functionOverrides?.[ storeName ]?.[ actionName ] ) {
		if ( ! functionOverrides?.[ storeName ] ) {
			functionOverrides[ storeName ] = {};
		}

		// Registy override for this action.
		// The purpose of `actionIntercept` is to provide a single unchanging reference to interception logic.
		// Without this, each time useInterceptActionDispatch() is called we will create a new dispatch override and
		// trigger rerenders of components that use dispatch functions as dependencies.
		// `actionIntercept` will be set during dispatch registration after we have access to the original action.
		//
		// The `callback` key is used to store the actual callback function. When this changes we update it a new
		// value with useEffect below and leave actionIntercept unchanged, making dispatch dependencies happy.
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
				// Create new function to override actionIntercept, just once, to avoid rerenders
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
