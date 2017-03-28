<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_MailChimp_Admin_Settings
 */
final class NF_Intel_Admin_Settings
{
    public function __construct()
    {
        //add_filter( 'ninja_forms_plugin_settings',                  array( $this, 'plugin_settings'             ), 10, 1 );
        //add_filter( 'ninja_forms_plugin_settings_groups',           array( $this, 'plugin_settings_groups'      ), 10, 1 );
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

    /*
    public function validate_ninja_forms_mc_api( $setting )
    {
        $debug = defined( 'WP_DEBUG' ) && WP_DEBUG;
        $api_key = trim( $setting[ 'value' ] );
        $ssl_verifypeer = ( Ninja_Forms()->get_setting( 'ninja_forms_mc_disable_ssl_verify' ) ) ? TRUE : FALSE;

        $options = array(
            'debug' => $debug,
            'ssl_verifypeer' => $ssl_verifypeer,
        );

        try {
            $mailchimp = new Mailchimp( $api_key, $options );
            $mailchimp->call( 'lists/list', array( 'limit' => 1 ) );
        } catch( Exception $e ) {
            // TODO: Log Error, $e->getMessage(), for System Status Report
            $setting[ 'errors' ][] = __( 'The MailChimp API key you have entered appears to be invalid.', 'ninja-forms-mail-chimp');
        }

        return $setting;
    }
    */

} // End Class NF_Intel_Admin_Settings
