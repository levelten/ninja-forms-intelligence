<?php if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * Plugin Name: Ninja Forms - Intelligence
 * Plugin URI: https://ninjaforms.com/extensions/intelligence/
 * Description: Intelligent analytics for Ninja Forms
 * Version: 3.0.4
 * Author: LevelTen
 * Author URI: http://getlevelten.com/
 * Text Domain: nf_intel
 *
 * Copyright 2017-2018 LevelTen Interactive.
 */

define('NF_INTEL_VER', '3.0.4');

if( version_compare( get_option( 'ninja_forms_version', '0.0.0' ), '3', '<' ) || get_option( 'ninja_forms_load_deprecated', FALSE ) ) {

  //include 'deprecated/ninja-forms-intel.php';

} else {

  /**
   * Class NF_Intel
   */
  final class NF_Intel {
    const VERSION = NF_INTEL_VER;
    const SLUG = 'intelligencewp';
    const NAME = 'Intelligence';
    const AUTHOR = 'LevelTen';
    const PREFIX = 'NF_Intel';

    /**
     * Intel plugin unique name
     * @var string
     */
    public $plugin_un = 'nf_intel';

    /**
     * Intel form type unique name
     * @var string
     */
    public $form_type_un = 'ninjaform';

    /**
     * Plugin Directory
     *
     * @since 3.0.0
     * @var string $dir
     */
    public $dir = '';

    /**
     * Plugin URL
     *
     * @since 3.0.0
     * @var string $url
     */
    public $url = '';

    /**
     * @var array
     * @since 3.0.0
     */
    public $plugin_info = array();

    /**
     * @var NF_Intel
     * @since 3.0
     */
    private static $instance;

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

      if (!isset(self::$instance) && !(self::$instance instanceof NF_Intel)) {
        self::$instance = new NF_Intel();

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

      $this->plugin_info = $this->intel_plugin_info();

      $this->dir = plugin_dir_path(__FILE__);

      $this->url = plugin_dir_url(__FILE__);


      /**
       * Ninja Forms hooks
       */

      add_filter('ninja_forms_field_settings_groups', array(
        $this,
        'ninja_forms_field_settings_groups'
      ));

      if ($this->is_intel_installed()) {

        add_filter('ninja_forms_register_actions', array(
          $this,
          'ninja_forms_register_actions'
        ));

        add_action('ninja_forms_loaded', array($this, 'ninja_forms_loaded'));

        add_filter('ninja_forms_post_run_action_type_save', array(
          $this,
          'ninja_forms_post_run_action_type_save'
        ));

        add_filter('ninja_forms_display_after_form', array(
          $this,
          'ninja_forms_display_after_form'
        ), 10, 2);

        add_action('add_meta_boxes', array($this, 'add_metaboxes'));

      }
      else {
        // Add pages for plugin setup
        add_action('wp_loaded', array($this, 'wp_loaded'));
      }

      /**
       * Intelligence hooks
       */

      // Register hook_intel_system_info()
      add_filter('intel_system_info', array( $this, 'intel_system_info' ));

      // Register hook_intel_menu()
      add_filter('intel_menu_info', array( $this, 'intel_menu_info' ));

      // Register hook_intel_demo_pages()
      add_filter('intel_demo_posts', array( $this, 'intel_demo_posts' ));

      // Register hook_intel_form_type_info()
      add_filter('intel_form_type_info', array( $this, 'intel_form_type_info'));

      // Register hook_intel_form_type_FORM_TYPE_UN_form_info()
      add_filter('intel_form_type_' . $this->form_type_un . '_form_info', array( $this, 'intel_form_type_form_info' ));

      // Register hook_intel_url_urn_resolver()
      add_filter('intel_url_urn_resolver', array( $this, 'intel_url_urn_resolver'));

      // Register hook_intel_test_url_parsing_alter()
      add_filter('intel_test_url_parsing_alter', array( $this, 'intel_test_url_parsing_alter'));

      // Register hook_form_FORM_ID_alter() for FORM_ID=
      add_action('intel_form_alter', array($this, 'intel_form_intel_visitor_delete_confirm_form_alter'), 10, 2);
    }

    public function ninja_forms_display_after_form($form_id, $is_preview = 0) {
      // this hook can be triggered without a form_id, so check if it exists
      if (empty($form_id)) {
        return;
      }
      $form = Ninja_Forms()->form($form_id)->get();

      $actions = Ninja_Forms()->form($form_id)->get_actions();

      $intel_action_settings = array();
      foreach ($actions as $action) {
        $action_settings = $action->get_settings();

        if ($action_settings['type'] == 'intel') {
          $intel_action_settings = $action_settings;
        }
      }

      $trackView = get_option('intel_form_track_view_default', '');
      if (isset($intel_action_settings['intel_track_view'])) {
        if ($intel_action_settings['intel_track_view'] == '_0') {
          $intel_action_settings['intel_track_view'] = '0';
        }
        $trackView = ($intel_action_settings['intel_track_view'] == '0') ? 0 : 1;
      }

      $def = array(
        'selector' => '#nf-form-' . $form_id . '-cont',
        'trackView' => $trackView,
        'formType' => $this->form_type_un,
        'formTitle' => $form->get_setting('title'),
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

      $submission->type = $this->form_type_un;
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

      if (!empty($intel_settings) && is_array($intel_settings)) {

        if (!empty($intel_settings['intel_track_submission'])) {
          if ($intel_settings['intel_track_submission'] == '_0') {
            $intel_settings['intel_track_submission'] == '0';
          }
          if (!empty($intel_settings['intel_track_submission']) && $intel_settings['intel_track_submission'] != '0') {
            $track['name'] = $intel_settings['intel_track_submission'];

            if (substr($track['name'], -1) == '-') {
              $track['name'] = substr($track['name'], 0, -1);
              $track['valued_event'] = 0;
            }
            if (!empty($intel_settings['intel_track_submission_value'])) {
              $track['value'] = $intel_settings['intel_track_submission_value'];
            }
          }

        }

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
      $actions = Ninja_Forms()->form($data['form_id'])->get_actions();

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
          add_meta_box('nf_intel_fields', Intel_Df::t('Intelligence'), array(
            $this,
            'edit_sub_metabox'
          ), 'nf_sub', 'normal', 'low');
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
      $actions['intel'] = new NF_Intel_Actions_Intel();

      return $actions;
    }

    /**
     * Setup tracking field groups
     */
    public function ninja_forms_field_settings_groups($groups) {
      $groups['tracking'] = array(
        'id' => 'tracking',
        'label' => __('Submission tracking', 'nf_intel'),
        'priority' => 600,
      );
      return $groups;
    }

    /**
     * Autoloader
     *
     * @param $class_name
     */
    public function autoloader($class_name) {
      if (class_exists($class_name)) {
        return;
      }

      if (FALSE === strpos($class_name, self::PREFIX)) {
        return;
      }

      $class_name = str_replace(self::PREFIX, '', $class_name);
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
      if (!class_exists('NF_Extension_Updater')) {
        return;
      }

      new NF_Extension_Updater(self::NAME, self::VERSION, self::AUTHOR, __FILE__, self::SLUG);
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
    public static function template($file_name = '', array $data = array()) {
      if (!$file_name) {
        return;
      }

      extract($data);

      include self::$instance->dir . 'includes/Templates/' . $file_name;
    }

    /**
     * Load Config File
     *
     * @param $file_name
     * @return array
     */
    public static function config($file_name) {
      return include self::$instance->dir . 'includes/Config/' . $file_name . '.php';
    }

    /**
     * Output our field editing metabox to the CPT editing page.
     *
     * @access public
     * @since 2.7
     * @return void
     */
    public function edit_sub_metabox($post) {
      global $ninja_forms_fields;

      if (!is_callable('intel')) {
        return;
      }

      if (!get_option('intel_form_feedback_submission_profile', 1)) {
        return;
      }


      $notice_vars = array(
        'inline' => 1,
      );

      // data function only available in Intel v1.2.7+
      if (!version_compare(INTEL_VER, '1.2.7', '>=')) {
        $notice_vars['message'] = __('Please update Intelligence to version 1.2.7 to view this feature.', 'nf_intel');
        print Intel_Df::theme('wp_notice', $notice_vars);
        return;
      }

      if (!intel_is_installed('ga_data')) {
        $notice_vars['message'] = intel_get_install_access_error_message(array('level' => 'ga_data', 'object_name' => __('data')));
        print Intel_Df::theme('wp_notice', $notice_vars);
        return;
      }


      // enqueue admin styling & scripts
      intel()->admin->enqueue_styles();
      intel()->admin->enqueue_scripts();

      $post_meta = get_post_meta($post->ID);
      $vars = array(
        'type' => $this->form_type_un,
        'fid' => get_post_meta($post->ID, '_form_id', TRUE),
        //'fsid' => get_post_meta($post->ID, '_seq_num', TRUE),
        'fsid' => $post->ID,
      );

      $submission = intel()
        ->get_entity_controller('intel_submission')
        ->loadByVars($vars);
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
        include_once INTEL_DIR . 'admin/intel.admin_submission.php';
        $options = array(
          'embedded' => 1,
          'current_path' => "wp-admin/post.php?post={$post->ID}&action=edit"
        );
        $output = intel_submission_profile_page($submission, $options);
      }
      else {
        include_once INTEL_DIR . 'includes/intel.reports.php';

        intel_add_report_headers();

        $output = intel_get_report_ajax_container($vars);
      }

      print $output;
      return;
    }

    /**
     * Implements hook_wp_loaded()
     *
     * Used to check if Intel is not loaded and include setup process if needed.
     * Alternatively this check can be done in hook_admin_menu() if the plugin
     * implements hook_admin_menu()
     */
    public function wp_loaded() {
      // check if Intel is installed, add setup processing if not
      if (!$this->is_intel_installed()) {
        require_once( $this->dir . 'ninja-forms-intel.setup.php' );
      }
    }

    /**
     * Returns if Intelligence plugin is installed and setup.
     *
     * @param string $level
     * @return mixed
     *
     * @see intel_is_installed()
     */
    public function is_intel_installed($level = 'min') {
      if (!is_callable('intel_is_installed')) {
        return FALSE;
      }
      return intel_is_installed($level);
    }

    /**
     * Provides plugin data for hook_intel_system_info()
     *
     * @param array $info
     * @return array
     *
     * @see Intel::system_info()
     */
    function intel_plugin_info($info = array()) {
      $info = array(
        // The unique name for this plugin
        'plugin_un' => $this->plugin_un,
        // Plugin version
        'plugin_version' => NF_INTEL_VER,
        // Title of the plugin
        'plugin_title' => __('Ninja Forms Google Analytics Intelligence', $this->plugin_un),
        // Shorter version of title used when reduced characters are desired
        'plugin_title_short' => __('Ninja Forms GA Intelligence', $this->plugin_un),
        // Plugin slug - name of directory containing plugin
        'plugin_slug' => 'ninja-forms-intelligencewp',
        // Main plugin file
        'plugin_file' => 'ninja-forms-intel.php', // Main plugin file
        // The server path to the plugin files directory
        'plugin_dir' => $this->dir,
        // The browser path to the plugin files directory
        'plugin_url' => $this->url,
        // The install file for the plugin if different than [plugin_un].install
        // Used to auto discover database updates
        'update_file' => 'ninja-forms-intel.install.php', // default [plugin_un].install
        // If this plugin extends a plugin other than Intelligence, include that
        // plugin's info in 'extends_' properties
        // The extends plugin unique name
        'extends_plugin_un' => 'ninja_forms',
        // the extends plugin text domain key
        'extends_plugin_text_domain' => 'ninja-forms',
        // the extends plugin title
        'extends_plugin_title' => __('Ninja Forms', 'ninja-forms'),
      );
      return $info;
    }

    /**
     * Implements hook_intel_system_info()
     *
     * Registers plugin with intel_system
     *
     * @param array $info
     * @return array
     */
    public function intel_system_info($info = array()) {
      // array of plugin info indexed by plugin_un
      $info[$this->plugin_un] = $this->intel_plugin_info();
      return $info;
    }

    /**
     * Implements hook_intel_menu_info()
     *
     * @param array $items
     * @return array
     */
    public function intel_menu_info($items = array()) {
      // route for Admin > Intelligence > Settings > Setup > Ninja Forms
      $items['admin/config/intel/settings/setup/' . $this->plugin_un] = array(
        'title' => 'Setup',
        'description' => $this->plugin_info['plugin_title'] . ' ' . __('initial plugin setup', $this->plugin_un),
        'page callback' => $this->plugin_un . '_admin_setup_page',
        'access callback' => 'user_access',
        'access arguments' => array('admin intel'),
        'type' => Intel_Df::MENU_LOCAL_TASK,
        'file' => 'admin/' . $this->plugin_un . '.admin_setup.php',
        'file path' => $this->dir,
      );
      // rout for Admin > Intelligence > Help > Demo > Ninja Forms
      $items['admin/help/demo/' . $this->plugin_un] = array(
        'title' => $this->plugin_info['extends_plugin_title'],
        'page callback' => array($this, 'intel_admin_help_demo_page'),
        'access callback' => 'user_access',
        'access arguments' => array('admin intel'),
        'intel_install_access' => 'min',
        'type' => Intel_Df::MENU_LOCAL_TASK,
        'weight' => 10,
      );
      return $items;
    }

    /*
     * Provides an Intelligence > Help > Demo > Example page
     */
    public function intel_admin_help_demo_page() {
      $output = '';

      $demo_mode = get_option('intel_demo_mode', 0);

      // function introduced in intel 1.2.8, check it exists.
      if (is_callable('intel_is_current_user_tracking_excluded') && intel_is_current_user_tracking_excluded()) {
        $notice_vars = array(
          'inline' => 1,
          'type' => 'warning',
        );
        $notice_vars['message'] = __('Your user is set to be excluded from tracking on all web pages except Intelligence demo pages.', $this->plugin_un);
        $notice_vars['message'] .= ' ' . __('If you submit a form that redirects to a non demo page tracking will be disabled.', $this->plugin_un);
        $notice_vars['message'] .= ' ' . __('Either test only non-redirected forms, with a non-excluded user or', $this->plugin_un);
        $l_options = Intel_Df::l_options_add_target('intel_admin');
        $l_options = Intel_Df::l_options_add_destination(Intel_Df::current_path());
        $notice_vars['message'] .= ' ' . Intel_Df::l(__('change the exclude settings', $this->plugin_un), 'admin/config/intel/settings/general', $l_options) . '.';

        //$output .= Intel_Df::theme('wp_notice', $notice_vars);

        $output .= '<div class="alert alert-info">' . $notice_vars['message'] . '</div>';
      }

      $output .= '<div class="card">';
      $output .= '<div class="card-block clearfix">';

      $output .= '<p class="lead">';
      $output .= Intel_Df::t('Try out your Ninja Forms tracking!');
      //$output .= ' ' . Intel_Df::t('This tutorial will walk you through the essentials of extending Google Analytics using Intelligence to create results oriented analytics.');
      $output .= '</p>';

      /*
      $l_options = Intel_Df::l_options_add_class('btn btn-info');
      $l_options = Intel_Df::l_options_add_destination(Intel_Df::current_path(), $l_options);
      $output .= Intel_Df::l( Intel_Df::t('Demo settings'), 'admin/config/intel/settings/general/demo', $l_options) . '<br><br>';
      */

      $output .= '<div class="row">';
      $output .= '<div class="col-md-6">';
      $output .= '<p>';
      $output .= '<h3>' . Intel_Df::t('First') . '</h3>';
      $output .= __('Launch Google Analytics to see conversions in real-time:', $this->plugin_un);
      $output .= '</p>';

      $output .= '<div>';
      $l_options = Intel_Df::l_options_add_target('ga');
      $l_options = Intel_Df::l_options_add_class('btn btn-info m-b-_5', $l_options);
      $url = 	$url = intel_get_ga_report_url('rt_goal');
      $output .= Intel_Df::l( Intel_Df::t('View real-time conversion goals'), $url, $l_options);

      $output .= '<br>';

      $l_options = Intel_Df::l_options_add_target('ga');
      $l_options = Intel_Df::l_options_add_class('btn btn-info m-b-_5', $l_options);
      $url = 	$url = intel_get_ga_report_url('rt_event');
      $output .= Intel_Df::l( Intel_Df::t('View real-time events'), $url, $l_options);
      $output .= '</div>';
      $output .= '</div>'; // end col-x-6

      $output .= '<div class="col-md-6">';

      $output .= '<p>';
      $output .= '<h3>' . Intel_Df::t('Next') . '</h3>';
      $output .= __('Pick one of your forms to test:', $this->plugin_un);
      $output .= '</p>';

      $forms = $this->intel_form_type_form_info();

      $l_options = Intel_Df::l_options_add_target($this->plugin_un . '_demo');
      $l_options = Intel_Df::l_options_add_class('btn btn-info m-b-_5', $l_options);
      $l_options['query'] = array();
      $output .= '<div>';
      foreach ($forms as $form) {
        $l_options['query']['fid'] = $form['id'];
        $output .= Intel_Df::l( __('Try', $this->plugin_un) . ': ' . $form['title'], 'intelligence/demo/' . $this->plugin_un, $l_options);
        $output .= '<br>';
      }
      $output .= '</div>';

      $output .= '</div>'; // end col-x-6
      $output .= '</div>'; // end row

      $output .= '</div>'; // end card-block
      $output .= '</div>'; // end card

      // Demo mode alert
      $notice_vars = array(
        'inline' => 1,
        'type' => 'info',
      );
      $mode = $demo_mode ? __('enabled') : __('disabled');
      $notice_vars['message'] = __('Demo pages for anonymous users are currently ', $this->plugin_un) . '<strong>' . $mode . '</strong>.';
      $l_options = Intel_Df::l_options_add_class('btn btn-default');
      $l_options = Intel_Df::l_options_add_destination(Intel_Df::current_path(), $l_options);
      $notice_vars['message'] .= ' ' . Intel_Df::l(__('Change demo settings', $this->plugin_un), 'admin/config/intel/settings/general/demo', $l_options);

      //$output .= Intel_Df::theme('wp_notice', $notice_vars);

      $output .= '<div class="alert alert-info">' . $notice_vars['message'] . '</div>';

      return $output;
    }

    /**
     * Implements hook_intel_demo_pages()
     *
     * Adds a demo page to test tracking for this plugin.
     *
     * @param array $posts
     * @return array
     */
    public function intel_demo_posts($posts = array()) {
      $id = -1 * (count($posts) + 1);

      $forms = $this->intel_form_type_form_info();

      $content = '';

      if (!empty($_GET['fid']) && !empty($forms[$_GET['fid']])) {
        $form = $forms[$_GET['fid']];
        $content .= '<br>';
        $content .= '[ninja_form id="' . $form['id'] . '"]';
      }
      elseif (!empty($forms)) {
        $form = array_shift($forms);
        $content .= '<br>';
        $content .= '[ninja_form id="' . $form['id'] . '"]';
      }
      else {
        $content = __('No Ninja forms were found', $this->plugin_un);
      }
      $posts["$id"] = array(
        'ID' => $id,
        'post_type' => 'page',
        'post_title' => __('Demo') . ' ' . $this->plugin_info['extends_plugin_title'],
        'post_content' => $content,
        'intel_demo' => array(
          'url' => 'intelligence/demo/' . $this->plugin_un,
          'overridable' => 0,
        ),
      );

      return $posts;
    }

    /**
     * Implements hook_intel_form_type_info()
     *
     * Registers 'ninjaform' form type with Intelligence
     */
    public function intel_form_type_info($info = array()) {
      $info[$this->form_type_un] = array(
        // A machine name to uniquely identify the form type provided by this plugin.
        'un' => $this->form_type_un,
        // Human readable name of the form type provided by this plugin.
        'title' => __( 'Ninja Form', $this->plugin_info['extends_plugin_text_domain'] ),
        // The plugin unique name for this plugin
        'plugin_un' => $this->plugin_un,
        // form tracking features addon supports
        'supports' => array(
          'track_submission' => 1,
          'track_submission_value' => 1,
          'track_view' => 1,
        ),
        // Callback to get data for form submissions
        'submission_data_callback' => array($this, 'intel_form_type_submission_data'),
      );

      return $info;
    }

    /**
     * Implements hook_intel_form_type_FORM_TYPE_UN_form_info()
     */
    public function intel_form_type_form_info($data = NULL, $options = array()) {
      $data = &Intel_Df::drupal_static( __METHOD__, array());

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
              if ($name == '_0') {
                $name = '0';
              }
              $row['settings']['track_submission'] = $name;
              $row['settings']['track_submission__title'] = !empty($intel_eventgoal_options[$name]) ? $intel_eventgoal_options[$name] : $name;
            }
            if (isset($action_settings['intel_track_submission_value'])) {
              $row['settings']['track_submission_value'] = $action_settings['intel_track_submission_value'];
            }
            if (isset($action_settings['intel_track_view'])) {
              $name = $action_settings['intel_track_view'];
              if ($name == '_0') {
                $name = '0';
              }
              $row['settings']['track_view'] = $name;
            }
          }
        }

        $data[$row['id']] = $row;
      }

      return $data;
    }

    /*
     * Implements hook_intel_intel_form_type_submission_data()
    */
    public function intel_form_type_submission_data($fid, $fsid) {

      $form_info = $this->intel_form_type_form_info();

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
    function intel_url_urn_resolver($vars) {
      $urn_elms = explode(':', $vars['path']);
      if ($urn_elms[0] == 'urn') {
        array_shift($urn_elms);
      }
      if ($urn_elms[0] == '') {
        if ($urn_elms[1] == $this->form_type_un && !empty($urn_elms[2])) {
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
    function intel_test_url_parsing_alter($urls) {
      $urls[] = ":{$this->form_type_un}:1";
      $urls[] = "urn::{$this->form_type_un}:1";
      $urls[] = ":{$this->form_type_un}:1:1";
      $urls[] = "urn::{$this->form_type_un}:1:1";
      return $urls;
    }

    /**
     * Implements hook_form_FORM_ID_alter()
     */
    function intel_form_intel_visitor_delete_confirm_form_alter(&$form, &$form_state) {
      /*
      $form['nf_intel_submission_delete'] = array(
        '#type' => 'checkbox',
        '#title' => __( 'Delete Ninja Forms submissions including this visitors\' email address(es).'),
        '#default_value' => 1,
      );
      $form['#submit'][] = array( $this, 'intel_form_intel_visitor_delete_confirm_form_submit' );
      */
    }

    /**
     * Processes hook_form_FORM_ID_alter() options
     */
    function intel_form_intel_visitor_delete_confirm_form_submit(&$form, &$form_state) {

    }

  }

  /**
   * The main function responsible for returning The Plugin
   * Instance to functions everywhere.
   *
   * Use this function like you would a global variable, except without needing
   * to declare the global.
   *
   * @since 3.0
   * @return NF_Intel
   */
  function NF_Intel() {
    return NF_Intel::instance();
  }

  NF_Intel();
}

/**
 * Implements hook_register_activation_hook()
 */
function nf_intel_activation_hook() {
  if (is_callable('intel_activate_plugin')) {
    intel_activate_plugin('nf_intel');
  }
}
register_activation_hook( __FILE__, 'nf_intel_activation_hook' );


/**
 * Implements hook_register_uninstall_hook()
 */
function nf_intel_uninstall_hook() {
  require_once plugin_dir_path( __FILE__ ) . 'ninja-forms-intel.install.php';
  nf_intel_uninstall();
}
register_uninstall_hook( __FILE__, 'nf_intel_uninstall_hook' );
