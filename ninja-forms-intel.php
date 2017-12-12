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

  //include 'deprecated/ninja-forms-intel.php';

} else {

  /**
   * Class NF_Intel
   */
  final class NF_Intel {
    const VERSION = '3.0.3';
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
     * Main Plugin Instance
     *
     * Insures that only one instance of a plugin class exists in memory at any one
     * time. Also prevents needing to define globals all over the place.
     *
     * @since 3.0
     * @static
     * @static var array $instance
     * @return NF_Intel Instance
     */
    public static function instance() {

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
     * NF_Intel constructor.
     *
     */
    public function __construct() {

      //add_action( 'admin_init', array( $this, 'setup_license' ) );
      //add_filter( 'ninja_forms_register_fields', array( $this, 'register_fields' ) );
      add_filter( 'ninja_forms_register_actions', array( $this, 'register_actions' ) );

      add_filter( 'ninja_forms_field_settings_groups', array( $this, 'field_settings_groups'));

      add_action( 'ninja_forms_loaded', array( $this, 'ninja_forms_loaded' ) );


      // Add our metabox for editing field values
      add_action( 'add_meta_boxes', array( $this, 'add_metaboxes' ) );

      if (!$this->is_setup()) {
        add_action( 'admin_menu', array($this, 'site_menu'));
      }

//Intel_Df::watchdog('NF_Intel construct', 'ok');
    }

    public function add_metaboxes() {
      add_meta_box( 'nf_intel_fields', __( 'Intelligence', 'ninja-forms-intel' ), array( $this, 'edit_sub_metabox' ), 'nf_sub', 'normal', 'low');
    }

    public function ninja_forms_loaded() {
      new NF_Intel_Admin_Metaboxes_Submission();
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
        'label' => __( 'Submission tracking', 'ninja-forms-intel' ),
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
    public function setup_license() {
      if ( ! class_exists( 'NF_Extension_Updater' ) ) return;

      new NF_Extension_Updater( self::NAME, self::VERSION, self::AUTHOR, __FILE__, self::SLUG );
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
    public static function template( $file_name = '', array $data = array() ) {
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
    public static function config( $file_name ) {
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

      // enqueue admin styling & scripts
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
          <h4 class="card-header"><?php print __('Submitter profile', 'ninjaformsintel'); ?></h4>
          <?php print $output; ?>
          <!-- <h4 class="card-header"><?php print __('Analytics', 'ninjaformsintel'); ?></h4> -->
          <div class="card-deck-wrapper m-b-1">
            <div class="card-deck">
              <?php print Intel_Df::theme('intel_trafficsource_block', array('trafficsource' => $submission->data['analytics_session']['trafficsource'])); ?>
              <?php print Intel_Df::theme('intel_location_block', array('entity' => $submission)); ?>
              <?php print Intel_Df::theme('intel_browser_environment_block', array('entity' => $submission)); ?>
            </div>
          </div>
          <?php print Intel_Df::theme('intel_visitor_profile_block', array('title' => __('Visit chronology', 'ninjaformsintel'), 'markup' => $steps_table, 'no_margin' => 1)); ?>
        </div>
      </div>
      <?php
      return;
    }

    public function is_intel_active() {
      static $flag;
      if (!isset($flag)) {
        $flag = is_callable('intel');
      }
      return $flag;
    }

    public function is_setup() {
      return 0;
    }

    public function site_menu() {
      global $wp_version;
      if ( !$this->is_setup() && current_user_can( 'manage_options' ) ) {
        if (!$this->is_intel_active()) {
          add_menu_page( esc_html__( "Intelligence", 'ninja-forms-intel' ), esc_html__( "Intelligence", 'ninja-forms-intel' ), 'manage_options', 'intel_admin', array( $this, 'menu_router' ), version_compare( $wp_version, '3.8.0', '>=' ) ? 'dashicons-analytics' : '');
        }

        add_submenu_page( 'intel_admin', esc_html__( "Setup", 'ninja-forms-intel' ), esc_html__( "Setup", 'ninja-forms-intel' ), 'manage_options', 'intel_setup', array( $this, 'menu_router' ) );
      }
    }

    public function menu_router() {

      require_once( self::$dir . 'intel_setup/intel.setup.inc' );
      if (!empty($_GET['install'])) {
        $this->install_intel();
      }

      if ($this->is_intel_active()) {
        //$items[] = Intel_Df::l( esc_html__('Continue', 'ninja-forms-intel') );
        Intel_Df::drupal_goto('admin/config/intel/settings/setup/nf_intel');
        return;
      }

      $items = array();
      $items[] = '<h1>' . __('Ninja Forms Intelligence Setup', 'ninja-forms-intel') . '</h1>';
      $items[] = __('To continue with the setup please install the Intelligence plugin.');

      $items[] = "<br>\n<br>\n";

      $vars = array(
        'plugin_slug' => 'intelligence',
        'card_class' => array(
          'action-buttons-only'
        ),
      );
      $vars = intel_setup_process_install_plugin_card($vars);

      $items[] = '<div class="intel-setup">';
      $items[] = intel_setup_theme_install_plugin_card($vars);
      $items[] = '</div>';

      $output = implode("\n", $items);

      print $output;
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

add_filter('intel_form_type_forms_info', 'nf_intel_form_type_forms_info');
function nf_intel_form_type_forms_info($info) {
  $info['ninjaforms'] = Ninja_Forms()->form()->get_forms();
  return $info;
}

add_filter('intel_form_type_ninjaforms_form_setup', 'nf_intel_form_type_form_setup', 0, 2);
function nf_intel_form_type_form_setup($data, $info) {

  $data['id'] = $info->get_id();
  $data['title'] = $info->get_setting('title', Intel_Df::t('(not set)'));
  $data['type_label'] = __( 'Ninja Forms', 'ninja-forms' );
  $data['settings_url'] = '/wp-admin/admin.php?page=ninja-forms&form_id=' . $data['id'];

  $actions = Ninja_Forms()->form( $data['id'] )->get_actions();

  foreach ($actions as $action) {
    $action_settings = $action->get_settings();
    if ($action_settings['type'] == 'intel') {

      if (!empty($action_settings['intel_tracking_event_name'])) {
        $labels = gf_intel_intl_eventgoal_labels();
        $name = $action_settings['intel_tracking_event_name'];
        $data['tracking_event_name'] = $data['tracking_event'] = !empty($labels[$name]) ? $labels[$name] : $name;
      }
      if (!empty($action_settings['intel_tracking_event_value'])) {
        $data['tracking_event_value'] = $action_settings['intel_tracking_event_value'];
      }
    }

  }

  return $data;
}

/**
 * Implements hook_intel_url_urn_resolver()
 */
add_filter('intel_url_urn_resovler', 'nf_intel_url_urn_resovler');
function nf_intel_url_urn_resovler($vars) {
  $urn_elms = explode(':', $vars['path']);
  if ($urn_elms[0] == 'urn') {
    array_shift($urn_elms);
  }
  if ($urn_elms[0] == '') {
    if ($urn_elms[1] == 'ninjaform' && !empty($urn_elms[2])) {
      $vars['path'] = 'wp-admin/post.php';
      $vars['options']['query']['action'] = 'edit';
      $vars['options']['query']['post'] = $urn_elms[2];
    }
  }

  return $vars;
}

/**
 * Implements hook_intel_test_url_parsing_alter()
 */
add_filter('intel_test_url_parsing_alter', 'nf_intel_test_url_parsing_alter');
function nf_intel_test_url_parsing_alter($urls) {
  $urls[] = ':ninjaform:1';
  $urls[] = 'urn::ninjaform:1';
  $urls[] = ':ninjaform:1:1';
  $urls[] = 'urn::ninjaform:1:1';
  return $urls;
}

// add intel_menu to hook_intel_menu_info
add_filter('intel_menu_info', 'nf_intel_menu');
/**
 *  Implements of hook_menu()
 */
function nf_intel_menu($items = array()) {
  $items['admin/config/intel/settings/setup/nf_intel'] = array(
    'title' => 'Setup',
    'description' => Intel_Df::t('Ninja Forms Intelligence initial plugin setup'),
    'page callback' => 'drupal_get_form',
    'page arguments' => array('nf_intel_admin_setup'),
    'access callback' => 'user_access',
    'access arguments' => array('admin intel'),
    'type' => Intel_Df::MENU_LOCAL_ACTION,
    //'weight' => $w++,
    'file' => 'admin/nf_intel.admin_setup.inc',
    'file path' => NF_Intel::$dir,
  );
  return $items;
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
