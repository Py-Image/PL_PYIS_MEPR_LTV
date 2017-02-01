( function( $ ) {

	if ( ! location.origin )
		location.origin = location.protocol + '//' + location.host;

	var pyisAjaxListTable = {

		/**
		 * Attach Events to the List Table for controling the Query
		 * 
		 * @since		1.0.0
		 * @return		void
		 */
		init: function() {

			var timer,
				delay = 500;

			// Pagination links, sortable link
			$( '.tablenav-pages a, .manage-column.sortable a, .manage-column.sorted a, .search-box input[type="submit"]' ).on( 'click', function( event ) {

				event.preventDefault();
				
				if ( typeof this.search !== 'undefined' ) {

					// Grab variables from the URL
					var query = this.search.substring( 1 );
					
				}
				else {
					var query = '';
				}

				// Get the value for each variable
				var data = {
					paged: pyisAjaxListTable._query( query, 'paged' ) || $( 'input[name="paged"]' ).val(),
					order: pyisAjaxListTable._query( query, 'order' ) || $( 'input[name="order"]' ).val(),
					orderby: pyisAjaxListTable._query( query, 'orderby' ) || $( 'input[name="orderby"]' ).val(),
					s: pyisAjaxListTable._query( query, 's' ) || $( '.search-box input[name="s"]' ).val(),
				};

				$( 'input[name="paged"]' ).val( data.paged );
				$( 'input[name="order"]' ).val( data.order );
				$( 'input[name="orderby"]' ).val( data.orderby );

				var urlQuery = document.location.search;
				
				urlQuery = pyisAjaxListTable._update_url( urlQuery, 'paged', data.paged );
				urlQuery = pyisAjaxListTable._update_url( urlQuery, 'order', data.order );
				urlQuery = pyisAjaxListTable._update_url( urlQuery, 'orderby', data.orderby );
				urlQuery = pyisAjaxListTable._update_url( urlQuery, 's', data.s );
				
				// Allows us to update the URL if the browser supports it.
				// If not, we still have those hidden inputs as a fallback
     			history.replaceState( undefined, undefined, urlQuery );

				// Update the table
				pyisAjaxListTable.update( data );

			} );

			// Page number input
			$( 'input[name=paged]' ).on( 'keyup', function( event ) {

				// If user hit enter, we don't want to submit the form
				if ( event.which == 13 ) {
					event.preventDefault();
				}

				// This time we fetch the variables in inputs
				var data = {
					paged: parseInt( $( 'input[name="paged"]' ).val() ) || '1',
					order: $( 'input[name="order"]' ).val() || 'asc',
					orderby: $( 'input[name="orderby"]' ).val() || 'title',
					s: $( '.search-box input[name="s"]' ).val() || '',
				};

				// Wait a bit to ensure the user is done typing before actually sending data
				window.clearTimeout( timer );
				timer = window.setTimeout( function() {
					pyisAjaxListTable.update( data );
				}, delay );

			} );
			
			$( '.flush-transients' ).on( 'click', function( event ) {
				
				event.preventDefault();
				
				pyisAjaxListTable.refresh();
				
			} );

		},

		/**
		 * Update the List Table via AJAX
		 * 
		 * @param		{object} data Data Object to send via AJAX
		 *                       
		 * @since		1.0.0
		 * @return		void
		 */
		update: function( data ) {

			data._ajax_nonce = $( '#_pyis_mepr_ltv_nonce' ).val();
			data.action = 'pyis_mepr_ltv_list';

			$.ajax( {
				type: 'POST',
				url: location.origin + ajaxurl,
				data: data,
				success: function( response ) {
					
					response = response.data;

					// Add the requested rows
					if ( response.rows.length ) {
						$( '#the-list' ).html( response.rows );
					}

					// Update column headers for sorting
					if ( response.column_headers.length ) {
						$( 'thead tr, tfoot tr' ).html( response.column_headers );
					}

					// Update pagination for navigation
					if ( response.pagination.bottom.length ) {
						$( '.tablenav.top .tablenav-pages' ).html( $( response.pagination.top ).html() );
					}

					if ( response.pagination.top.length ) {
						$( '.tablenav.bottom .tablenav-pages' ).html( $( response.pagination.bottom ).html() );
					}

					// Init back our event handlers
					pyisAjaxListTable.init();

				},
				error : function( request, status, error ) {
					console.error( request.responseText );
					console.error( error );
				}

			} );

		},

		/**
		 * Grab Query Parameters from the clicked-on Element
		 * 
		 * @param		{string}         query    URL
		 * @param 		{string}         variable Parameter we're checking
		 *                                    
		 * @since		1.0.0
		 * @returns 	{string|boolean} Value on success, false on failure
		 */
		_query: function( query, variable ) {

			var vars = query.split( '&' );

			for ( var i = 0; i < vars.length; i++ ) {

				var pair = vars[ i ].split( '=' );

				if ( pair[0] == variable ) {
					return pair[1];
				}

			}

			return false;

		},

		/**
		 * Allows updating the Query String in the URL in case we'd like to link to it
		 * 
		 * @param		{string} url   URL
		 * @param 		{string} key   Key
		 * @param 		{string} value Value
		 *                         
		 * @since		1.0.0
		 * @returns 	{string} Updated URL
		 */
		_update_url: function( url, key, value ) {
			
			// remove the hash part before operating on the url
			var hashIndex = url.indexOf( '#' );
			var hash = hashIndex === -1 ? ''  : url.substr( hashIndex );
			url = hashIndex === -1 ? url : url.substr( 0, hashIndex );

			var re = new RegExp( "([?&])" + key + "=.*?(&|$)", "i" );
			
			var separator = url.indexOf( '?' ) !== -1 ? "&" : "?";
			
			if ( url.match( re ) && value !== '' ) {
				url = url.replace( re, '$1' + key + "=" + value + '$2' );
			}
			else if ( value == '' ) {
				url = url.replace( re, '' );
			}
			else {
				url = url + separator + key + "=" + value;
			}
			
			return url + hash;
			
		},
		
		/**
		 * Clear out the Transient and Update the Table
		 * 
		 * @since		1.0.0
		 * @return		void
		 */
		refresh: function() {
			
			var data = {
				_ajax_nonce: $( '#_pyis_mepr_ltv_nonce' ).val(),
				action: 'pyis_mepr_ltv_flush',
			};
			
			$.ajax( {
				type: 'POST',
				url: location.origin + ajaxurl,
				data: data,
				success: function( response ) {
					
					response = response.data;
					
					$( '.transient-expiration' ).html( response.expiration );

				},
				error : function( request, status, error ) {
					console.error( request.responseText );
					console.error( error );
				}

			} );
			
			// Ensure that the current view is preserved
			data.paged = $( 'input[name="paged"]' ).val();
			data.order = $( 'input[name="order"]' ).val();
			data.orderby = $( 'input[name="orderby"]' ).val();
			data.s = $( '.search-box input[name="s"]' ).val();
			
			// Update using the refreshed data
			pyisAjaxListTable.update( data );
			
		}

	}

	pyisAjaxListTable.init();

} )( jQuery );