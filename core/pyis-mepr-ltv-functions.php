<?php
/**
 * Provides helper functions.
 *
 * @since	  1.0.0
 *
 * @package	PYIS_MEPR_LTV
 * @subpackage PYIS_MEPR_LTV/core
 */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Returns the main plugin object
 *
 * @since		1.0.0
 *
 * @return		PYIS_MEPR_LTV
 */
function PYISMEPRLTV() {
	return PYIS_MEPR_LTV::instance();
}