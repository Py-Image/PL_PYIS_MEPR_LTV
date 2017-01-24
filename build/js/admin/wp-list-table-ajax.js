( function( $ ) {

	var pyisAjaxListTable = {
		
		/**
		 * [[Description]]
		 */
		init: function() {

			var timer,
				delay = 500;

			// Pagination links, sortable link
			$( '.tablenav-pages a, .manage-column.sortable a, .manage-column.sorted a' ).on( 'click', function( event ) {
				
				event.preventDefault();
				
				// Grab variables from the URL
				var query = this.search.substring( 1 );

				// Get the value for each variable
				var data = {
					paged: pyisAjaxListTable._query( query, 'paged' ) || '1',
					order: pyisAjaxListTable._query( query, 'order' ) || 'asc',
					orderby: pyisAjaxListTable._query( query, 'orderby' ) || 'title'
				};
				
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
					paged: parseInt( $('input[name="paged"]').val() ) || '1',
					order: $('input[name="order"]').val() || 'asc',
					orderby: $('input[name="orderby"]').val() || 'title'
				};

				// Wait a bit to ensure the user is done typing before actually sending data
				window.clearTimeout( timer );
				timer = window.setTimeout(function() {
					pyisAjaxListTable.update( data );
				}, delay);
				
			} );
			
		},
		
		/**
		 * [[Description]]
		 * @param {object} data [[Description]]
		 */
		update: function( data ) {
			
			data._ajax_nonce = $('#_ajax_custom_list_nonce').val();
			data.action = 'pyis_mepr_ltv_list';
			
			$.ajax( {
				method: 'POST',
				url: ajaxurl,
				data: data,
				success: function( response ) {

					// WP_List_Table::ajax_response() returns json
					var response = $.parseJSON( response );

					// Add the requested rows
					if ( response.rows.length ) {
						$('#the-list').html( response.rows );
					}
					
					// Update column headers for sorting
					if ( response.column_headers.length ) {
						$('thead tr, tfoot tr').html( response.column_headers );
					}
					
					// Update pagination for navigation
					if ( response.pagination.bottom.length ) {
						$('.tablenav.top .tablenav-pages').html( $(response.pagination.top).html() );
					}
					
					if ( response.pagination.top.length ) {
						$('.tablenav.bottom .tablenav-pages').html( $(response.pagination.bottom).html() );
					}

					// Init back our event handlers
					pyisAjaxListTable.init();
					
				}
				
			} );
			
		},
		
		/**
		 * [[Description]]
		 * @param   {string}   query    [[Description]]
		 * @param   {[[Type]]} variable [[Description]]
		 * @returns {boolean}  [[Description]]
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
		
	}

	pyisAjaxListTable.init();

} )( jQuery );