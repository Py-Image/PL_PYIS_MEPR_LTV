<?php
/*
Plugin Name: PyImageSearch MemberPress LTV
Plugin URL: https://github.com/realbig/PL_PYIS_MEPR_LTV
Description: Provides a searchable and sortable table showing Life Time Value
Version: 1.0.0
Text Domain: pyis-mepr-ltv
Author: Eric Defore
Author URI: http://realbigmarketing.com
Contributors: d4mation
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'PYIS_MEPR_LTV' ) ) {

	/**
	 * Main PYIS_MEPR_LTV class
	 *
	 * @since	  1.0.0
	 */
	class PYIS_MEPR_LTV {
		
		/**
		 * @var			PYIS_MEPR_LTV $plugin_data Holds Plugin Header Info
		 * @since		1.0.0
		 */
		public $plugin_data;
		
		/**
		 * @var			PYIS_MEPR_LTV $admin_errors Stores all our Admin Errors to fire at once
		 * @since		1.0.0
		 */
		private $admin_errors;
		
		/**
		 * @var			PYIS_MEPR_LTV $admin Admin Settings
		 * @since		1.0.0
		 */
		public $admin;

		/**
		 * Get active instance
		 *
		 * @access	  public
		 * @since	  1.0.0
		 * @return	  object self::$instance The one true PYIS_MEPR_LTV
		 */
		public static function instance() {
			
			static $instance = null;
			
			if ( null === $instance ) {
				$instance = new static();
			}
			
			return $instance;

		}
		
		protected function __construct() {
			
			$this->setup_constants();
			$this->load_textdomain();
			
			if ( ! defined( 'MEPR_VERSION' ) ) {
				
				$this->admin_errors[] = sprintf( _x( '%s requires %s to be installed!', 'Missing Dependency Error', PYIS_MEPR_LTV_ID ), '<strong>' . $this->plugin_data['Name'] . '</strong>', '<a href="//www.memberpress.com/" target="_blank"><strong>MemberPress</strong></a>' );
				
				if ( ! has_action( 'admin_notices', array( $this, 'admin_errors' ) ) ) {
					add_action( 'admin_notices', array( $this, 'admin_errors' ) );
				}
				
			}
			
			$this->require_necessities();
			
			// Register our CSS/JS for the whole plugin
			add_action( 'init', array( $this, 'register_scripts' ) );
			
		}

		/**
		 * Setup plugin constants
		 *
		 * @access	  private
		 * @since	  1.0.0
		 * @return	  void
		 */
		private function setup_constants() {
			
			// WP Loads things so weird. I really want this function.
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}
			
			// Only call this once, accessible always
			$this->plugin_data = get_plugin_data( __FILE__ );
			
			if ( ! defined( 'PYIS_MEPR_LTV_ID' ) ) {
				// Plugin Text Domain
				define( 'PYIS_MEPR_LTV_ID', $this->plugin_data['TextDomain'] );
			}

			if ( ! defined( 'PYIS_MEPR_LTV_VER' ) ) {
				// Plugin version
				define( 'PYIS_MEPR_LTV_VER', $this->plugin_data['Version'] );
			}

			if ( ! defined( 'PYIS_MEPR_LTV_DIR' ) ) {
				// Plugin path
				define( 'PYIS_MEPR_LTV_DIR', plugin_dir_path( __FILE__ ) );
			}

			if ( ! defined( 'PYIS_MEPR_LTV_URL' ) ) {
				// Plugin URL
				define( 'PYIS_MEPR_LTV_URL', plugin_dir_url( __FILE__ ) );
			}
			
			if ( ! defined( 'PYIS_MEPR_LTV_FILE' ) ) {
				// Plugin File
				define( 'PYIS_MEPR_LTV_FILE', __FILE__ );
			}

		}

		/**
		 * Internationalization
		 *
		 * @access	  private 
		 * @since	  1.0.0
		 * @return	  void
		 */
		private function load_textdomain() {

			// Set filter for language directory
			$lang_dir = PYIS_MEPR_LTV_DIR . '/languages/';
			$lang_dir = apply_filters( 'pyis_mepr_ltv_languages_directory', $lang_dir );

			// Traditional WordPress plugin locale filter
			$locale = apply_filters( 'plugin_locale', get_locale(), PYIS_MEPR_LTV_ID );
			$mofile = sprintf( '%1$s-%2$s.mo', PYIS_MEPR_LTV_ID, $locale );

			// Setup paths to current locale file
			$mofile_local   = $lang_dir . $mofile;
			$mofile_global  = WP_LANG_DIR . '/' . PYIS_MEPR_LTV_ID . '/' . $mofile;

			if ( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/edd-slack/ folder
				// This way translations can be overridden via the Theme/Child Theme
				load_textdomain( PYIS_MEPR_LTV_ID, $mofile_global );
			}
			else if ( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/edd-slack/languages/ folder
				load_textdomain( PYIS_MEPR_LTV_ID, $mofile_local );
			}
			else {
				// Load the default language files
				load_plugin_textdomain( PYIS_MEPR_LTV_ID, false, $lang_dir );
			}

		}
		
		/**
		 * Include different aspects of the Plugin
		 * 
		 * @access	  private
		 * @since	  1.0.0
		 * @return	  void
		 */
		private function require_necessities() {
			
			if ( is_admin() ) {
				
				require_once PYIS_MEPR_LTV_DIR . '/core/admin/class-pyis-mepr-ltv-admin.php';
				$this->admin = new PYIS_MEPR_LTV_Admin();
				
			}
			
		}
		
		/**
		 * Show admin errors.
		 * 
		 * @access	  public
		 * @since	  1.0.0
		 * @return	  HTML
		 */
		public function admin_errors() {
			?>
			<div class="error">
				<?php foreach ( $this->admin_errors as $notice ) : ?>
					<p>
						<?php echo $notice; ?>
					</p>
				<?php endforeach; ?>
			</div>
			<?php
		}
		
		/**
		 * Register our CSS/JS to use later
		 * 
		 * @access	  public
		 * @since	  1.0.0
		 * @return	  void
		 */
		public function register_scripts() {
			
			wp_register_style(
				PYIS_MEPR_LTV_ID . '-admin',
				PYIS_MEPR_LTV_URL . 'assets/css/admin.css',
				null,
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : PYIS_MEPR_LTV_VER
			);
			
			wp_register_script(
				PYIS_MEPR_LTV_ID . '-admin',
				PYIS_MEPR_LTV_URL . 'assets/js/admin.js',
				array( 'jquery', 'jquery-effects-core', 'jquery-effects-highlight' ),
				defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : PYIS_MEPR_LTV_VER,
				true
			);
			
			wp_localize_script( 
				PYIS_MEPR_LTV_ID . '-admin',
				'pyisMeprLtv',
				apply_filters( 'pyis_mepr_ltv_localize_admin_script', array() )
			);
			
		}
		
	}
	
} // End Class Exists Check

/**
 * The main function responsible for returning the one true PYIS_MEPR_LTV
 * instance to functions everywhere
 *
 * @since	  1.0.0
 * @return	  \PYIS_MEPR_LTV The one true PYIS_MEPR_LTV
 */
add_action( 'plugins_loaded', 'pyis_mepr_ltv_load' );
function pyis_mepr_ltv_load() {

	require_once __DIR__ . '/core/pyis-mepr-ltv-functions.php';
	PYISMEPRLTV();

}
