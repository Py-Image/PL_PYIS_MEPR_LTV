<?php
/**
 * The AJAX-ified WP_List_Table
 *
 * @since 1.0.0
 *
 * @package PYIS_MEPR_LTV
 * @subpackage PYIS_MEPR_LTV/core/includes
 */

defined( 'ABSPATH' ) || die();

if ( ! class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

/* Hide notices to avoid AJAX errors
 * Sometimes the Class throws a notice about 'hook_suffix' being undefined,
 * which breaks every AJAX call.
 */
//error_reporting( ~E_NOTICE );
// Just defining it like this for now to get around the error.
$GLOBALS['hook_suffix'] = 'pyis-mepr-ltv';

class PYIS_MEPR_LTV_List_Table extends WP_List_Table {
	
	public $query = array();

	function __construct() {

		global $status, $page;
		
		//Set parent defaults
		parent::__construct(
			array(
				'singular'	=> _x( 'User', 'Singular Name of Listed Record', PYIS_MEPR_LTV_ID ),
				'plural'	=> _x( 'Users', 'Plural Name of Listed Record', PYIS_MEPR_LTV_ID ),
				'ajax'		=> true
			)
		);
		
	}
	
	/**
	 * Output the data within a Table Cell
	 * 
	 * @param		object $item        A singular item (one full row's worth of data)
	 * @param		string $column_name The name/slug of the column to be processed
	 *                                                               
	 * @access		public
	 * @since		1.0.0
	 * @return		string Text or HTML to be placed inside the column <td>
	 */
	public function column_default( $item, $column_name ) {

		switch ( $column_name ) {

			case 'last_name' :
				return $item->first_name . ' ' . $item->$column_name;
			case 'user_login' :
			case 'user_email' :
				return $item->$column_name;
			case 'user_registered' :
			case 'last_billed' :
			case 'next_billed' :
				return date_i18n( get_option( 'date_format' ), strtotime( $item->$column_name ) );
			case 'transactions' :
				
				echo '<ul style="margin-top:0">';
				foreach( $item->$column_name as $product ) {
					
					echo '<li>' . $product['name'] . '<ul style="list-style: disc; margin-left: 1.5em;">';
					
					foreach ( $product['transactions'] as $transaction_id => $transaction_number ) {
						echo '<li>';
							echo '<a href="' . admin_url( 'admin.php?page=memberpress-trans&action=edit&id=' . $transaction_id ) . '" title="' . _x( 'Edit/View Transaction', 'Edit/View Transaction Link Title', PYIS_MEPR_LTV_ID ) . '">' . $transaction_number . '</a>';
						echo '</li>';
					}
					
					echo '</ul></li>';
					
				}
				echo '</ul>';
				
				return false;
				
			case 'ltv' : 
				return MeprAppHelper::format_currency( $item->$column_name, true );
			default :
				// Show the passed value as what it truly is
				echo '<pre>';
				var_dump( $item->$column_name );
				echo '</pre>';
				return false;
				
		}
		
	}
	
	/**
	 * Defines the Headers for each Column
	 * 
	 * @access		public
	 * @since		1.0.0
	 * @return		array An associative array containing column information: 'slug' => 'Visible Title'
	 */
	public function get_columns() {

		return $columns = array(
			'last_name' => _x( 'Full Name', 'Full Name Column Header', PYIS_MEPR_LTV_ID ),
			'user_login' => _x( 'User Login', 'User Login Column Header', PYIS_MEPR_LTV_ID ),
			'user_email' => _x( 'Email Address', 'Email Address Column Header', PYIS_MEPR_LTV_ID ),
			'user_registered' => _X( 'Joined', 'Joined Column Header', PYIS_MEPR_LTV_ID ),
			'transactions' => _x( 'Completed Transactions', 'Completed Transactions Column Header', PYIS_MEPR_LTV_ID ),
			'last_billed' => _x( 'Last Billed', 'Last Billed Column Header', PYIS_MEPR_LTV_ID ),
			'next_billed' => _x( 'Next Billed', 'Next Billed Column Header', PYIS_MEPR_LTV_ID ),
			'ltv' => _x( 'LTV', 'LTV Column Header', PYIS_MEPR_LTV_ID ),
		);
		
	}

	/**
	 * Optional. If you want one or more columns to be sortable (ASC/DESC toggle), 
	 * you will need to register it here. This should return an array where the 
	 * key is the column that needs to be sortable, and the value is db column to 
	 * sort by. Often, the key and value will be the same, but this is not always
	 * the case (as the value is a column name from the database, not the list table).
	 * 
	 * This method merely defines which columns should be sortable and makes them
	 * clickable - it does not handle the actual sorting. You still need to detect
	 * the ORDERBY and ORDER querystring variables within prepare_items() and sort
	 * your data accordingly (usually by modifying your query).
	 * 
	 * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
	 */
	public function get_sortable_columns() {
		
		$sortable_columns = array(
			'last_name',
			'user_login',
			'user_email',
			'user_registered',
			'last_billed',
			'next_billed',
			'ltv',
		);
		
		$result = array(); // After determining the correct values, this will be populated
		foreach ( $sortable_columns as $column ) {
			
			if ( isset( $_REQUEST['orderby'] ) && 
				strtolower( $_REQUEST['orderby'] ) == $column && 
				isset( $_REQUEST['order'] ) && 
				strtolower( $_REQUEST['order'] ) == 'asc' ) {
				
				$result[ $column ] = array( $column, true );
				
			}
			else {
				$result[ $column ] = array( $column, false );
			}
			
			// If we aren't ordering by anything else, default to Last Name
			if ( ! isset( $_REQUEST['orderby'] ) ) {
				$result['last_name'] = array( 'last_name', true );
			}
			
		}

		return $result;
		
	}
	
	/**
	 * Queries the Database and sets up everything that WP_List_Table expects to find
	 * 
	 * @access		public
	 * @since		1.0.0
	 * @return		void
	 */
	public function prepare_items() {

		global $wpdb; //This is used only if making any database queries

		/**
		 * First, lets decide how many records per page to show
		 */
		$per_page = apply_filters( 'pyis_mepr_ltv_per_page', 15 );
		
		/**
		 * Set up the Columns and their Headers
		 */
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		
		$this->_column_headers = array( $columns, $hidden, $sortable );
			
		$data = $this->get_data();
		
		if ( isset( $_REQUEST['orderby'] ) ) {
			
			// If we're ordering by LTV
			if ( $_REQUEST['orderby'] == 'ltv' ) {
				usort( $data, array( $this, 'usort_numeric' ) );
			}
			else if ( $_REQUEST['orderby'] == 'user_registered' ||
					$_REQUEST['orderby'] == 'last_billed' ||
					$_REQUEST['orderby'] == 'next_billed' ) { // If we're ordering by a Date
				usort( $data, array( $this, 'usort_date' ) );
			}
			else {
				usort( $data, array( $this, 'usort_apha' ) );
			}
			
		}
		
		// If we're searching for specific Users
		if ( ( isset( $_REQUEST['s'] ) ) && ( $_REQUEST['s'] !== '' ) ) {
			
			$data = array_filter( $data, array( $this, 'search_users' ) );
			
		}
				
		/**
		 * Determine Current Page
		 */
		$current_page = $this->get_pagenum();
		
		/**
		 * Determine total
		 */
		$total_items = count( $data );
		
		/**
		 * Trim the data to only the current page
		 * Unfortunately due to how we need to grab the data via AJAX, we need to grab it all at once
		 * This also helps when we sort by LTV, since LTV isn't User Data.
		 */
		$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );
		
		/**
		 * Set the Sorted Data to the items Member
		 */
		$this->items = $data;
		
		/**
		 * All of this data needs to also be set for WP_List_Table to be happy
		 */
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page' => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
				'orderby' => ! empty( $_REQUEST['orderby'] ) && '' != $_REQUEST['orderby'] ? $_REQUEST['orderby'] : 'last_name',
				'order' => ! empty( $_REQUEST['order'] ) && '' != $_REQUEST['order'] ? $_REQUEST['order'] : 'asc'
			)
		);
		
	}

	/**
	 * Create the initial Table display
	 *
	 * @access		public
	 * @since		1.0.0
	 * @return		HTML
	 */
	public function display() {
		
		$this->prepare_items();
		
		?>
		
		<form id="pyis-mepr-ltv-table" method="get">
			<input type="hidden" name="paged" value="<?php echo isset( $_REQUEST['paged'] ) ? $_REQUEST['paged'] : '1'; ?>" />
			<input type="hidden" id="order" name="order" value="<?php echo $this->_pagination_args['order']; ?>" />
			<input type="hidden" id="orderby" name="orderby" value="<?php echo $this->_pagination_args['orderby']; ?>" />
			<?php
				wp_nonce_field( 'pyis-mepr-ltv-nonce', '_pyis_mepr_ltv_nonce' );
				$this->search_box( sprintf( 'Search %s', ucwords( $this->_args['plural'] ) ), 'ltv_search' );
				parent::display();
			?>
		</form>
		<br class="clear" />

		<?php
		
	}

	/**
	 * Handle all AJAX Requests
	 *
	 * @access		public
	 * @since		1.0.0
	 * @return		string JSON of all our List Table Elements as HTML
	 */
	public function ajax_response() {
		
		check_ajax_referer( 'pyis-mepr-ltv-nonce', '_pyis_mepr_ltv_nonce' );

		$this->prepare_items();
		
		$total_items = $this->_pagination_args['total_items'];
		$total_pages = $this->_pagination_args['total_pages'];

		ob_start();
		if ( ! empty( $_REQUEST['no_placeholder'] ) )
			$this->display_rows();
		else
			$this->display_rows_or_placeholder();
		$rows = ob_get_clean();

		ob_start();
		$this->print_column_headers();
		$headers = ob_get_clean();

		ob_start();
		$this->pagination( 'top' );
		$pagination_top = ob_get_clean();

		ob_start();
		$this->pagination( 'bottom' );
		$pagination_bottom = ob_get_clean();

		$response = array( 'rows' => $rows );
		$response['pagination']['top'] = $pagination_top;
		$response['pagination']['bottom'] = $pagination_bottom;
		$response['column_headers'] = $headers;

		if ( isset( $total_items ) )
			$response['total_items_i18n'] = sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) );

		if ( isset( $total_pages ) ) {
			$response['total_pages'] = $total_pages;
			$response['total_pages_i18n'] = number_format_i18n( $total_pages );
		}

		wp_send_json_success( $response );
		
	}
	
	/**
	 * Data grabbing function abstracted out to be accessible directly from the Object
	 * 
	 * @access		public
	 * @since		1.0.0
	 * @return		array Array of (modified) WP_User Objects
	 */
	public function get_data() {
		
		if ( ! $data = get_transient( 'pyis_mepr_ltv_data' ) ) {
			$data = $this->query();
			set_transient( 'pyis_mepr_ltv_data', $data, WEEK_IN_SECONDS );
		}
		
		return $data;
		
	}
	
	/**
	 * Handles the actual Query
	 * 
	 * @acess		private
	 * @since		1.0.0
	 * @return		array Array of (modified) WP_User Objects
	 */
	private function query() {
		
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'mepr_transactions';
		
		$query = "
		SELECT DISTINCT(user_id)
		FROM $table_name
		WHERE status = 'complete'";
		
		// Grab a list of User IDs in the Transactions Table. This lets us narrow things down in a User Query
		$has_transactions = $wpdb->get_results( $query, ARRAY_N );
		
		// We want a flat Array of just the User IDs
		$has_transactions = array_map( array( $this,  'extract_user_id' ), $has_transactions );
		
		$args = array (
            'order' => 'ASC',
			'meta_key' => 'last_name',
			'orderby' => 'meta_value',
			'include' => $has_transactions,
			'meta_query' => array(
				'relation' => 'AND', // Based on $_REQUEST, we tack onto this with successive rules that must all be TRUE
				array(
					'relation' => 'OR', // In order to query two Roles with wp_user_query() you need to use a Meta Query. Not very intuitive.
					array( 
						'key' => $wpdb->prefix . 'capabilities',
						'value' => 'subscriber',
						'compare' => 'LIKE',
					),
					array(
						'relation' => 'AND',
						array(
							'key' => $wpdb->prefix . 'capabilities',
							'value' => 'administrator',
							'compare' => 'LIKE',
						),
						array(
							'key' => 'first_name',
							'value' => 'Adrian',
							'compare' => 'LIKE',
						),
						array(
							'key' => 'last_name',
							'value' => 'Rosebrock',
							'compare' => 'LIKE',
						)
					),
				),
            ),
			'fields' => array(
				'ID',
				'user_login',
				'user_email',
				'user_registered',
			),
		);
		
		$orderby = ( isset( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'last_name';
		
		// WP can automagically handle ordering my Last Name, which is User Meta, for us.
		if ( $orderby == 'last_name' ) {
			$args['meta_key'] = $orderby;
			$args['orderby'] = 'meta_value';
		}
		else {
			$args['orderby'] = $orderby;
		}
		
		$user_query = new WP_User_Query( $args );
		
		$results = $user_query->get_results();
		
		foreach ( $results as $user ) {
			
			$transactions = $this->completed_transactions_by_user_id( $user->ID );
			
			$transaction_list = array();
			$ltv = 0;
			$last_billed = '1970-01-01'; // Unix Epoch
			$today = date( 'Y-m-d', current_time( 'timestamp' ) );
			$next_billed = $today; // We only want the soonest next billed date, so we compare to today.
			foreach ( $transactions as $transaction ) {
				
				if ( ! isset( $transaction_list[ $transaction->rec->product_id ] ) ) {
				
					$transaction_list[ $transaction->rec->product_id ]['name'] = get_the_title( $transaction->rec->product_id );
						
				}
				
				$transaction_list[ $transaction->rec->product_id ]['transactions'][ $transaction->rec->id ] = $transaction->rec->trans_num;
				
				$created_at = date( 'Y-m-d', strtotime( $transaction->rec->created_at ) );
				if ( $created_at > $last_billed ) {
					$last_billed = $created_at;
				}
				
				$expires_at = date( 'Y-m-d', strtotime( $transaction->rec->expires_at ) );
				if ( $expires_at > $next_billed && 
				   $next_billed == $today ) {
					$next_billed = $expires_at;
				}
				
				$ltv += $transaction->rec->total;
				
			}
			
			$user->first_name = get_user_meta( $user->ID, 'first_name', true );
			$user->last_name = get_user_meta( $user->ID, 'last_name', true );
			$user->transactions = $transaction_list;
			$user->last_billed = $last_billed;
			$user->next_billed = $next_billed;
			$user->ltv = $ltv;
			
		}
	
		return $results;
		
	}
	
	/**
	 * Array Map Callback needs to be a Class Method to prevent Function Redeclaration Errors
	 * 
	 * @param		array $array Input Array
	 *                   
	 * @access		public
	 * @since		1.0.0
	 * @return		array Extract out the inner Array's only value
	 */
	public function extract_user_id( $array ) {

		$reset = reset( $array );

		return $reset;

	}
	
	/**
	 * MemberPress is so weird about this stuff
	 * 
	 * @param		integer $user_id User ID
	 *                               
	 * @access		public
	 * @since		1.0.0
	 * @return		array   Array of Transactions that are Complete
	 */
	public function completed_transactions_by_user_id( $user_id ) {
		
		$transactions = MeprTransaction::get_all_objects_by_user_id( $user_id );
		
		$transactions = array_filter( $transactions, array( $this, 'completed_transactions_only' ) );
		
		return $transactions;
		
	}
	
	/**
	 * Array Filter callback needs to be a Class Method to prevent Function Redclaration Errors
	 * 
	 * @param		object $object Transaction Object
	 *                                    
	 * @access		public
	 * @since		1.0.0
	 * @return		array  Filtered Transactions Array
	 */
	public function completed_transactions_only( $object ) {
		
		if ( $object->status !== 'complete' ) return false;
			
		return true;
		
	}
	
	/**
	 * Sort the resulting Array of Objects Numerically
	 * 
	 * @param		object  $a WP_User Object with Transactions and LTV added
	 * @param		object  $b WP_User Object with Transactions and LTV added
	 *                                                             
	 * @access		public
	 * @since		1.0.0
	 * @return		integer Whether to move forward or backward in the Stack
	 */
	public function usort_numeric( $a, $b ) {
		
		// If no order, default to asc
		$order = ( isset( $_REQUEST['order'] ) && ! empty( $_REQUEST['order'] ) ) ? strtolower( trim( $_REQUEST['order'] ) ) : 'asc';
		
		// Default to LTV
		$orderby = ( isset( $_REQUEST['orderby'] ) && ! empty( $_REQUEST['orderby'] ) ) ? strtolower( trim( $_REQUEST['orderby'] ) ) : 'ltv';
		
		if ( $a->$orderby == 0 && $b->$orderby !== 0 ) {
			$result = 1;
		}
		else if ( $a->$orderby !== 0 && $b->$orderby == 0 ) {
			$result = -1;
		}
		else if ( $a->$orderby == $b->$orderby ) {
			$result = 0;
		}
		else {
			$result = ( $a->$orderby > $b->$orderby ) ? -1 : 1;
		}
		
		if ( $order == 'asc' ) {
			return $result;
		}
		else {
			return -$result;
		}
		
	}
	
	/**
	 * Sort the resulting Array of Objects Alphabetically
	 * 
	 * @param		object  $a WP_User Object with Transactions and LTV added
	 * @param		object  $b WP_User Object with Transactions and LTV added
	 *                                                             
	 * @access		public
	 * @since		1.0.0
	 * @return		integer Whether to move forward or backward in the Stack
	 */
	public function usort_apha( $a, $b ) {
		
		// If no order, default to asc
		$order = ( isset( $_REQUEST['order'] ) && ! empty( $_REQUEST['order'] ) ) ? strtolower( trim( $_REQUEST['order'] ) ) : 'asc';
		
		// Default to Last Name
		$orderby = ( isset( $_REQUEST['orderby'] ) && ! empty( $_REQUEST['orderby'] ) ) ? strtolower( trim( $_REQUEST['orderby'] ) ) : 'last_name';
		
		$result = strcasecmp( $a->$orderby, $b->$orderby );
		
		if ( $order == 'asc' ) {
			return $result;
		}
		else {
			return -$result;
		}
		
	}
	
	/**
	 * Sort the resulting Array of Objects by Date
	 * 
	 * @param		object  $a WP_User Object with Transactions and LTV added
	 * @param		object  $b WP_User Object with Transactions and LTV added
	 *                                                             
	 * @access		public
	 * @since		1.0.0
	 * @return		integer Whether to move forward or backward in the Stack
	 */
	public function usort_date( $a, $b ) {
		
		// If no order, default to asc
		$order = ( isset( $_REQUEST['order'] ) && ! empty( $_REQUEST['order'] ) ) ? strtolower( trim( $_REQUEST['order'] ) ) : 'asc';
		
		// Default to Registration Date
		$orderby = ( isset( $_REQUEST['orderby'] ) && ! empty( $_REQUEST['orderby'] ) ) ? strtolower( trim( $_REQUEST['orderby'] ) ) : 'user_registered';
		
		// ISO 8610
		// https://xkcd.com/1179/
		$a_value = date( 'Y-m-d', strtotime( $a->$orderby ) );
		$b_value = date( 'Y-m-d', strtotime( $b->$orderby ) );
		
		if ( $a_value == 0 && $b_value !== 0 ) {
			$result = 1;
		}
		else if ( $a_value !== 0 && $b_value == 0 ) {
			$result = -1;
		}
		else if ( $a_value == $b_value ) {
			$result = 0;
		}
		else {
			$result = ( $a_value > $b_value ) ? -1 : 1;
		}
		
		if ( $order == 'asc' ) {
			return $result;
		}
		else {
			return -$result;
		}
		
	}
	
	/**
	 * WP_User_Query isn't as flexible as we need to preform searches on BOTH User Meta and User Data
	 * 
	 * @param		object  $user WP_User Object with Transactions and LTV Added
	 *                                                                
	 * @access		public
	 * @since		1.0.0
	 * @return		boolean Whether to filter out this User or not
	 */
	public function search_users( $user ) {
		
		if ( isset( $_REQUEST['s'] ) &&
			( strpos( strtolower( $user->first_name ), strtolower( trim( $_REQUEST['s'] ) ) ) !== false ||
		    strpos( strtolower( $user->last_name ), strtolower( trim( $_REQUEST['s'] ) ) ) !== false ||
			strpos( strtolower( $user->user_login ), strtolower( trim( $_REQUEST['s'] ) ) ) !== false ||
		    strpos( strtolower( $user->user_email ), strtolower( trim( $_REQUEST['s'] ) ) ) !== false ) ) {
			
			return true;
		
		}
		
		return false;
		
	}
	
	/**
	 * Include our CSS/JavaScript only when the List Table exists
	 * 
	 * @access		public
	 * @since		1.0.0
	 * @return		void
	 */
	public function _js_vars() {
		
		wp_enqueue_style( PYIS_MEPR_LTV_ID . '-admin' );
		
		// Dependencies
		wp_enqueue_script( 'jquery-effects-core' );
		wp_enqueue_script( 'jquery-effects-highlight' );
		
		wp_enqueue_script( PYIS_MEPR_LTV_ID . '-admin' );
		
	}

}