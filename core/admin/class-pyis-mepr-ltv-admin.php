<?php
/**
 * The admin settings side to EDD Slack
 *
 * @since 1.0.0
 *
 * @package PYIS_MEPR_LTV
 * @subpackage PYIS_MEPR_LTV/core/admin
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
defined( 'ABSPATH' ) || die();

class PYIS_MEPR_LTV_Admin {

	/**
	 * PYIS_MEPR_LTV_Admin constructor.
	 *
	 * @since 1.0.0
	 */
	function __construct() {
		
		add_action( 'admin_menu', array( $this, 'add_submenu_page' ) );
		
		add_action( 'wp_ajax_pyis_mepr_ltv_list', array( $this, 'pyis_mepr_ltv_ajax_callback' ) );
		
	}
	
	private function require_necessities() {
		require_once PYIS_MEPR_LTV_DIR . '/core/includes/class-pyis-mepr-ltv-list-table.php';
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
		
		// Prevent loading the PHP Error hiding anywhere we don't want it
		$this->require_necessities();
		
		$table = new PYIS_MEPR_LTV_List_Table();
		$table->display();
		
	}
	
	public function pyis_mepr_ltv_ajax_callback() {
		
		// Prevent loading the PHP Error hiding anywhere we don't want it
		$this->require_necessities();
		
		$table = new PYIS_MEPR_LTV_List_Table();
		$table->ajax_response();
		
	}
	
}