<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class PyIS_MEPR_LTV_User_Query_Process extends WP_Background_Process {

	/**
	 * @var string
	 */
	protected $action = 'pyis_mepr_ltv_user_query_process';

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $item Queue item to iterate over
	 *
	 * @return mixed
	 */
	protected function task( $user ) {
		
		$transactions = $this->completed_transactions_by_user_id( $user->ID );
			
		$ltv = 0;
		$last_billed = '1970-01-01'; // Unix Epoch
		$today = date( 'Y-m-d', current_time( 'timestamp' ) );
		$next_billed = $today; // We only want the soonest next billed date, so we compare to today.
		foreach ( $transactions as $transaction ) {

			$created_at = date( 'Y-m-d', strtotime( $transaction->rec->created_at ) );
			if ( $created_at > $last_billed ) {
				$last_billed = $created_at;
			}

			if ( $next_billed == $today ) {

				$expires_at = date( 'Y-m-d', strtotime( $transaction->rec->expires_at ) );

				if ( $expires_at == '-0001-11-30' ) { // It says to leave the field blank, but this seemingly random value is what gets saved
					$next_billed = -1;
					break; // Lifetime Subscription, stop looping
				}
				else if ( $expires_at > $next_billed ) {
					$next_billed = $expires_at;
				}

			}

			$ltv += $transaction->rec->total;

		}

		if ( $next_billed == $today ) {
			$next_billed = 0; // No Next Billed Date
		}

		$user->first_name = trim( get_user_meta( $user->ID, 'first_name', true ) );
		$user->last_name = trim( get_user_meta( $user->ID, 'last_name', true ) );
		$user->last_billed = $last_billed;
		$user->next_billed = $next_billed;
		$user->ltv = $ltv;
		
		$count = $user->process_index + 1;
		
		$data = get_option( 'pyis_merp_ltv_data' );
		
		if ( ! $data ) {
			$data = array();
		}
		
		$data[] = $user;
		
		error_log( "User #$user->ID has been processed" );
		error_log( "$count / $user->process_total Users have been processed" );
		set_transient( 'pyis_mepr_ltv_data_status', "$count/$user->process_total", WEEK_IN_SECONDS );
		
		update_option( 'pyis_merp_ltv_data', $data );
		
		if ( $count == $user->process_total ) {
			
			$this->complete(); // This should not be necessary, but I cannot find out why it is running longer than it should
			
			global $wpdb;
		
			// Phase Comment Key now removed from DB
			$sql = $wpdb->delete(
				$wpdb->options,
				array(
					'option_name' => '%pyis_mepr_ltv_user_query_process%',
				)
			);
			
		}

		return false;
		
	}
	
	/**
	 * MemberPress is so weird about this stuff
	 * 
	 * @param		integer $user_id User ID
	 *                               
	 * @access		public
	 * @since		{{VERSION}}
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
	 * @since		{{VERSION}}
	 * @return		array  Filtered Transactions Array
	 */
	public function completed_transactions_only( $object ) {
		
		if ( $object->status !== 'complete' ) return false;
			
		return true;
		
	}

	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		parent::complete();

		// Show notice to user or perform some other arbitrary task...
		
		error_log( "All MemberPress LTV Data Processed" );
		
		set_transient( 'pyis_mepr_ltv_data_status', true, WEEK_IN_SECONDS );
		
	}

}