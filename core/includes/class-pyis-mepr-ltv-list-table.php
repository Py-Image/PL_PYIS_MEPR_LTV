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

	/**
	 * REQUIRED. Set up a constructor that references the parent constructor. We 
	 * use the parent reference to set some default configs.
	 */
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
	 * Recommended. This method is called when the parent class can't find a method
	 * specifically build for a given column. Generally, it's recommended to include
	 * one method for each column you want to render, keeping your package class
	 * neat and organized. For example, if the class needs to process a column
	 * named 'title', it would first see if a method named $this->column_title() 
	 * exists - if it does, that method will be used. If it doesn't, this one will
	 * be used. Generally, you should try to use custom column methods as much as 
	 * possible. 
	 * 
	 * Since we have defined a column_title() method later on, this method doesn't
	 * need to concern itself with any column with a name of 'title'. Instead, it
	 * needs to handle everything else.
	 * 
	 * For more detailed insight into how columns are handled, take a look at 
	 * WP_List_Table::single_row_columns()
	 * 
	 * @param array $item A singular item (one full row's worth of data)
	 * @param array $column_name The name/slug of the column to be processed
	 * 
	 * @return string Text or HTML to be placed inside the column <td>
	 */
	public function column_default( $item, $column_name ) {

		switch ( $column_name ) {

			case 'last_name' :
				return get_user_meta( $item->ID, 'first_name', true ) . ' ' . get_user_meta( $item->ID, 'last_name', true );
			case 'user_login' :
			case 'user_email' :
				return $item->$column_name;
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
	 * REQUIRED! This method dictates the table's columns and titles. This should
	 * return an array where the key is the column slug (and class) and the value 
	 * is the column's title text. If you need a checkbox for bulk actions, refer
	 * to the $columns array below.
	 * 
	 * The 'cb' column is treated differently than the rest. If including a checkbox
	 * column in your table you must create a column_cb() method. If you don't need
	 * bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
	 * 
	 * @see WP_List_Table::single_row_columns()
	 * 
	 * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
	 */
	public function get_columns() {

		return $columns = array(
			'last_name' => _x( 'Full Name', 'Full Name Column Header', PYIS_MEPR_LTV_ID ),
			'user_login' => _x( 'User Login', 'User Login Column Header', PYIS_MEPR_LTV_ID ),
			'user_email'	=> _x( 'Email Address', 'Email Address Column Header', PYIS_MEPR_LTV_ID ),
			'transactions'	=> _x( 'Completed Transactions', 'Completed Transactions Column Header', PYIS_MEPR_LTV_ID ),
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
	 * REQUIRED! This is where you prepare your data for display. This method will
	 * usually be used to query the database, sort and filter the data, and generally
	 * get it ready to be displayed. At a minimum, we should set $this->items and
	 * $this->set_pagination_args(), although the following properties and methods
	 * are frequently interacted with here...
	 * 
	 * @global WPDB $wpdb
	 * @uses $this->_column_headers
	 * @uses $this->items
	 * @uses $this->get_columns()
	 * @uses $this->get_sortable_columns()
	 * @uses $this->get_pagenum()
	 * @uses $this->set_pagination_args()
	 */
	public function prepare_items() {

		global $wpdb; //This is used only if making any database queries

		/**
		 * First, lets decide how many records per page to show
		 */
		$per_page = apply_filters( 'pyis_mepr_ltv_per_page', 15 );
		
		/**
		 * REQUIRED. Now we need to define our column headers. This includes a complete
		 * array of columns to be displayed (slugs & titles), a list of columns
		 * to keep hidden, and a list of columns that are sortable. Each of these
		 * can be defined in another method (as we've done here) before being
		 * used to build the value for our _column_headers property.
		 */
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		
		/**
		 * REQUIRED. Finally, we build an array to be used by the class for column 
		 * headers. The $this->_column_headers property takes an array which contains
		 * 3 other arrays. One for all columns, one for hidden columns, and one
		 * for sortable columns.
		 */
		$this->_column_headers = array( $columns, $hidden, $sortable );
		
		/**
		 * Instead of querying a database, we're going to fetch the example data
		 * property we created for use in this plugin. This makes this example 
		 * package slightly different than one you might build on your own. In 
		 * this example, we'll be using array manipulation to sort and paginate 
		 * our data. In a real-world implementation, you will probably want to 
		 * use sort and pagination data to build a custom query instead, as you'll
		 * be able to use your precisely-queried data immediately.
		 */
		$data = $this->query();
				
		/**
		 * REQUIRED for pagination. Let's figure out what page the user is currently 
		 * looking at. We'll need this later, so you should always include it in 
		 * your own package classes.
		 */
		$current_page = $this->get_pagenum();
		
		/**
		 * REQUIRED for pagination. Let's check how many items are in our data array. 
		 * In real-world use, this would be the total number of items in your database, 
		 * without filtering. We'll need this later, so you should always include it 
		 * in your own package classes.
		 */
		$total_items = count( $data );
		
		/**
		 * The WP_List_Table class does not handle pagination for us, so we need
		 * to ensure that the data is trimmed to only the current page. We can use
		 * array_slice() to 
		 */
		$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );
		
		/**
		 * REQUIRED. Now we can add our *sorted* data to the items property, where 
		 * it can be used by the rest of the class.
		 */
		$this->items = $data;
		
		/**
		 * REQUIRED. We also have to register our pagination options & calculations.
		 */
		$this->set_pagination_args(
			array(
				//WE have to calculate the total number of items
				'total_items'	=> $total_items,
				//WE have to determine how many items to show on a page
				'per_page'	=> $per_page,
				//WE have to calculate the total number of pages
				'total_pages'	=> ceil( $total_items / $per_page ),
				// Set ordering values if needed (useful for AJAX)
				'orderby'	=> ! empty( $_REQUEST['orderby'] ) && '' != $_REQUEST['orderby'] ? $_REQUEST['orderby'] : 'title',
				'order'		=> ! empty( $_REQUEST['order'] ) && '' != $_REQUEST['order'] ? $_REQUEST['order'] : 'asc'
			)
		);
		
	}

	/**
	 * Display the table
	 * Adds a Nonce field and calls parent's display method
	 *
	 * @since 3.1.0
	 * @access public
	 */
	public function display() {
		
		$this->prepare_items();
		
		?>
		
		<form method="get">
			<input type="hidden" name="paged" value="<?php echo isset( $_REQUEST['paged'] ) ? $_REQUEST['paged'] : '1'; ?>" />
			<input type="hidden" id="order" name="order" value="<?php echo $this->_pagination_args['order']; ?>" />
			<input type="hidden" id="orderby" name="orderby" value="<?php echo $this->_pagination_args['orderby']; ?>" />
			<?php
				wp_nonce_field( 'ajax-custom-list-nonce', '_ajax_custom_list_nonce' );
				$this->search_box( sprintf( 'Search %s', ucwords( $this->_args['plural'] ) ), 'ltv_search' );
				parent::display();
			?>
		</form>
		<br class="clear" />

		<?php
		
	}

	/**
	 * Handle an incoming ajax request (called from admin-ajax.php)
	 *
	 * @since 3.1.0
	 * @access public
	 */
	public function ajax_response() {
		
		check_ajax_referer( 'ajax-custom-list-nonce', '_ajax_custom_list_nonce' );

		$this->prepare_items();

		extract( $this->_args );
		extract( $this->_pagination_args, EXTR_SKIP );

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

		die( json_encode( $response ) );
		
	}
	
	public function query() {
		
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
            'order' => ( isset( $_REQUEST['order'] ) ) ? strtoupper( $_REQUEST['order'] ) : 'ASC',
			'include' => $has_transactions,
			'meta_query'     => array(
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
			),
		);
		
		$orderby = ( isset( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'last_name';
		
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
			foreach ( $transactions as $transaction ) {
				
				if ( ! isset( $transaction_list[ $transaction->rec->product_id ] ) ) {
				
					$transaction_list[ $transaction->rec->product_id ]['name'] = get_the_title( $transaction->rec->product_id );
						
				}
				
				$transaction_list[ $transaction->rec->product_id ]['transactions'][ $transaction->rec->id ] = $transaction->rec->trans_num;
				
				$ltv += $transaction->rec->total;
				
			}
			
			$user->transactions = $transaction_list;
			$user->ltv = $ltv;
			
		}
		
		// If we're ordering by LTV
		if ( isset( $_REQUEST['orderby'] ) &&
			$_REQUEST['orderby'] == 'ltv' ) {
			
			usort( $results, array( $this, 'usort_ltv' ) );
			
		}
	
		return $results;
		
	}
	
	/**
	 * Array Map Callback needs to be a Class Method to prevent Function Redeclaration Errors
	 * @param		array $array Input Array
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
	 * Sort the resulting Array of Objects by LTV
	 * 
	 * @param		object  $a WP_User Object with Transactions and LTV added
	 * @param		object  $b WP_User Object with Transactions and LTV added
	 *                                                             
	 * @access		public
	 * @since		1.0.0
	 * @return		integer Whether to move forward or backward in the Stack
	 */
	public function usort_ltv( $a, $b ) {
		
		// If no order, default to asc
		$order = ( ! empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'asc';
		
		if ( $a->ltv == 0 && $b->ltv !== 0 ) {
			$result = -1;
		}
		else if ( $a->ltv !== 0 && $b->ltv == 0 ) {
			$result = 1;
		}
		else {
			$result = ( $a->ltv > $b->ltv ) ? 1 : -1;
		}
		
		if ( $order == 'asc' ) {
			return $result;
		}
		else {
			return -$result;
		}
		
	}
	
	public function _js_vars() {
		
		wp_enqueue_script( PYIS_MEPR_LTV_ID . '-admin' );
		
	}

}