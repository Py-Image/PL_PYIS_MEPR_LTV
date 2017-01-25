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

class PYIS_MEPR_LTV_List_Table extends WP_List_Table {

	/**
	 * Normally we would be querying data from a database and manipulating that
	 * for use in your list table. For this example, we're going to simplify it
	 * slightly and create a pre-built array. Think of this as the data that might
	 * be returned by $wpdb->query().
	 * 
	 * @var array 
	 */

	public $example_data = array(
		array(
			'ID'		=> 1,
			'title'		=> '300',
			'rating'	=> 'R',
			'director'	=> 'Zach Snyder'
		),
		array(
			'ID'		=> 2,
			'title'		=> 'Eyes Wide Shut',
			'rating'	=> 'R',
			'director'	=> 'Stanley Kubrick'
		),
		array(
			'ID'		=> 3,
			'title'		=> 'Moulin Rouge!',
			'rating'	=> 'PG-13',
			'director'	=> 'Baz Luhrman'
		),
		array(
			'ID'		=> 4,
			'title'		=> 'Snow White',
			'rating'	=> 'G',
			'director'	=> 'Walt Disney'
		),
		array(
			'ID'		=> 5,
			'title'		=> 'Super 8',
			'rating'	=> 'PG-13',
			'director'	=> 'JJ Abrams'
		),
		array(
			'ID'		=> 6,
			'title'		=> 'The Fountain',
			'rating'	=> 'PG-13',
			'director'	=> 'Darren Aronofsky'
		),
		array(
			'ID'		=> 7,
			'title'		=> 'Watchmen',
			'rating'	=> 'R',
			'director'	=> 'Zach Snyder'
		),
		array(
			'ID'		=> 8,
			'title'		=> 'The Descendants',
			'rating'	=> 'R',
			'director'	=> 'Alexander Payne'
		),
		array(
			'ID'		=> 9,
			'title'		=> 'Moon',
			'rating'	=> 'R',
			'director'	=> 'Duncan Jones'
		),
		array(
			'ID'		=> 10,
			'title'		=> 'Elysium',
			'rating'	=> 'R',
			'director'	=> 'Neill Blomkamp'
		),
		array(
			'ID'		=> 11,
			'title'		=> 'Source Code',
			'rating'	=> 'PG-13',
			'director'	=> 'Duncan Jones'
		),
		array(
			'ID'		=> 12,
			'title'		=> 'Django Unchained',
			'rating'	=> 'R',
			'director'	=> 'Quentin Tarantino'
		)
	);

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

