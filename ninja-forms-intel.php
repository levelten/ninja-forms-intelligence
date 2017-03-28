<?php if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * Plugin Name: Ninja Forms - Intelligence
 * Plugin URI: https://ninjaforms.com/extensions/intelligence/
 * Description: Intelligent analytics for Ninja Forms
 * Version: 3.0.0
 * Author: LevelTen
 * Author URI: http://getlevelten.com/
 * Text Domain: ninja-forms-intel
 *
 * Copyright 2017 LevelTen Interactive.
 */

if( version_compare( get_option( 'ninja_forms_version', '0.0.0' ), '3', '<' ) || get_option( 'ninja_forms_load_deprecated', FALSE ) ) {

  include 'deprecated/ninja-forms-intel.php';

} else {

  /**
   * Class NF_Intel
   */
  final class NF_Intel {
    const VERSION = '3.0.0';
    const SLUG    = 'intel';
    const NAME    = 'Intelligence';
    const AUTHOR  = 'LevelTen';
    const PREFIX  = 'NF_Intel';

    /**
     * @var NF_MailChimp
     * @since 3.0
     */
    private static $instance;

    /**
     * Plugin Directory
     *
     * @since 3.0
     * @var string $dir
     */
    public static $dir = '';

    /**
     * Plugin URL
     *
     * @since 3.0
     * @var string $url
     */
    public static $url = '';

    /**
     * @var Mailchimp
     */
    private $_api;

    /**
     * Main Plugin Instance
     *
     * Insures that only one instance of a plugin class exists in memory at any one
     * time. Also prevents needing to define globals all over the place.
     *
     * @since 3.0
     * @static
     * @static var array $instance
     * @return NF_MailChimp Highlander Instance
     */
    public static function instance()
    {

      if ( !isset( self::$instance ) && !( self::$instance instanceof NF_Intel ) ) {
        self::$instance = new NF_Intel();

        self::$dir = plugin_dir_path(__FILE__);

        self::$url = plugin_dir_url(__FILE__);

        spl_autoload_register(array(self::$instance, 'autoloader'));

        new NF_Intel_Admin_Settings();
      }

      return self::$instance;
    }

    /**
     * NF_MailChimp constructor.
     *
     */
    public function __construct()
    {

      if ( ! function_exists( 'curl_version' ) ) {
        add_action( 'admin_notices', array( $this, 'curl_error' ) );
        return false;
      }
      //add_action( 'admin_init', array( $this, 'setup_license' ) );
      //add_filter( 'ninja_forms_register_fields', array( $this, 'register_fields' ) );
      add_filter( 'ninja_forms_register_actions', array( $this, 'register_actions' ) );

      add_filter( 'ninja_forms_field_settings_groups', array( $this, 'field_settings_groups'));

      add_action( 'ninja_forms_loaded', array( $this, 'ninja_forms_loaded' ) );


      // Add our metabox for editing field values
      add_action( 'add_meta_boxes', array( $this, 'add_metaboxes' ) );

//Intel_Df::watchdog('NF_Intel construct', 'ok');
    }

    public function add_metaboxes() {
      add_meta_box( 'nf_intel_fields', __( 'Intelligence', 'ninja-forms-intel' ), array( $this, 'edit_sub_metabox' ), 'nf_sub', 'normal', 'low');
    }

    public function ninja_forms_loaded() {
      new NF_Intel_Admin_Metaboxes_Submission();
    }

    /**
     * Register Fields
     *
     * @param array $actions
     * @return array $actions
     */
    public function register_fields($actions) {
      //$actions[ 'mailchimp-optin' ] = new NF_MailChimp_Fields_OptIn();

      return $actions;
    }

    /**
     * Register Actions
     *
     * @param array $actions
     * @return array $actions
     */
    public function register_actions($actions) {
      $actions[ 'intel' ] = new NF_Intel_Actions_Intel();

      return $actions;
    }

    /**
     * Setup tracking field groups
     */
    public function field_settings_groups($groups) {
      $groups['tracking'] = array(
        'id' => 'tracking',
        'label' => __( 'Tracking', 'ninja-forms-intel' ),
        'priority' => 600,
      );
      return $groups;
    }

    /**
     * Autoloader
     *
     * @param $class_name
     */
    public function autoloader($class_name)
    {
      if (class_exists($class_name)) return;

      if ( false === strpos( $class_name, self::PREFIX ) ) return;

      $class_name = str_replace( self::PREFIX, '', $class_name );
      $classes_dir = realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;
      $class_file = str_replace('_', DIRECTORY_SEPARATOR, $class_name) . '.php';

      if (file_exists($classes_dir . $class_file)) {
        require_once $classes_dir . $class_file;
      }
    }

    /**
     * Setup License
     */
    public function setup_license()
    {
      if ( ! class_exists( 'NF_Extension_Updater' ) ) return;

      new NF_Extension_Updater( self::NAME, self::VERSION, self::AUTHOR, __FILE__, self::SLUG );
    }

    /*
     * API
     */

    public function get_lists()
    {
      if( ! $this->api() ) return array();

      $lists = array();

      try {
        $response = $this->api()->call('lists/list', array());
      } catch( Exception $e ){
        return $lists;
      }

      foreach( $response[ 'data' ] as $data ) {

        Ninja_Forms()->update_setting( 'mail_chimp_list_' . $data[ 'id' ], $data[ 'name' ] );

        $lists[] = array(
          'value' => $data[ 'id' ],
          'label' => $data[ 'name' ],
          'groups' => $this->get_list_groups( $data[ 'id' ] ),
          'fields' => $this->get_list_merge_vars( $data[ 'id' ] )
        );
      }

      return $lists;
    }

    public function get_list_merge_vars( $list_id )
    {
      if( ! $this->api() ) return array();

      $response = $this->api()->lists->mergeVars( array( $list_id ) );

      $merge_vars = array();
      $list = $response[ 'data' ][ 0 ];
      foreach( $list[ 'merge_vars' ] as $merge_var ){

        $required_text = ( $merge_var[ 'req' ] ) ? ' <small style="color:red">(required)</small>' : '';

        $merge_vars[] = array(
          'value' => $list[ 'id' ] . '_' . $merge_var[ 'tag' ],
          'label' => $merge_var[ 'name' ] . $required_text
        );
      }

      return $merge_vars;
    }

    public function get_list_groups( $list_id )
    {
      if( ! $this->api() ) return array();

      try {
        $response = $this->api()->lists->interestGroupings($list_id);
      }  catch( Mailchimp_Error $e ) {
        return array();
      } catch( Exception $e ) {
        // TODO: Log error for System Status page.
        return array();
      }

      $groups = array();

      if( $response ) {
        foreach( $response as $grouping ) {
          foreach ($grouping['groups'] as $group) {
            $groups[] = array(
              'value' => $list_id . '_group_' . $grouping['id'] . '_' . $group['name'],
              'label' => $group['name']
            );
          }
        }
      }

      return $groups;
    }

    public function subscribe( $list_id, $merge_vars, $double_opt_in )
    {
      try {
        return NF_MailChimp()->api()->call('lists/subscribe', array(
          'id' => $list_id,
          'email' => array( 'email' => $merge_vars[ 'EMAIL' ] ),
          'merge_vars' => $merge_vars,
          'double_optin' => $double_opt_in,
          'update_existing' => true,
          'replace_interests' => false,
          'send_welcome' => false,
        ));
      } catch( Mailchimp_Error $e ) {
        // TODO: Log error for System Status page.
        return array( 'error' => $e->getMessage() );
      } catch( Exception $e ) {
        // TODO: Log error for System Status page.
        return FALSE;
      }
    }

    public function api()
    {
      if( ! $this->_api ) {

        $debug = defined('WP_DEBUG') && WP_DEBUG;
        $api_key = trim(Ninja_Forms()->get_setting('ninja_forms_mc_api'));
        $ssl_verifypeer = (Ninja_Forms()->get_setting('ninja_forms_mc_disable_ssl_verify')) ? FALSE : TRUE;

        $options = array(
          'debug' => $debug,
          'ssl_verifypeer' => $ssl_verifypeer,
        );

        try {
          $this->_api = new Mailchimp($api_key, $options);
        } catch (Exception $e) {
          // TODO: Log Error, $e->getMessage(), for System Status Report
        }
      }
      return $this->_api;
    }

    /*
     * STATIC METHODS
     */

    /**
     * Load Template File
     *
     * @param string $file_name
     * @param array $data
     */
    public static function template( $file_name = '', array $data = array() )
    {
      if( ! $file_name ) return;

      extract( $data );

      include self::$dir . 'includes/Templates/' . $file_name;
    }

    /**
     * Load Config File
     *
     * @param $file_name
     * @return array
     */
    public static function config( $file_name )
    {
      return include self::$dir . 'includes/Config/' . $file_name . '.php';
    }

    /**
     * Output our field editing metabox to the CPT editing page.
     *
     * @access public
     * @since 2.7
     * @return void
     */
    public function edit_sub_metabox( $post ) {
      global $ninja_forms_fields;

      // enueue admin styling & scripts
      intel()->admin->enqueue_styles();
      intel()->admin->enqueue_scripts();

      $post_meta = get_post_meta($post->ID);
      $vars = array(
        'type' => 'ninja_form',
        'fid' => get_post_meta($post->ID, '_form_id', TRUE),
        'fsid' => get_post_meta($post->ID, '_seq_num', TRUE),
      );

      $submission = intel()->get_entity_controller('intel_submission')->loadByVars($vars);
      if (empty($submission)) {
        _e('Submission entry not found.', 'ninja-form-intel');
        return;
      }
      $submission = array_shift($submission);
      $s = $submission->getSynced();
      if (!$submission->getSynced()) {
        $submission->syncData();
      }
      $submission->build_content($submission);
      $visitor = intel()->get_entity_controller('intel_visitor')->loadOne($submission->vid);
      $visitor->build_content($visitor);

      //d($visitor->content);
      $build = $visitor->content;
      foreach ($build as $k => $v) {
        if (empty($v['#region']) || ($v['#region'] == 'sidebar')) {
          unset($build[$k]);
        }
      }
      $build = array(
        'elements' => $build,
        'view_mode' => 'half',
      );
      $output = Intel_Df::theme('intel_visitor_profile', $build);

      $steps_table = Intel_Df::theme('intel_visit_steps_table', array('steps' => $submission->data['analytics_session']['steps']));
      ?>
      <div class="inside bootstrap-wrapper intel-wrapper">
        <div class="intel-content half row">
          <h4 class="card-header"><?php print __('Submitter profile', 'gravityformsintel'); ?></h4>
          <?php print $output; ?>
          <!-- <h4 class="card-header"><?php print __('Analytics', 'gravityformsintel'); ?></h4> -->
          <div class="card-deck-wrapper m-b-1">
            <div class="card-deck">
              <?php print Intel_Df::theme('intel_trafficsource_block', array('trafficsource' => $submission->data['analytics_session']['trafficsource'])); ?>
              <?php print Intel_Df::theme('intel_location_block', array('entity' => $submission)); ?>
              <?php print Intel_Df::theme('intel_browser_environment_block', array('entity' => $submission)); ?>
            </div>
          </div>
          <?php print Intel_Df::theme('intel_visitor_profile_block', array('title' => __('Visit chronology', 'gravityformsintel'), 'markup' => $steps_table, 'no_margin' => 1)); ?>
        </div>
      </div>
      <?php
      return;
    }

    /**
     * Output an admin notice if curl is not available
     *
     * @return  void;
     */
    public static function curl_error() {
      ?>
      <div class="notice notice-error">
        <p>
          <?php _e( '<strong>Please contact your host:</strong> PHP cUrl is not installed; Mailchimp for Ninja Forms requires cUrl and will not function properly. ', 'ninja-forms-mailchimp' ); ?>
        </p>
      </div>

      <?php
    }
  }

  /**
   * The main function responsible for returning The Highlander Plugin
   * Instance to functions everywhere.
   *
   * Use this function like you would a global variable, except without needing
   * to declare the global.
   *
   * @since 3.0
   * @return NF_MailChimp
   */
  function NF_Intel() {
    return NF_Intel::instance();
  }

  NF_Intel();
}

/*
add_filter( 'ninja_forms_upgrade_action_mailchimp', 'NF_Intel_Upgrade' );
function NF_MailChimp_Upgrade( $action ){

  // newsletter_list
  if( ! isset( $action[ 'list-id' ] ) ) return $action;

  $list_id = $action[ 'list-id' ];
  $action[ 'newsletter_list' ] = $list_id;

  // 93c8c814a4_EMAIL
  if( isset( $action[ 'merge-vars' ] ) ) {
    $merge_vars = maybe_unserialize($action['merge-vars']);
    foreach ($merge_vars as $key => $value) {
      $action[$list_id . '_' . $key] = $value;
    }
  }

  //	93c8c814a4_group_8373_Group B
  if( isset( $action[ 'groups' ] ) ) {
    $groups = maybe_unserialize($action['groups']);
    foreach ($groups as $id => $group) {
      foreach ($group as $key => $name) {
        $action[$list_id . '_group_' . $id . '_' . $name] = 1;
      }
    }
  }

  if( isset( $action[ 'double-opt' ] ) ) {
    if ('yes' == $action['double-opt']) {
      $action['double_opt_in'] = 1;
      unset($action['double-opt']);
    }
  }

  return $action;
}
*/
