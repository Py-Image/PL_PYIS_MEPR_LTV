<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'PyIS_MEPR_LTV_User_Query' ) ) {

	class PyIS_MEPR_LTV_User_Query {

		function __construct() {
			
			require_once __DIR__ . '/class-pyis-mepr-ltv-user-query-process.php';
			
			$this->process_all = new PyIS_MEPR_LTV_User_Query_Process();
			
			add_action( 'admin_init', array( $this, 'start_process' ), 2 );
			
			add_action( 'admin_init', array( $this, 'process_handler' ) );
			
		}
		
		public function start_process() {
			
			$status = get_transient( 'pyis_mepr_ltv_data_status' );
			
			if ( ! $status ) {
				
				$url = remove_query_arg( 'pyis_mepr_ltv_process' );

				wp_safe_redirect( add_query_arg( 'pyis_mepr_ltv_process', '1', $url ) );
				
			}
			
		}
		
		/**
		 * Process handler
		 */
		public function process_handler() {
			
			if ( isset( $_GET['pyis_mepr_ltv_process'] ) && 
			   $_GET['pyis_mepr_ltv_process'] == true ) {
				
				error_log( 'starting' );
				
				global $wpdb;
		
				// Phase Comment Key now removed from DB
				$sql = $wpdb->delete(
					$wpdb->options,
					array(
						'option_name' => '%pyis_mepr_ltv_user_query_process%',
					)
				);
				
				delete_option( 'pyis_mepr_ltv_data' );
		
				$this->handle_all();
				
				$url = remove_query_arg( 'pyis_mepr_ltv_process' );

				wp_safe_redirect( $url );
				
			}
			
		}
		
		/**
		 * Handle all
		 */
		protected function handle_all() {
			
			$users = $this->get_users();
			
			foreach ( $users as $index => $user ) {
				
				$user->process_index = $index;
				$user->process_total = count( $users ); // This allows us to easily report a status
				
				$this->process_all->push_to_queue( $user );
				
			}
			
			$this->process_all->save()->dispatch();
			
		}
		
		/**
		 * Get names
		 *
		 * @return array
		 */
		protected function get_users() {
			
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

			$user_query = new WP_User_Query( $args );

			$results = $user_query->get_results();
			
			return $results;
			
		}
		
		/**
		 * Array Map Callback needs to be a Class Method to prevent Function Redeclaration Errors
		 * 
		 * @param		array $array Input Array
		 *                   
		 * @access		public
		 * @since		{{VERSION}}
		 * @return		array Extract out the inner Array's only value
		 */
		public function extract_user_id( $array ) {

			$reset = reset( $array );

			return $reset;

		}
		
	}
	
}

$instance = new PyIS_MEPR_LTV_User_Query();