			case 'user_fullname' :
			case 'user_login' :
			case 'user_email' :
				return $item[ $column_name ];
			default :
				//Show the whole array for troubleshooting purposes
				return print_r( $item, true );
		}
	}

	/**
	 * Recommended. This is a custom column method and is responsible for what
	 * is rendered in any column with a name/slug of 'title'. Every time the class
	 * needs to render a column, it first looks for a method named 
	 * column_{$column_title} - if it exists, that method is run. If it doesn't
	 * exist, column_default() is called instead.
	 * 
	 * This example also illustrates how to implement rollover actions. Actions
	 * should be an associative array formatted as 'slug'=>'link html' - and you
	 * will need to generate the URLs yourself. You could even ensure the links
	 * 
	 * @see WP_List_Table::single_row_columns()
	 * 
	 * @param array $item A singular item (one full row's worth of data)
	 * 
	 * @return string Text to be placed inside the column <td> (movie title only)
	 */
	public function column_title( $item ) {
		
		//Build row actions
		$actions = array(
			'edit'		=> sprintf( '<a href="?page=%s&action=%s&movie=%s">Edit</a>', $_REQUEST['page'], 'edit', $item['ID'] ),
			'delete'	=> sprintf( '<a href="?page=%s&action=%s&movie=%s">Delete</a>', $_REQUEST['page'], 'delete', $item['ID'] ),
		);
		
		//Return the title contents
		return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
			/*$1%s*/ $item['title'],
			/*$2%s*/ $item['ID'],
			/*$3%s*/ $this->row_actions( $actions )
		);
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
			'user_fullname' => _x( 'Full Name', 'Full Name Column Header', PYIS_MEPR_LTV_ID ),
			'user_login' => _x( 'User Login', 'User Login Column Header', PYIS_MEPR_LTV_ID ),
			'user_email'	=> _x( 'Email Address', 'Email Address Column Header', PYIS_MEPR_LTV_ID ),
			//'purchases'	=> _x( 'Purchases', 'Purchases Column Header', PYIS_MEPR_LTV_ID ),
			//'ltv' => _x( 'LTV', 'LTV Column Header', PYIS_MEPR_LTV_ID ),
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

		return $sortable_columns = array(
			'user_fullname' => array( 'user_fullname', false ),
			'user_login' => array( 'user_login', false ),	//true means it's already sorted
			'user_email'	=> array( 'user_email', false ),
			'purchases'	=> array( 'purchases', false ),
			'ltv' => array( 'ltv', false ),
		);
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
		 * This checks for sorting input and sorts the data in our array accordingly.
		 * 
		 * In a real-world situation involving a database, you would probably want 
		 * to handle sorting by passing the 'orderby' and 'order' values directly 
		 * to a custom query. The returned data will be pre-sorted, and this array
		 * sorting technique would be unnecessary.
		 */
		function usort_reorder( $a, $b ) {

			//If no sort, default to title
			$orderby = ( ! empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'user_fullname';
			//If no order, default to asc
			$order = ( ! empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'asc';
			 //Determine sort order
			$result = strcmp( $a[ $orderby ], $b[ $orderby ] );
			//Send final sort direction to usort
			return ( 'asc' === $order ) ? $result : -$result;
			
		}
		usort( $data, 'usort_reorder' );
		
		
		/***********************************************************************
		 * ---------------------------------------------------------------------
		 * vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv
		 * 
		 * In a real-world situation, this is where you would place your query.
		 *
		 * For information on making queries in WordPress, see this Codex entry:
		 * http://codex.wordpress.org/Class_Reference/wpdb
		 * 
		 * ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
		 * ---------------------------------------------------------------------
		 **********************************************************************/
		
				
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
		$total_items = $this->get_total_count();
		
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

		wp_nonce_field( 'ajax-custom-list-nonce', '_ajax_custom_list_nonce' );

		echo '<input type="hidden" id="order" name="order" value="' . $this->_pagination_args['order'] . '" />';
		echo '<input type="hidden" id="orderby" name="orderby" value="' . $this->_pagination_args['orderby'] . '" />';

		parent::display();
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
		$this->pagination('top');
		$pagination_top = ob_get_clean();

		ob_start();
		$this->pagination('bottom');
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
	
	public function get_total_count() {
		
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'mepr_transactions';
		
		// You cannot pass a Table Name via $wpdb->prepare() as that will cause the table name to not match
		// http://wordpress.stackexchange.com/a/25850
		$query = "
		SELECT COUNT(DISTINCT user_id) 
		FROM $table_name
		WHERE status = 'complete'";
		
		$total_items = $wpdb->get_var( $query );
		
		if ( $total_items === null ) return 0;
		
		return $total_items;
		
	}
	
	public function query() {
		
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'mepr_transactions';
		
		$query = "
		SELECT $wpdb->users.ID,$wpdb->users.user_login,$wpdb->users.user_email
		FROM $wpdb->users
		LEFT OUTER JOIN $table_name
		ON $table_name.user_id = $wpdb->users.ID";
		
		$results = $wpdb->get_results( $query, ARRAY_A );
		
		foreach ( $results as &$result ) {
			$result['user_fullname'] = get_user_meta( $result['ID'], 'first_name', true ) . ' ' . get_user_meta( $result['ID'], 'last_name', true );
		}
	
		return $results;
		
	}

}