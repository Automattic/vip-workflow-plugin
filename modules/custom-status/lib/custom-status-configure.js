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

// ToDo: Ensure the prior jQuery edit functionality is replicated in React

// ( function ( $ ) {
// 	const inlineEditCustomStatus = {
// 		init() {
// 			const t = this;
// 			const row = $( '#inline-edit' );

// 			t.what = '#term-';

// 			$( document ).on( 'click', '.editinline', function () {
// 				inlineEditCustomStatus.edit( this );
// 				return false;
// 			} );

// 			// prepare the edit row
// 			row.on( 'keyup', function ( e ) {
// 				if ( e.which == 27 ) {
// 					return inlineEditCustomStatus.revert();
// 				}
// 			} );

// 			$( 'a.cancel', row ).on( 'click', function () {
// 				return inlineEditCustomStatus.revert();
// 			} );
// 			$( 'a.save', row ).on( 'click', function () {
// 				return inlineEditCustomStatus.save( this );
// 			} );
// 			$( 'input, select', row ).on( 'keydown', function ( e ) {
// 				if ( e.which == 13 ) {
// 					return inlineEditCustomStatus.save( this );
// 				}
// 			} );
// 		},

// 		toggle( el ) {
// 			const t = this;
// 			$( t.what + t.getId( el ) ).css( 'display' ) == 'none' ? t.revert() : t.edit( el );
// 		},

// 		edit( id ) {
// 			const t = this;
// 			t.revert();

// 			if ( typeof id === 'object' ) {
// 				id = t.getId( id );
// 			}

// 			let editRow = $( '#inline-edit' ).clone( true );
// 			let rowData = $( '#inline_' + id );

// 			$( 'td', editRow ).attr( 'colspan', $( '.widefat:first thead th:visible' ).length );

// 			if ( $( t.what + id ).hasClass( 'alternate' ) ) {
// 				$( editRow ).addClass( 'alternate' );
// 			}

// 			$( t.what + id )
// 				.hide()
// 				.after( editRow );

// 			const name_text = $( '.name', rowData ).text();
// 			$( ':input[name="name"]', editRow ).val( name_text );
// 			$( ':input[name="description"]', editRow ).val( $( '.description', rowData ).text() );

// 			$( editRow )
// 				.attr( 'id', 'edit-' + id )
// 				.addClass( 'inline-editor' )
// 				.show();

// 			const $name_field = $( '.ptitle', editRow ).eq( 0 );
// 			if ( 'draft' === name_text.trim().toLowerCase() ) {
// 				$name_field.attr( 'readonly', 'readonly' );
// 			} else {
// 				$name_field.focus();
// 			}

// 			return false;
// 		},

// 		save( id ) {
// 			let params;
// 			let fields;
// 			const tax = $( 'input[name="taxonomy"]' ).val() || '';

// 			if ( typeof id === 'object' ) {
// 				id = this.getId( id );
// 			}

// 			$( 'table.widefat .inline-edit-save .waiting' ).show();

// 			params = {
// 				action: 'inline_save_status',
// 				status_id: id,
// 			};

// 			fields = $( '#edit-' + id + ' :input' ).serialize();
// 			params = fields + '&' + $.param( params );

// 			// make ajax request
// 			$.post( ajaxurl, params, function ( r ) {
// 				let row;
// 				let new_id;
// 				$( 'table.widefat .inline-edit-save .waiting' ).hide();

// 				if ( r ) {
// 					if ( -1 != r.indexOf( '<tr' ) ) {
// 						$( inlineEditCustomStatus.what + id ).remove();
// 						new_id = $( r ).attr( 'id' );

// 						$( '#edit-' + id )
// 							.before( r )
// 							.remove();
// 						row = new_id ? $( '#' + new_id ) : $( inlineEditCustomStatus.what + id );
// 						row.hide().fadeIn();
// 					} else {
// 						$( '#edit-' + id + ' .inline-edit-save .error' )
// 							.html( r )
// 							.show();
// 					}
// 				} else {
// 					$( '#edit-' + id + ' .inline-edit-save .error' )
// 						.html( inlineEditL10n.error )
// 						.show();
// 				}
// 			} );
// 			return false;
// 		},

// 		revert() {
// 			let id = $( 'table.widefat tr.inline-editor' ).attr( 'id' );

// 			if ( id ) {
// 				$( 'table.widefat .inline-edit-save .waiting' ).hide();
// 				$( '#' + id ).remove();
// 				id = id.substr( id.lastIndexOf( '-' ) + 1 );
// 				$( this.what + id ).show();
// 			}

// 			return false;
// 		},

// 		getId( o ) {
// 			const id = o.tagName == 'TR' ? o.id : $( o ).parents( 'tr' ).attr( 'id' );
// 			const parts = id.split( '-' );
// 			return parts[ parts.length - 1 ];
// 		},
// 	};

// 	$( document ).ready( function () {
// 		inlineEditCustomStatus.init();
// 	} );
// } )( jQuery );

// jQuery( document ).ready( function () {
// 	jQuery( '.delete-status a' ).on( 'click', function () {
// 		if ( ! confirm( VW_CUSTOM_STATUS_CONFIGURE.delete_status_string ) ) {
// 			return false;
// 		}
// 	} );
// } );
