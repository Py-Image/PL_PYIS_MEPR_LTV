<?php
/**
 * The admin settings side to EDD Slack
 *
 * @since 1.0.0
 *
 * @package PYIS_MEPR_LTV
 * @subpackage PYIS_MEPR_LTV/core/admin
 */

defined( 'ABSPATH' ) || die();

class PYIS_MEPR_LTV_Admin {
	
	public $table;

	/**
	 * PYIS_MEPR_LTV_Admin constructor.
	 *
	 * @since 1.0.0
	 */
	function __construct() {
		
		$this->require_necessities();
		
		if ( current_user_can( 'manage_options' ) ) {
		
			add_action( 'admin_init', array( $this, 'global_table' ) );

			add_action( 'admin_menu', array( $this, 'add_submenu_page' ) );

			add_action( 'wp_ajax_pyis_mepr_ltv_list', array( $this, 'pyis_mepr_ltv_ajax_callback' ) );
			
			add_action( 'wp_ajax_pyis_mepr_ltv_flush', array( $this, 'pyis_mepr_ltv_flush_callback' ) );
			
			add_filter( 'pyis_mepr_ltv_localize_admin_script', array( $this, 'localize_javascript_text' ) );
			
		}
		
	}
	
	/**
	 * Include our WP_List_Table Class
	 * 
	 * @access		public
	 * @since		1.0.0
	 * @return		void
	 */
	private function require_necessities() {
		
		require_once PYIS_MEPR_LTV_DIR . '/core/includes/class-pyis-mepr-ltv-list-table.php';
		
	}
	
	/**
	 * Make our Table Object accessible globally
	 * 
	 * @access		public
	 * @since		1.0.0
	 * @return		void
	 */
	public function global_table() {
		
		global $pagenow;
		
		if ( ( $pagenow == 'admin.php' && 
		   isset( $_GET['page'] ) &&
		   $_GET['page'] == 'pyis-mepr-ltv' ) ||
		   $pagenow == 'admin-ajax.php' ) {
		
			$this->table = new PYIS_MEPR_LTV_List_Table();
			
		}
		
	}
	
	/**
	 * Add a Submenu to MemberPress
	 * 
	 * @access		public
	 * @since		1.0.0
	 * @return		void
	 */
	public function add_submenu_page() {
		
		add_submenu_page(
			'memberpress',
			'MemberPress LTV', // Page Title
			'LTV', // Submenu Tite
			'manage_options',
			'pyis-mepr-ltv',
			array( $this, 'page_content' )
		);
		
	}
	
	/**
	 * Create our Page Content
	 * 
	 * @access		public
	 * @since		1.0.0
	 * @return		HTML
	 */
	public function page_content() {
		
		$this->table->display();
		
		$expiration = get_option( '_transient_timeout_pyis_mepr_ltv_data' );
			
		// date_i18n() doesn't support Timezones and I don't know why
		// Even if you generate a Timezone-appropriate Timestamp, it converts it to UTC
		$expiration = $this->date_i18n_timezone( false, $expiration );
		
		?>

		<label>
			<input type="button" class="flush-transients button button-primary" value="<?php echo _x( 'Refresh Table Data', 'Flush Transients Label', PYIS_MEPR_LTV_ID ); ?>" /> <br />
			<?php echo _x( 'Table data will refresh automatically on: ', 'Transient Expiration Date Label', PYIS_MEPR_LTV_ID ); ?>
			<span class="transient-expiration"><?php echo $expiration; ?></span>
		</label>

		<?php
		
	}
	
	/**
	 * Callback for the AJAX request to Update the Table
	 * 
	 * @access		public
	 * @since		1.0.0
	 * @return		JSON
	 */
	public function pyis_mepr_ltv_ajax_callback() {
		
		$this->table->ajax_response();
		
	}
	
	/**
	 * Callback to clear the Transient via AJAX
	 * 
	 * @access		public
	 * @since		1.0.0
	 * @return		JSON
	 */
	public function pyis_mepr_ltv_flush_callback() {
		
		check_ajax_referer( 'pyis-mepr-ltv-nonce', '_pyis_mepr_ltv_nonce' );
		
		$deleted = delete_transient( 'pyis_mepr_ltv_data' );
		
		if ( $deleted === true ) {
			
			// Force a refresh of the data so we can get a new Expiration Datetime
			$refresh = $this->table->get_data();
		
			// The Transient has been reset, so we have a new Expiration Timestamp
			$expiration = get_option( '_transient_timeout_pyis_mepr_ltv_data' );
			
			// date_i18n() doesn't support Timezones and I don't know why
			// Even if you generate a Timezone-appropriate Timestamp, it converts it to UTC
			$expiration = $this->date_i18n_timezone( false, $expiration );
			
			wp_send_json_success( array(
				'expiration' => $expiration,
			) );
			
		}
		else {
			
			// Something broke
			wp_send_json_error( array(
				'deleted' => $deleted,
			) );
			
		}
		
	}
	
	/**
	 * Localize Strings for JavaScript to use
	 * 
	 * @param		array $localization Localization Array
	 *                                          
	 * @access		public
	 * @since		1.0.0
	 * @return		array Localization Array
	 */
	public function localize_javascript_text( $localization ) {
		
		$localization['i18n'] = array(
			'flushProcessing' => _x( 'Working...', 'Transient Flush in Process Text', PYIS_MEPR_LTV_ID ),
			'flushSuccess' => _x( 'Done!', 'Transient Successfully Flushed Text', PYIS_MEPR_LTV_ID ),
			'flushFailure' => _x( 'Failed to Refresh Data', 'Transient Flush Failed Text', PYIS_MEPR_LTV_ID ),
			'flushDefault' => _x( 'Refresh Table Data', 'Flush Transients Label', PYIS_MEPR_ID ),
		);
		
		return $localization;
		
	}
	
	/**
	 * date_i18n() doesn't support Timezones. It explicitly works against them.
	 * Based off http://wordpress.stackexchange.com/a/135049
	 * 
	 * @param		string  $format    PHP Date Format String
	 * @param		integer $timestamp UNIX Timestamp to convert
	 * @param		string  $timezone  Timezone String
	 * @param		boolean $gmt       Whether or not this Timestamp is based on GMT
	 *                                                                      
	 * @access		public
	 * @since		1.0.0
	 * @return		string  Localized and Timezone-ified Date String
	 */
	public function date_i18n_timezone( $format = false, $timestamp = false, $timezone = false, $gmt = false ) {
		
		if ( ! $format ) {
			$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) . ' T';
		}
		
		if ( ! $timestamp ) {
			
			if ( ! $gmt ) {
				$timestamp = current_time( 'timestamp' );
			}
			else {
				$timestamp = time();
			}
			
			// date_i18n() defaults this to true if there's no Timestamp. Guess we will too
			
			// we should not let date() interfere with our
			// specially computed timestamp
			$gmt = true;
			
		}
		
		if ( ! $timezone ) {
			$timezone = get_option( 'timezone_string' );
		}

		// The datetime in the local timezone
		$datetime = new \DateTime( null, new DateTimeZone( $timezone ) );
		$datetime->setTimestamp( (int) $timestamp );
		$date_str = $datetime->format( 'Y-m-d H:i:s' );

		// Pretend the local date is UTC to get the timestamp to pass to date_i18n()
		// Otherwise date_i18n() "corrects" itself back to UTC
		$utc_timezone = new \DateTimeZone( 'UTC' );
		$utc_date = new \DateTime( $date_str, $utc_timezone );

		$timestamp = $utc_date->getTimestamp();

		return date_i18n( $format, $timestamp, $gmt );
		
	}
	
}