<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Intel_Admin_Settings
 */
final class NF_Intel_Admin_Settings
{
    public function __construct()
    {
        add_filter( 'ninja_forms_plugin_settings',                  array( $this, 'plugin_settings'             ), 10, 1 );
        add_filter( 'ninja_forms_plugin_settings_groups',           array( $this, 'plugin_settings_groups'      ), 10, 1 );
        //add_filter( 'ninja_forms_check_setting_ninja_forms_mc_api', array( $this, 'validate_ninja_forms_mc_api' ), 10, 1 );
    }

    public function plugin_settings( $settings )
    {
        $settings[ 'intel' ] = NF_Intel()->config( 'PluginSettings' );
        return $settings;
    }

    public function plugin_settings_groups( $groups )
    {
        $groups = array_merge( $groups, NF_Intel()->config( 'PluginSettingsGroups' ) );
        return $groups;
    }

} // End Class NF_Intel_Admin_Settings
