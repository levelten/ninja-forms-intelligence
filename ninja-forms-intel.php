<?php if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * Plugin Name: Ninja Forms - Intelligence
 * Plugin URI: https://ninjaforms.com/extensions/intelligence/
 * Description: Intelligent analytics for Ninja Forms
 * Version: 3.0.0
 * Author: LevelTen
 * Author URI: http://getlevelten.com/
 * Text Domain: nf_intel
 *
 * Copyright 2017-2018 LevelTen Interactive.
 */

if( version_compare( get_option( 'ninja_forms_version', '0.0.0' ), '3', '<' ) || get_option( 'ninja_forms_load_deprecated', FALSE ) ) {

  //include 'deprecated/ninja-forms-intel.php';

} else {

  /**
   * Class NF_Intel
   */
  final class NF_Intel {
    const VERSION = '3.0.0';
    const SLUG    = 'intelligence';
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

      add_filter( 'ninja_forms_field_settings_groups', array( $this, 'ninja_forms_field_settings_groups'));

      if (is_callable('intel')) {

        add_filter( 'ninja_forms_register_actions', array( $this, 'ninja_forms_register_actions' ) );

        add_action( 'ninja_forms_loaded', array( $this, 'ninja_forms_loaded' ) );

        add_filter( 'ninja_forms_post_run_action_type_save', array($this, 'ninja_forms_post_run_action_type_save'));

        add_filter( 'ninja_forms_display_after_form', array($this, 'ninja_forms_display_after_form'), 10, 2 );

        add_action( 'add_meta_boxes', array( $this, 'add_metaboxes' ) );

      }
      else {
        // Add pages for plugin setup
        add_action( 'admin_menu', array($this, 'intel_setup_menu'));
      }
    }

    public function ninja_forms_display_after_form($form_id, $is_preview = 0) {
      // this hook can be triggered without a form_id, so check if it exists
      if (empty($form_id)) {
        return;
      }
      $form = Ninja_Forms()->form( $form_id )->get();

      $actions = Ninja_Forms()->form( $form_id )->get_actions();

      $intel_action_settings = array();
      foreach ($actions as $action) {
        $action_settings = $action->get_settings();

        if ($action_settings['type'] == 'intel') {
          $intel_action_settings = $action_settings;
        }
      }

      $trackView = get_option('intel_form_track_view_default', '');
      if (!empty($intel_action_settings['intel_track_view'])) {
        $trackView = ($intel_action_settings['intel_track_view'] == '0') ? 0 : 1;
      }

      $def = array(
        'selector' => '#nf-form-' . $form_id . '-cont',
        'trackView' => $trackView,
        'formType' => 'ninjaform',
        'formTitle' => $form->get_setting( 'title' ),
        'formId' => $form_id,
      );
      //intel_add_page_intel_push(array('formtracker:trackForm', $def));

      print "<script>io('formtracker:trackForm', " . json_encode($def) . ");</script>";
    }

    /**
     * Initializes Intelligence form submission vars and adds it to the
     * NF submission data.
     *
     * @param $data form submission data
     * @param array $intel_settings intel action settings
     * @return mixed
     */
    public function add_form_submission_vars($data, $intel_settings = array()) {

      if (!empty($data['actions']['intel']['form_submission_vars'])) {
        $vars = $data['actions']['intel']['form_submission_vars'];
      }
      else {
        $vars = intel_form_submission_vars_default();
      }

      $submission = &$vars['submission'];
      $track = &$vars['track'];
      $visitor_properties = &$vars['visitor_properties'];

      $submission->type = 'ninjaform';
      $submission->fid = $data['form_id'];
      $submission->form_title = $data['settings']['title'];


      $vars['submission_values'] = array();
      if (!empty($data['fields']) && is_array($data['fields'])) {
        foreach ($data['settings']['formContentData'] as $field) {
          if (empty($field['settings']['key'])) {
            continue;
          }
          $key = $field['settings']['key'];
          $vars['submission_values'][$key] = $field['value'];
        }
      }

      if (!isset($intel_settings['intel_track_submission'])) {
        $intel_settings['intel_track_submission'] = get_option('intel_form_track_submission_default', 'form_submission');
      }
      if (!isset($intel_settings['intel_track_submission_value'])) {
        $intel_settings['intel_track_submission_value'] = get_option('intel_form_track_submission_value_default', '');
      }

      /*
      if (!isset($intel_settings['intel_tracking_event_name'])) {
        $intel_settings['intel_tracking_event_name'] = get_option('intel_form_submission_tracking_event_name_default', 'form_submission');
      }
      if (!isset($intel_settings['intel_tracking_event_value'])) {
        $intel_settings['intel_tracking_event_value'] = get_option('intel_form_submission_tracking_event_value_default', '');
      }
      */

      if (!empty($intel_settings) && is_array($intel_settings)) {

        if (!empty($intel_settings['intel_track_submission'])) {
          $track['name'] = $intel_settings['intel_track_submission'];
          if (substr($track['name'], -1) == '-') {
            $track['name'] = substr($track['name'], 0, -1);
            $track['valued_event'] = 0;
          }
          if (!empty($intel_settings['intel_track_submission_value'])) {
            $track['value'] = $intel_settings['intel_track_submission_value'];
          }
        }

        /*
        if (!empty($intel_settings['intel_tracking_event_name'])) {
          $track['name'] = $intel_settings['intel_tracking_event_name'];
          if (substr($track['name'], -1) == '-') {
            $track['name'] = substr($track['name'], 0, -1);
            $track['valued_event'] = 0;
          }
          if (!empty($intel_settings['intel_tracking_event_value'])) {
            $track['value'] = $intel_settings['intel_tracking_event_value'];
          }
        }
        */

        // process visitor_properties
        foreach ($intel_settings as $k => $v) {
          if (substr($k, 0, 11) != 'intel_prop_') {
            continue;
          }
          $prop_name = substr($k, 11);
          $visitor_properties[$prop_name] = $v;
        }
      }

      $data['actions']['intel']['form_submission_vars'] = $vars;

      return $data;
    }

    /*
     * Implements hook_ninja_forms_post_run_action_type_save()
     */
    public function ninja_forms_post_run_action_type_save($data) {

      // init intel form submission vars if not set
      if (empty($data['actions']['intel']['form_submission_vars'])) {
        $data = $this->add_form_submission_vars($data);
      }

      // save sub_id if set
      if (!empty($data['actions']['save']['sub_id'])) {
        $data['actions']['intel']['form_submission_vars']['submission']->fsid = $data['actions']['save']['sub_id'];
      }

      // Intel pushes need to be handled differently for ajax messages and
      // redirects need to be handled differently. Need to determine if this form
      // has a redirect action.
      $actions = Ninja_Forms()->form( $data['form_id'] )->get_actions();

      $mode = '';
      foreach ($actions as $k => $v) {
        $type = $v->get_setting('type');
        if ($type == 'redirect') {
          $mode = 'redirect';
        }
        if (!$mode && $type == 'successmessage') {
          $mode = 'successmessage';
        }
      }

      // if process has already been run, return
      if (!empty($data['actions']['intel']['process_form_submission'])) {
        return $data;
      }

      intel_process_form_submission($data['actions']['intel']['form_submission_vars']);

      // set flag that process_for_submission was run
      $data['actions']['intel']['process_form_submission'] = 1;

      // message mode, send events via javascript in message
      if ($mode == 'successmessage') {

        $script = intel()->tracker->get_pushes_script();
        if (!isset($data['actions']['success_message'])) {
          $data['actions']['success_message'] = '';
        }
        $data['actions']['success_message'] .= "\n$script";
      }
      // redirect, send event using cookie
      else {

        // save the page flushes to cache
        intel_save_flush_page_intel_pushes();
        /*
        // append cache busting query
        if (function_exists('intel_cache_busting_url')) {
          if (is_array($confirmation) && !empty($confirmation['redirect'])) {
            $confirmation['redirect'] = intel_cache_busting_url($confirmation['redirect']);
          }
        }
        */
      }

      // set flag that pushes have been processed
      $data['actions']['intel']['process_pushes'] = 1;

      return $data;
    }

    public function ninja_forms_post_run_action_type_successmessage($data) {
      return $data;
      /*
      if (empty($data['actions']['success_message'])) {
        $data['actions']['success_message'] = '';
      }

      if (empty($data['actions']['intel']['form_submission_vars'])) {
        $data = $this->add_form_submission_vars($data);
      }

      intel_process_form_submission($data['actions']['intel']['form_submission_vars']);

      // set flag that process_for_submission was run
      $data['actions']['intel']['process_form_submission'] = 1;

      $script = intel()->tracker->get_pushes_script();
      $data['actions']['success_message'] .= "\n$script";

      // set flag that pushes have been processed
      $data['actions']['intel']['process_pushes'] = 1;

      return $data;
      */
    }

    public function ninja_forms_after_submission($data) {
      return $data;
      /*
      if (!empty($data['actions']['intel']['pushes_sent'])) {
        return $data;
      }
      return $data;
      //Intel_Df::watchdog('nf_forms_after_submission', json_encode($data));
      if (!empty($data['actions']['intel']['form_submission_vars'])) {
        $form_submission_vars = $data['actions']['intel']['form_submission_vars'];
      }
      else {
        return;
      }

      // check if sub_id is set and save to submission->fsid
      if (!empty($data['actions']['save']['sub_id'])) {
        $form_submission_vars['submission']->fsid = $data['actions']['save']['sub_id'];
      }

      intel_process_form_submission($form_submission_vars);


      if( ! isset($data['actions'] ) || ! isset($data['actions']['success_message']) ) {
        $data['actions']['success_message'] = '';
      }

      $script = intel()->tracker->get_pushes_script();
      $data['actions']['success_message'] .= "\n$script";
      */
    }

    public function add_metaboxes() {
      if (is_callable('intel') && version_compare(INTEL_VER, '1.2.7', '>=')) {
        if (intel_is_api_level('pro') && get_option('intel_form_feedback_submission_profile', 1)) {
          add_meta_box('nf_intel_fields', Intel_Df::t('Intelligence'), array( $this, 'edit_sub_metabox' ), 'nf_sub', 'normal', 'low');
        }
      }
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
    public function ninja_forms_register_actions($actions) {
      $actions[ 'intel' ] = new NF_Intel_Actions_Intel();

      return $actions;
    }

    /**
     * Setup tracking field groups
     */
    public function ninja_forms_field_settings_groups($groups) {
      $groups['tracking'] = array(
        'id' => 'tracking',
        'label' => __( 'Submission tracking', 'nf_intel' ),
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

      if (!is_callable('intel')) {
        return;
      }

      if (!get_option('intel_form_feedback_submission_profile', 1)) {
        return;
      }

      // data function only available in Intel v1.2.7+
      if (version_compare(INTEL_VER, '1.2.7', '>=')) {
        _e('Please update Intelligence to version 1.2.7 to view this feature.', 'nf_intel');
        return;
      }


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
        _e('Submission entry not found.', 'nf_intel');
        return;
      }
      $submission = array_shift($submission);

      $vars = array(
        'page' => 'intel_admin',
        'q' => 'submission/' . $submission->get_id() . '/profile',
        'query' => array(
          'embedded' => 1,
        ),
        'current_path' => "wp-admin/post.php?post={$post->ID}&action=edit",
      );

      // json loading
      $return_type = 'xmarkup';
      if ($return_type == 'markup') {
        include_once INTEL_DIR . 'admin/intel.admin_submission.inc';
        $options = array(
          'embedded' => 1,
          'current_path' => "wp-admin/post.php?post={$post->ID}&action=edit"
        );
        $output = intel_submission_profile_page($submission, $options);
      }
      else {
        include_once INTEL_DIR . 'includes/intel.reports.inc';

        intel_add_report_headers();

        $output = intel_get_report_ajax_container($vars);
      }

      print $output;
      return;
    }

    public function is_intel_installed($level = 'min') {
      if (!is_callable('intel_is_installed')) {
        return FALSE;
      }
      return intel_is_installed($level);
    }

    public function is_setup() {
      is_callable('intel');
      return 0;
    }

    public function intel_setup_activated_plugin($plugin) {
      require_once( self::$dir . 'intel_com/intel.setup.inc' );
      intel_setup_activated_plugin($plugin);
    }

    public function intel_setup_menu() {
      global $wp_version;

      // check if intel is installed, if so exit
      if (is_callable('intel')) {
        return;
      }

      add_menu_page( esc_html__( "Intelligence", 'nf_intel' ), esc_html__( "Intelligence", 'nf_intel' ), 'manage_options', 'intel_admin', array( $this, 'intel_setup_page' ), version_compare( $wp_version, '3.8.0', '>=' ) ? 'dashicons-analytics' : '');
      add_submenu_page( 'intel_admin', esc_html__( "Setup", 'nf_intel' ), esc_html__( "Setup", 'nf_intel' ), 'manage_options', 'intel_admin', array( $this, 'intel_setup_page' ) );

      add_action( 'activated_plugin', array( $this, 'intel_setup_activated_plugin') );
    }

    public function intel_setup_page() {
      if (!empty($_GET['plugin']) && $_GET['plugin'] != 'nf_intel') {
        return;
      }
      $output = $this->setup_intel_plugin_instructions();

      print $output;
    }

    function setup_intel_plugin_instructions($options = array()) {

      require_once( self::$dir . 'intel_com/intel.setup.inc' );

      $plugin_un = 'nf_intel';

      // initialize setup state option
      $intel_setup = get_option('intel_setup', array());
      $intel_setup['active_path'] = 'admin/config/intel/settings/setup/' . $plugin_un;
      update_option('intel_setup', $intel_setup);

      intel_setup_set_activated_option('intelligence', array('destination' => $intel_setup['active_path']));

      $items = array();

      $items[] = '<h1>' . __('Ninja Forms Intelligence Setup', $plugin_un) . '</h1>';
      $items[] = __('To continue with the setup please install the Intelligence plugin.', $plugin_un);

      $items[] = "<br>\n<br>\n";

      $vars = array(
        'plugin_slug' => 'intelligence',
        'card_class' => array(
          'action-buttons-only'
        ),
        //'activate_url' => $activate_url,
      );
      $vars = intel_setup_process_install_plugin_card($vars);

      $items[] = '<div class="intel-setup">';
      $items[] = intel_setup_theme_install_plugin_card($vars);
      $items[] = '</div>';

      return implode(' ', $items);
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

/**
 * Implements hook_register_activation_hook()
 */
function nf_intel_activation() {
  if (is_callable('intel_activate_plugin')) {
    intel_activate_plugin('nf_intel');
  }
}
register_activation_hook( __FILE__, 'nf_intel_activation' );

/**
 * Implements hook_register_uninstall_hook()
 */
function _nf_intel_uninstall() {
  require_once plugin_dir_path( __FILE__ ) . 'ninja-forms-intel.install';
  nf_intel_uninstall();
}
register_uninstall_hook( __FILE__, '_nf_intel_uninstall' );

/**
 * Implements hook_intel_system_info()
 *
 * Registers plugin with intel_system
 *
 * @param array $info
 * @return array
 */
function nf_intel_intel_system_info($info = array()) {
  $info['nf_intel'] = array(
    'plugin_file' => 'ninja-forms-intel.php', // Main plugin file
    'plugin_path' => NF_Intel::$dir, // The path to the directory containing file
    'update_file' => 'ninja-forms-intel.install', // default [plugin_un].install
  );
  return $info;
}
add_filter('intel_system_info', 'nf_intel_intel_system_info');

/**
 * Implements hook_intel_form_type_forms_info()
 */
function nf_intel_form_type_forms_info($info) {
  $info['ninjaforms'] = Ninja_Forms()->form()->get_forms();
  return $info;
}
// Register hook_intel_form_type_forms_info()
add_filter('intel_form_type_forms_info', 'nf_intel_form_type_forms_info');

/*
 * Implements hook_intel_form_type_form_setup()
 */
function nf_intel_form_type_form_setup($data, $info) {

  $data['id'] = $info->get_id();
  $data['title'] = $info->get_setting('title', Intel_Df::t('(not set)'));
  $data['type_label'] = __( 'Ninja Forms', 'ninja-forms' );
  $data['settings_url'] = '/wp-admin/admin.php?page=ninja-forms&form_id=' . $data['id'];

  $actions = Ninja_Forms()->form( $data['id'] )->get_actions();

  $labels = intel_get_form_submission_eventgoal_options();
  foreach ($actions as $action) {
    $action_settings = $action->get_settings();
    if ($action_settings['type'] == 'intel') {

      if (!empty($action_settings['intel_tracking_event_name'])) {
        //$labels = gf_intel_intl_eventgoal_labels();

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
// Register hook_intel_form_type_form_setup()
add_filter('intel_form_type_ninjaforms_form_setup', 'nf_intel_form_type_form_setup', 10, 2);

/**
 * Implements hook_intel_form_type_info()
 */
function nf_intel_form_type_info($info = array()) {
  $info['ninjaform'] = array(
    'title' => __( 'Ninja Form', 'ninja-form' ),
    'plugin' => array(
      'name' => __( 'Ninja Forms', 'ninja-forms' ),
      'slug' => 'ninja-forms',
      'text_domain' => 'ninja-forms',
    ),
    'supports' => array(
      'track_submission' => 1,
      'track_submission_value' => 1,
      'track_view' => 1,
    ),
    'submission_data_callback' => 'nf_intel_form_type_submission_data',
  );
  return $info;
}
// Register hook_intel_form_type_forms_info()
add_filter('intel_form_type_info', 'nf_intel_form_type_info');

/**
 * Implements hook_intel_form_type_FORM_TYPE_UN_form_data()
 */
function nf_intel_form_type_form_info($data = NULL, $options = array()) {
  $data = &Intel_Df::drupal_static( __FUNCTION__, array());
  if (!empty($data) && empty($options['refresh'])) {
    return $data;
  }
  $ninja_forms = Ninja_Forms()->form()->get_forms();
  $intel_eventgoal_options = intel_get_form_submission_eventgoal_options();
  foreach ($ninja_forms as $k => $form) {
    $row = array();
    $row['id'] = $form->get_id();
    $row['title'] = $form->get_setting('title', Intel_Df::t('(not set)'));
    $row['settings_url'] = '/wp-admin/admin.php?page=ninja-forms&form_id=' . $row['id'];
    $row['settings'] = array();

    $actions = Ninja_Forms()->form( $row['id'] )->get_actions();

    foreach ($actions as $action) {
      $action_settings = $action->get_settings();

      if ($action_settings['type'] == 'intel') {
        if (isset($action_settings['intel_track_submission'])) {
          $name = $action_settings['intel_track_submission'];
          $row['settings']['track_submission'] = $name;
          $row['settings']['track_submission__title'] = !empty($intel_eventgoal_options[$name]) ? $intel_eventgoal_options[$name] : $name;
        }
        if (isset($action_settings['intel_track_submission_value'])) {
          $row['settings']['track_submission_value'] = $action_settings['intel_track_submission_value'];
        }
        if (isset($action_settings['intel_track_view'])) {
          $row['settings']['track_view'] = $action_settings['intel_track_view'];
        }
      }
    }

    $data[$row['id']] = $row;
  }

  return $data;
}
// Register hook_intel_form_type_forms_info()
add_filter('intel_form_type_ninjaform_form_info', 'nf_intel_form_type_form_info');


/*
 * Implements hook_intel_form_type_form_setup()
 */
function nf_intel_form_type_submission_data($fid, $fsid) {

  $form_info = nf_intel_form_type_form_info($data = NULL);

  $data = array();

  if (!empty($form_info[$fid])) {
    $data['form_title'] = $form_info[$fid]['title'];
  }

  $data['submission_data_url'] = "wp-admin/post.php?post=$fsid&action=edit";
  $data['field_values'] = array();
  $data['field_titles'] = array();

  $sub = Ninja_Forms()->form()->sub( $fsid )->get();

  $field_values = $sub->get_field_values();

  $field_models = Ninja_Forms()->form( $fid )->get_fields();

  $field_model_keys = array();
  foreach ($field_models as $k => $v) {
    $i = $v->get_setting( 'key' );
    $field_model_keys[$i] = $k;
  }

  foreach ($field_values as $k => $v) {
    if (substr($k, 0, 1) == '_') {
      continue;
    }
    // some keys have a trailing numeric id, we want to remove this to normalize
    // the data keys.
    $nk = $k;
    $a = explode('_', $k);
    $a_last = array_pop($a);
    if (count($a) >= 1 && strlen($a_last) > 12 && is_numeric($a_last)) {
      $nk = implode('_', $a);
    }
    $data['field_values'][$nk] = $v;

    if (!empty($field_models[$field_model_keys[$k]])) {
      $field_model = $field_models[$field_model_keys[$k]];
      $data['field_titles'][$nk] = $field_model->get_setting( 'label' );

      $field_type = $field_model->get_settings( 'type' );
      if ($field_type == 'email') {
        $data['field_values'][$nk] = Intel_Df::l($data['field_values'][$nk], 'mailto:' . $data['field_values'][$nk]);
      }


    }
  }

  return $data;
}

/**
 * Implements hook_intel_url_urn_resolver()
 */
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
// Register hook_intel_url_urn_resolver()
add_filter('intel_url_urn_resovler', 'nf_intel_url_urn_resovler');

/**
 * Implements hook_intel_test_url_parsing_alter()
 */
function nf_intel_test_url_parsing_alter($urls) {
  $urls[] = ':ninjaform:1';
  $urls[] = 'urn::ninjaform:1';
  $urls[] = ':ninjaform:1:1';
  $urls[] = 'urn::ninjaform:1:1';
  return $urls;
}
// Register hook_intel_test_url_parsing_alter()
add_filter('intel_test_url_parsing_alter', 'nf_intel_test_url_parsing_alter');


/**
 *  Implements of hook_intel_menu()
 */
function nf_intel_menu($items = array()) {
  $items['admin/config/intel/settings/setup/nf_intel'] = array(
    'title' => 'Setup',
    'description' => Intel_Df::t('Ninja Forms Intelligence initial plugin setup'),
    'page callback' => 'nf_intel_admin_setup_page',
    //'page callback' => 'drupal_get_form',
    //'page arguments' => array('nf_intel_admin_setup'),
    'access callback' => 'user_access',
    'access arguments' => array('admin intel'),
    'type' => Intel_Df::MENU_LOCAL_ACTION,
    //'weight' => $w++,
    'file' => 'admin/nf_intel.admin_setup.inc',
    'file path' => NF_Intel::$dir,
  );
  return $items;
}
// Register hook_intel_menu()
add_filter('intel_menu_info', 'nf_intel_menu');