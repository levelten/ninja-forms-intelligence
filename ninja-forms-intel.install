<?php

/**
 * Fired when the plugin is installed and contains schema info and updates.
 *
 * @link       getlevelten.com/blog/tom
 * @since      1.2.7
 *
 * @package    Intel
 */


function nf_intel_install() {

}

/**
 * Implements hook_uninstall();
 *
 * Delete plugin settings
 *
 */
function nf_intel_uninstall() {
	// uninstall plugin related intel data
	if (is_callable('intel_uninstall_plugin')) {
		intel_uninstall_plugin('nf_intel');
	}
}