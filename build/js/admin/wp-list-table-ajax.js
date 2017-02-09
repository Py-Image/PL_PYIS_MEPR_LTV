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
			$( '.tablenav-pages a, .manage-column.sortable a, .manage-column.sorted a' ).on( 'click', function( event ) {

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
					event: 'sort',
					paged: pyisAjaxListTable._query( query, 'paged' ) || $( 'input[name="paged"]' ).val(),
					order: pyisAjaxListTable._query( query, 'order' ) || $( 'input[name="order"]' ).val(),
					orderby: pyisAjaxListTable._query( query, 'orderby' ) || $( 'input[name="orderby"]' ).val(),
					s: pyisAjaxListTable._query( query, 's' ) || $( '.search-box input[name="s"]' ).val(),
				};
				
				// In this case, we send a different event
				if ( $( this ).parent().hasClass( 'pagination-links' ) ) {
					data.event = 'paginate';
				}
				
				// Reset to page one if we're intentionally navigating to the first page
				if ( $( this ).hasClass( 'first-page' ) ) {
					data.paged = 1;
				}

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
					event: 'typed_page',
					paged: parseInt( $( this ).val() ) || 1,
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
			
			// Define the values of these hidden fields based on the Data so different actions can default to them
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

			$.ajax( {
				type: 'POST',
				url: location.origin + ajaxurl,
				data: data,
				success: function( response ) {
					
					if ( response.success && 
						response.hasOwnProperty( 'data' ) ) {
					
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
						
						// This bit is directly from MemberPress, but we need to reattach the event after recreating the Table
						$( 'table.wp-list-table tr' ).hover(
							function( event ) {
								$( this ).find( '.mepr-row-actions' ).css( 'visibility', 'visible' );
							},
							function( event ) {
								$( this ).find( '.mepr-row-actions' ).css( 'visibility', 'hidden' );
							}
						);

						// Init back our event handlers
						pyisAjaxListTable.init();
						
					}

				},
				error: function( request, status, error ) {
					
					console.error( request.responseText );
					console.error( error );
					
				}

			} )
			.done( function( response ) {
				
				if ( response.success && 
					response.hasOwnProperty( 'data' ) ) {
					
					if ( data.event == 'sort' ) {
					
						$( '.column-' + data.orderby ).effect( 'highlight', { color : '#DFF2BF' }, 1000 );
						
					}
					else {
						
						var $table = $( '.wp-list-table' );
						
						// Ensure the highlight can be seen on each row
						$table.removeClass( 'striped' );
						
						$table.find( 'tbody#the-list' ).effect( 'highlight', { color : '#DFF2BF' }, 1000, function() {
							$table.addClass( 'striped' );
						} );
						
					}
					
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
		refresh: function( event ) {
			
			var button = event.currentTarget,
				defaultText = $( button ).val();
			
			$( button ).val( pyisMeprLtv.i18n.flushProcessing );
			
			var data = {
				_ajax_nonce: $( '#_pyis_mepr_ltv_nonce' ).val(),
				action: 'pyis_mepr_ltv_flush',
			};
			
			$.ajax( {
				type: 'POST',
				url: location.origin + ajaxurl,
				data: data,
				success: function( response ) {
					
					if ( response.success && 
						response.hasOwnProperty( 'data' ) ) {
					
						response = response.data;
					
						$( '.transient-expiration' ).html( response.expiration );
						
					}

				},
				error : function( request, status, error ) {
					console.error( request.responseText );
					console.error( error );
				}

			} )
			.done( function( response ) {
				
				if ( response.success === true && 
					response.hasOwnProperty( 'data' ) ) {
					
					$( button ).val( pyisMeprLtv.i18n.flushSuccess );
					
					$( '.transient-expiration' ).effect( 'highlight', { color : '#DFF2BF' }, 1000 );
					
					setTimeout( function() {
						$( button ).val( pyisMeprLtv.i18n.flushDefault );
					}, 1000 );
					
				}
				else {
					
					$( button ).val( pyisMeprLtv.i18n.flushError );
					
				}
				
			} );
			
			// Ensure that the current view is preserved
			data.paged = $( 'input[name="paged"]' ).val();
			data.order = $( 'input[name="order"]' ).val();
			data.orderby = $( 'input[name="orderby"]' ).val();
			data.s = $( '.search-box input[name="s"]' ).val();
			data.event = 'flush';
			
			// Update using the refreshed data
			pyisAjaxListTable.update( data );
			
		}

	}

	// Bind events for all items that get removed on Table Redraws
	pyisAjaxListTable.init();
			
	/**
	 * Search for specific Users by Name, Login, or Email
	 * This Event needs to be bound outside of the init() function otherwise it will get rebound constantly
	 * 
	 * @since		1.0.0
	 * @return		void
	 */
	$( '#pyis-mepr-ltv-table' ).on( 'submit', function( event ) {

		event.preventDefault();

		// This time we fetch the variables in inputs
		// Except we always go to Page 1
		var data = {
			event: 'search',
			paged: 1,
			order: $( 'input[name="order"]' ).val() || 'asc',
			orderby: $( 'input[name="orderby"]' ).val() || 'title',
			s: $( '.search-box input[name="s"]' ).val() || '',
		};

		// Update the table
		pyisAjaxListTable.update( data );

	} );
	
	/** 
	 * Clear the Transient Data and refresh the Table
	 * This Event needs to be bound outside of the init() function otherwise it will get rebound constantly
	 * 
	 * @since		1.0.0
	 * @return		void
	 */
	$( '.flush-transients.button' ).on( 'click', function( event ) {

		event.preventDefault();

		pyisAjaxListTable.refresh( event );

	} );

} )( jQuery );