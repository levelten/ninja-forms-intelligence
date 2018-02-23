<?php

/**
 * @file
 * Included to assist in initial setup of plugin
 *
 * @since      1.0.0
 *
 * @package    Intel
 */

if (!is_callable('intel_setup')) {
	include_once NF_Intel()->dir . 'intel_com/intel.setup.php';
}

class NF_Intel_Setup extends Intel_Setup {

	public $plugin_un = 'nf_intel';

	/*
	 * Include any methods from Intel_Setup you want to override
	 */

}

function nf_intel_setup() {
	return NF_Intel_Setup::instance();
}
nf_intel_setup();
