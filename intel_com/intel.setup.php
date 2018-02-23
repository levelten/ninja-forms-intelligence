<?php

/*
 * @file
 * Supports plugin setup to install Intelligence plugin.
 *
 * This file should only be included before Intelligence is installed and active.
 *
 * All code in this file should have no dependencies on Intel framework.
 */

if (!is_callable('intel_setup')) {

  class Intel_Setup {
    const VERSION = '1.0.3';

    /**
     * @var Intel_Setup
     * @since 1.0.1
     */
    protected static $instance;

    /**
     * Plugin Unique Name
     *
     * @since 1.0.1
     * @var string $plugin_un
     */
    public $plugin_un = 'intel';

    /**
     * Plugin Directory
     *
     * @since 1.0.1
     * @var string $dir
     */
    public $dir = '';

    /**
     * Plugin URL
     *
     * @since 1.0.1
     * @var string $url
     */
    public $url = '';

    protected $system_info = array();

    protected $plugin_info = array();

    /**
     * Main Plugin Instance
     *
     * Insures that only one instance of a plugin class exists in memory at any one
     * time. Also prevents needing to define globals all over the place.
     *
     * @since 3.0
     * @static
     * @static var array $instance
     * @return Intel_Setup Highlander Instance
     */
    public static function instance($options = array()) {

      if (null === static::$instance) {
        static::$instance = new static($options);
      }

      return static::$instance;
    }

    /**
     * Intel_Setup constructor.
     *
     */
    protected function __construct($options = array()) {
      $this->dir = plugin_dir_path(__FILE__);

      $this->url = plugin_dir_url(__FILE__);

      $this->system_info = apply_filters('intel_system_info', $this->system_info);

      if (!empty($this->plugin_un) && !empty($this->system_info[$this->plugin_un])) {
        $this->plugin_info = $this->system_info[$this->plugin_un];
      }

      if (!is_callable('intel')) {
        // Add pages for plugin setup
        add_action( 'admin_menu', array( $this, 'admin_menu_plugin_setup' ));
      }

      if (!isset($options['plugins_admin_notice']) || !empty($options['plugins_admin_notice'])) {
        global $pagenow;

        if ( 'plugins.php' == $pagenow ) {
          add_action( 'admin_notices', array( $this, 'admin_notices_plugins_page') );
        }
      }
    }

    /**
     * Implements hook_admin_menu() when plugin is not installed.
     */
    public function admin_menu_plugin_setup() {
      global $admin_page_hooks;

      $text_domain = $plugin_un = $this->plugin_un;

      // check if intel is installed, if so exit
      if (is_callable('intel')) {
        return;
      }

      // check to see if admin page has already been added
      if (empty($admin_page_hooks['intel_admin'])) {
        add_menu_page(esc_html__("Intelligence", $text_domain), esc_html__("Intelligence", $text_domain), 'manage_options', 'intel_admin', array($this, 'plugin_setup_page'), 'dashicons-analytics');
        add_submenu_page('intel_admin', esc_html__("Setup", $text_domain), esc_html__("Setup", $text_domain), 'manage_options', 'intel_config', array($this, 'plugin_setup_page'));
      }

      // setup redirect after plugin install back to setup wizard
      add_action('activated_plugin', array( $this, 'activated_plugin' ));
    }

    public function get_plugin_setup_url() {
      return 'admin.php?page=intel_config&plugin=' . $this->plugin_un . '&q=' .'admin/config/intel/settings/setup/' . $this->plugin_un;
    }

    /**
     * Generates admin notices if dependences need to be installed.
     *
     * @param array $options
     * @return string
     */
    public function get_plugin_setup_notice($options = array()) {

      wp_enqueue_style('intel-setup-notice', intel_setup()->url . 'css/intel.setup_notice.css');

      $output = '';
      $items = array();
      $plugin_name = __('Plugin');
      if (!empty($this->plugin_info['plugin_title'])) {
        $plugin_name = $this->plugin_info['plugin_title'];
      }

      $type = !empty($options['type']) ? $options['type'] : 'error';
      $notice_class = "intel-setup notice notice-$type";
      if (!empty($vars['inline'])) {
        $notice_class .= " inline";
      }

      // check dependencies
      if (!function_exists('intel_is_plugin_active')) {
        $output .= '<div class="' . $notice_class . '">';
        $output .= '<p>';
        $output .= '<strong>' . __('Notice:') . '</strong> ';

        $output .= $plugin_name . ' ';
        $output .= __('needs to be setup:', $this->plugin_un);
        $output .= ' ' . sprintf(
            __( ' %sSetup plugin%s', $this->plugin_un ),
            '<a href="' . $this->get_plugin_setup_url() . '" class="button">', '</a>'
          );

        $output .= '</p>';
        $output .= '</div>';
      }
      else {
        if (!empty($this->plugin_info['extends_plugin_un']) && !empty($this->plugin_info['extends_plugin_title'])) {
          if (!intel_is_plugin_active($this->plugin_info['extends_plugin_un'])) {
            $output .= '<div class="' . $notice_class . '">';
            $output .= '<p>';
            $output .= '<strong>' . __('Notice:') . '</strong> ';
            $output .= $plugin_name . ' ';
            $output .= __('requires the', $this->plugin_un) . ' ';
            $output .= $this->plugin_info['extends_plugin_title'] . ' ';
            $output .= __('plugin to be installed and active.', $this->plugin_un);
            $output .= '</p>';
            $output .= '</div>';
          }
        }
      }

      return $output;
    }

    /**
     * Implements hook_admin_notices() when on plugins page.
     * @param array $options
     */
    public function admin_notices_plugins_page($options = array()) {
      $output = $this->get_plugin_setup_notice($options);
      if ($output) {
        print $output;
      }
    }

    /**
     * Callback for admin_menu_plugin_setup page=intel_admin. Prints plugin setup
     * page.
     *
     * Also sets intel admin setup to redirect to this plugin setup wizard.
     */
    public function plugin_setup_page() {
      if (!empty($_GET['plugin']) && $_GET['plugin'] != $this->plugin_un) {
        return;
      }

      // initialize setup state option
      $intel_setup = get_option('intel_setup', array());
      $intel_setup['active_path'] = 'admin/config/intel/settings/setup/' . $this->plugin_un;
      update_option('intel_setup', $intel_setup);

      intel_setup_set_activated_option('intelligence', array('destination' => $intel_setup['active_path']));

      print $this->get_plugin_setup_page();
    }

    /**
     * Returns plugin setup output in wp_screen format.
     *
     * @param array $options
     * @return mixed|string
     */
    public function get_plugin_setup_page($options = array()) {

      $text_domain = $plugin_un = $this->plugin_un;

      $vars = array();
      $vars['title'] = !empty($this->plugin_info['plugin_title']) ? $this->plugin_info['plugin_title'] : __('Plugin');
      $vars['title'] .= ' ' . __('Setup', $text_domain);
      $vars['content'] = $this->get_intel_install_instructions();

      $output = intel_setup_theme('setup_screen', $vars);

      return $output;
    }

    /**
     * Returns plugin setup page instruction content
     * 
     * @return mixed|string
     */
    public function get_intel_install_instructions($vars = array()) {
      $text_domain = $plugin_un = $this->plugin_un;

      $plugin_info = $this->plugin_info;

      wp_enqueue_style('intel-plugin-setup-page', intel_setup()->url . 'css/intel.plugin_setup_page.css');

      if (!isset($vars['title'])) {
        $vars['title'] = __('Welcome to Smarter Google Analytics!', $text_domain);
      }

      if (!isset($vars['panel_header'])) {
        $vars['panel_header'] = '<div class="logo"></div>';
      }

      if (!isset($vars['description'])) {
        $vars['description'] = __('Your moments away from enhanced', $text_domain);
        $vars['description'] .= ' ' . (!empty($plugin_info['extends_plugin_title']) ? $plugin_info['extends_plugin_title'] : __('Google Analytics', $text_domain));
        $vars['description'] .= ' ' . __('tracking', $text_domain);
      }

      if (!isset($vars['body'])) {
        $vars['body'] = '<p>';
        $vars['body'] .= __('First we need to install the Intelligence framework plugin.', $text_domain);
        $vars['body'] .=  '<br>' . __('It\'s what makes the magic.', $text_domain);
        $vars['body'] .= '</p>';

        if (!isset($vars['action_header'])) {
          $vars['body'] .= '<h3>' . __('Get Started', $text_domain) . '</h3>';
        }

        if (empty($vars['install_plugin_card_vars'])) {
          $vars['install_plugin_card_vars'] = array(
            'plugin_slug' => 'intelligence',
            'card_class' => array(
              'action-buttons-only'
            ),
            'install_link_install_class' => array(
              'btn',
              'btn-primary',
              'button-primary',
            ),
            'install_link_activate_class' => array(
              'btn',
              'btn-primary',
              'button-primary',
            ),
            'install_link_active_class' => array(
              'btn',
              'btn-success',
            ),
            'install_link_install_text' => __('Install Intelligence', $text_domain),
            'install_link_activate_text' => __('Activate Intelligence', $text_domain),
            'install_link_update_text' => __('Update Intelligence', $text_domain),
            'install_link_active_text' => '<span class="icon glyphicon glyphicon-check" aria-hidden="true"></span> ' . __('Intelligence is active', $text_domain),
          );
        }

        $vars['body'] .= '<div class="intel-setup">';
        $vars['body'] .= intel_setup_theme('install_plugin_card', $vars['install_plugin_card_vars']);

        $vars['body'] .= '</div>';
      }

      $output = intel_setup_theme('setup_welcome_panel', $vars);

      return $output;
    }

    /**
     * Implements hook_activated_plugin()
     *
     * Used to redirect back to wizard after intel is activated
     *
     * @param $plugin
     */
    public function activated_plugin($plugin) {
      intel_setup_activated_plugin($plugin);
    }
  }

  function intel_setup() {
    return Intel_Setup::instance();
  }

  /**
   * Provides basic theme processes as standin if intel not installed.
   * @param $hook
   * @param array $variables
   * @return mixed|string
   */
  function intel_setup_theme($hook, $variables = array()) {
    if (class_exists('Intel_Df')) {
      return Intel_Df::theme($hook, $variables);
    }

    $output = '';

    $theme_info = array();
    $theme_info = apply_filters('intel_theme_info', $theme_info);
    $theme_info = apply_filters('intel_theme_info_alter', $theme_info);
    if (empty($theme_info[$hook])) {
      return '';
    }
    $info = $theme_info[$hook];

    $func_prefix = !empty($info['function_prefix']) ? $info['function_prefix'] . '_' : '';

    // call preprocess functions
    if (is_callable($func_prefix . 'template_preprocess_' . $hook)) {
      call_user_func_array($func_prefix . 'template_preprocess_' . $hook, array(&$variables));
    }

    // fire hook_intel_preprocess_HOOK()
    // allow plugins to preprocess variables
    $variables = apply_filters('intel_preprocess_' . $hook, $variables);

    // call process functions
    if (is_callable($func_prefix . 'template_process_' . $hook)) {
      call_user_func_array($func_prefix . 'template_process_' . $hook, array(&$variables));
    }

    // fire hook_intel_process_HOOK()
    // allow plugins to preprocess variables
    $variables = apply_filters('intel_process_' . $hook, $variables);

    if (!empty($info['function']) || !empty($info['callback'])) {
      $func = !empty($info['callback']) ? $info['callback'] : $func_prefix . $info['function'];
      if (is_callable($func)) {
        $output = call_user_func_array($func, array(&$variables));
      }
    }

    return $output;
  }

  /**
   * Implements hook_theme().
   */
  function intel_setup_theme_info($themes = array()) {
    $themes['setup_screen'] = array(
      'variables' => array(
        'title' => NULL,
        'content' => NULL,
        'help_tab' => array(),
      ),
      'function' => 'theme_setup_screen',
      'function_prefix' => 'intel_setup',
    );
    $themes['setup_welcome_panel'] = array(
      'variables' => array(
        'title' => NULL,
        'description' => NULL,
        'body' => NULL,
        'panel_header' => NULL,
        'panel_footer' => NULL,
      ),
      'function' => 'theme_setup_welcome_panel',
      'function_prefix' => 'intel_setup',
    );
    $themes['install_plugin_card'] = array(
      'variables' => array(
      ),
      'function' => 'theme_install_plugin_card',
      'function_prefix' => 'intel_setup',
    );
    return $themes;
  }
  // Register hook_theme()
  add_filter('intel_theme_info', 'intel_setup_theme_info');

  function intel_setup_theme_setup_screen(&$vars) {
    global $intel_wp_screen;

    $screen = get_current_screen();

    $intel_wp_screen = (object) array(
      'id' => !empty($vars['screen_id']) ? $vars['screen_id'] : $screen->id,
      'variables' => $vars,
    );

    $output = '';
    $class = 'wrap intel-wp-screen';
    if (!empty($vars['class'])) {
      $class .= ' ' . implode(' ', $vars['class']);
    }

    $output .= '<div class="' . $class . '">';

    if (isset($vars['title'])) {
      $output .= '<h1>' . $vars['title'] . '</h1>';
    }

    if (isset($vars['content'])) {
      $output .= $vars['content'];
    }

    $output .= '</div>';

    return $output;
  }

  function intel_setup_theme_setup_welcome_panel(&$vars) {
    $output = '';
    $class = 'welcome-panel intel-wp-welcome-panel';
    if (!empty($vars['class'])) {
      $class .= ' ' . implode(' ', $vars['class']);
    }
    $output .= '<div id="welcome-panel" class="' . $class . '">';

    if (isset($vars['panel_header'])) {
      $output .= $vars['panel_header'];
    }

    $output .= '<div class="welcome-panel-content">';

    if (isset($vars['title'])) {
      $output .= '<h2 class="title">' . $vars['title'] . '</h2>';
    }

    if (isset($vars['description'])) {
      $output .= '<div class="about-description">' . $vars['description'] . '</div>';
    }

    if (isset($vars['body'])) {
      $output .= '<div class="welcome-panel-column-container">';
      if (is_array($vars['body'])) {
        foreach ($vars['body'] as $cnt => $content) {
          $class = 'welcome-panel-column-container';
          if ($cnt == (count($content) - 1)) {
            $class .= ' welcome-panel-last';
          }
          $output .= '<div class="' . $class . '">';
          $output .= $content;
          $output .= '</div>'; // end div.welcome-panel-column-container
        }
      }
      else {
        $output .= $vars['body'];
      }

      $output .= '</div>'; // end div.welcome-panel-column-container

    }

    $output .= '</div>'; // end div.welcome-panel-content

    if (isset($vars['panel_footer'])) {
      $output .= $vars['panel_footer'];
    }

    $output .= '</div>'; // end div#welcome-panel

    return $output;
  }

  function intel_setup_template_preprocess_install_plugin_card(&$vars = array()) {
    include_once(ABSPATH . 'wp-admin/includes/plugin-install.php'); //for plugins_api..

    $plugin_slug = !empty($vars['plugin_slug']) ? $vars['plugin_slug'] : 'intelligence';
    $args = array(
      'slug' => $plugin_slug,
    );
    $plugin = plugins_api('plugin_information', $args);

    if (is_wp_error($plugin)) {

    }
    else {
      if (!empty($plugin->download_link) && (current_user_can('install_plugins') || current_user_can('update_plugins'))) {
        //wp_enqueue_script( 'plugin-install' );
        //wp_enqueue_script( 'updates' );
        $vars['status'] = $status = install_plugin_install_status($plugin);
        if (is_object($plugin)) {
          $plugin = (array) $plugin;
        }
        $vars['plugin'] = $plugin;
        // code reused from class-wp-plugin-install-list-table.php

        $plugins_allowedtags = intel_setup_get_plugin_allowedtags();
        $vars['title'] = $title = wp_kses($plugin['name'], $plugins_allowedtags);

        // Remove any HTML from the description.
        $vars['description'] = $description = !empty($plugin['short_description']) ? strip_tags($plugin['short_description']) : '';
        $vars['version'] = $version = wp_kses($plugin['version'], $plugins_allowedtags);

        $vars['name'] = $name = strip_tags($title . ' ' . $version);


        $text = array(
          'install_link_install' => __('Install Now'),
          'install_link_update' => __('Update Now'),
          'install_link_activate' => __('Activate'),
          'install_link_active' => _x('Active', 'plugin'),
          'install_link_installed' => _x('Installed', 'plugin'),
          'details_link' => __('More Details'),
        );

        foreach ($text as $k => $v) {
          if (!empty($vars[$k . '_text'])) {
            $text[$k] = ' ' . $vars[$k . '_text'];
          }
        }

        $class_add = array(
          'install_link' => '',
          'install_link_install' => '',
          'install_link_update' => '',
          'install_link_activate' => '',
          'install_link_active' => ' button-disabled',
          'install_link_installed' => ' button-disabled',
          'details_link' => '',
        );

        foreach ($class_add as $k => $v) {
          if (!empty($vars[$k . '_class'])) {
            if (is_array($vars[$k . '_class'])) {
              $class_add[$k] = ' ' . implode(' ', $vars[$k . '_class']);
            }
            else {
              $class_add[$k] = ' ' . $vars[$k . '_class'];
            }
          }
        }

        switch ($status['status']) {
          case 'install':
            if ($status['url']) {
              /* translators: 1: Plugin name and version. */
              // hack to pass activate url to install button
              if (!empty($vars['activate_url'])) {
                $activate_url = $vars['activate_url'];
              }
              else {
                $activate_url = network_admin_url('plugins.php');
              }
              $activate_url = add_query_arg(array(
                //'_wpnonce' => wp_create_nonce('activate-plugin_' . $status['file']),
                'action' => 'activate',
                //'plugin' => $status['file'],
              ), $activate_url);
              $vars['install_link'] = $action_links[] = '<a class="install-now button' . $class_add['install_link'] . $class_add['install_link_install'] . '" data-slug="' . esc_attr($plugin['slug']) . '" href="' . esc_url($status['url']) . '" aria-label="' . esc_attr(sprintf(__('Install %s now'), $name)) . '" data-name="' . esc_attr($name) . '" data-activate-url="' . $activate_url .  '">' . $text['install_link_install'] . '</a>';
            }
            break;

          case 'update_available':
            if ($status['url']) {
              /* translators: 1: Plugin name and version */
              $vars['install_link'] = $action_links[] = '<a class="update-now button aria-button-if-js' . $class_add['install_link'] . $class_add['install_link_update'] . '" data-plugin="' . esc_attr($status['file']) . '" data-slug="' . esc_attr($plugin['slug']) . '" href="' . esc_url($status['url']) . '" aria-label="' . esc_attr(sprintf(__('Update %s now'), $name)) . '" data-name="' . esc_attr($name) . '">' . $text['install_link_update'] . '</a>';
            }
            break;

          case 'latest_installed':
          case 'newer_installed':
            if (is_plugin_active($status['file'])) {
              $vars['install_link'] = $action_links[] = '<button type="button" class="button' . $class_add['install_link'] . $class_add['install_link_active'] . '" disabled="disabled">' . $text['install_link_active'] . '</button>';
            }
            elseif (current_user_can('activate_plugins')) {
              $button_text = $text['install_link_activate'];
              /* translators: %s: Plugin name */
              $button_label = _x('Activate %s', 'plugin');
              if (!empty($vars['activate_url'])) {
                $activate_url = $vars['activate_url'];
              }
              else {
                $activate_url = network_admin_url('plugins.php');
              }
              $vars['activate_url'] = $activate_url = add_query_arg(array(
                '_wpnonce' => wp_create_nonce('activate-plugin_' . $status['file']),
                'action' => 'activate',
                'plugin' => $status['file'],
              ), $activate_url);



              if (is_network_admin()) {
                $button_text = __('Network Activate');
                /* translators: %s: Plugin name */
                $button_label = _x('Network Activate %s', 'plugin');
                $activate_url = add_query_arg(array('networkwide' => 1), $activate_url);
              }

              $vars['install_link'] = $action_links[] = sprintf(
                '<a href="%1$s" class="button activate-now' . $class_add['install_link'] . $class_add['install_link_activate'] . '" aria-label="%2$s">%3$s</a>',
                esc_url($activate_url),
                esc_attr(sprintf($button_label, $plugin['name'])),
                $button_text
              );
            }
            else {
              $vars['install_link'] = $action_links[] = '<button type="button" class="button' . $class_add['install_link'] . $class_add['install_link_installed'] . '" disabled="disabled">' . $text['install_link_installed']. '</button>';
            }
            break;
        }
      }
    }

    $details_link = self_admin_url('plugin-install.php?tab=plugin-information&amp;plugin=' . $plugin['slug'] .
      '&amp;TB_iframe=true&amp;width=600&amp;height=550');

    /* translators: 1: Plugin name and version. */
    $vars['details_link'] = $action_links[] = '<a href="' . esc_url($details_link) . '" class="thickbox open-plugin-details-modal" aria-label="' . esc_attr(sprintf(__('More information about %s'), $name)) . '" data-title="' . esc_attr($name) . '">' . __('More Details') . '</a>';

    $vars['action_links'] = $action_links;

    if (!empty($vars['activated_destination']) && is_callable('Intel_Df::url')) {
      $vars['activated_redirect'] = Intel_Df::url($vars['activated_destination']);
    }
    if (!empty($vars['activated_redirect'])) {
      intel_setup_set_activated_option($plugin_slug, array('redirect' => $vars['activated_redirect']));
    }

    return $vars;
  }



  function intel_setup_theme_install_plugin_card($vars) {
    wp_enqueue_style('intel-install-plugin-card', intel_setup()->url . 'css/intel.install_plugin_card.css');

    wp_enqueue_script('plugin-install');
    wp_enqueue_script('updates');
    wp_enqueue_script('intel-install-plugin-card', intel_setup()->url . 'js/intel.install_plugin_card.js');
    add_thickbox();

    $class_add = '';
    if (!empty($vars['class'])) {
      if (is_array($vars['class'])) {
        $class_add = ' ' . implode(' ', $vars['class']);
      }
      else {
        $class_add = ' ' . $vars['class'];
      }
    }

    $wrapper0 = '<div id="plugin-filter' . $class_add . '">';
    $wrapper1 = '</div>';

    // check if only wrapper is to be returned
    if (!isset($vars['wrapper']) || !empty($vars['wrapper'])) {
      if (!empty($vars['wrapper']) && ($vars['wrapper'] == 'open')) {
        return $wrapper0;
      }
      elseif (!empty($vars['wrapper']) && ($vars['wrapper'] == 'close')) {
        return $wrapper1;
      }
    }

    $card_class_add = '';
    if (!empty($vars['card_class'])) {
      if (is_array($vars['card_class'])) {
        $card_class_add = ' ' . implode(' ', $vars['card_class']);
      }
      else {
        $card_class_add = ' ' . $vars['card_class'];
      }
    }

    $items[] = '<div class="plugin-card plugin-card-' . $vars['plugin_slug'] . $card_class_add . '">';

    $items[] = '<div class="action-links">';
    $items[] = '<ul class="plugin-action-buttons">';
    $items[] = '<li class="install-link">' . $vars['install_link'] . '</li>';
    $items[] = '<li class="details-link">' . $vars['details_link'] . '</li>';
    $items[] = '</ul>';
    $items[] = '</div>';

    $items[] = '</div>';

    $output = implode($items);

    return $wrapper0 . $output . $wrapper1;
  }

  /**
   * deprecated - function renamed to template_preprocess_intel_install_plugin_card
   * @param $vars
   */
  function intel_setup_process_install_plugin_card($vars) {
    return template_preprocess_intel_install_plugin_card($vars);
  }

  function intel_setup_install_intel() {
    include_once(ABSPATH . 'wp-admin/includes/plugin-install.php'); //for plugins_api..

    $plugin = 'intelligence';

    $api = plugins_api('plugin_information', array(
      'slug' => $plugin,
      'fields' => array(
        'short_description' => FALSE,
        'sections' => FALSE,
        'requires' => FALSE,
        'rating' => FALSE,
        'ratings' => FALSE,
        'downloaded' => FALSE,
        'last_updated' => FALSE,
        'added' => FALSE,
        'tags' => FALSE,
        'compatibility' => FALSE,
        'homepage' => FALSE,
        'donate_link' => FALSE,
      ),
    ));

//includes necessary for Plugin_Upgrader and Plugin_Installer_Skin
    include_once(ABSPATH . 'wp-admin/includes/file.php');
    include_once(ABSPATH . 'wp-admin/includes/misc.php');
    include_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');

    $upgrader = new Plugin_Upgrader(new Plugin_Installer_Skin(compact('title', 'url', 'nonce', 'plugin', 'api')));
    $upgrader->install($api->download_link);
  }

  function intel_setup_get_plugin_allowedtags() {
    $plugins_allowedtags = array(
      'a' => array('href' => array(), 'title' => array(), 'target' => array()),
      'abbr' => array('title' => array()),
      'acronym' => array('title' => array()),
      'code' => array(),
      'pre' => array(),
      'em' => array(),
      'strong' => array(),
      'ul' => array(),
      'ol' => array(),
      'li' => array(),
      'p' => array(),
      'br' => array()
    );
    return $plugins_allowedtags;
  }

  function intel_setup_set_activated_option($plugin_slug, $value) {
    set_transient('intel_activated_' . $plugin_slug, $value, 3600);
  }

  function intel_setup_activated_plugin($plugin) {
    $a = explode('/', $plugin);
    $slug = $a[0];
    $info = get_transient('intel_activated_' . $slug, '');

    // if no transient exists, exit
    if (!is_array($info)) {
      return;
    }

    // transient should only be used once
    delete_transient('intel_activated_' . $slug);

    if (!empty($info['destination'])) {
      $info['redirect'] = Intel_Df::url($info['destination']);
    }
    if (!empty($info['redirect'])) {
      // need to init role capabilities before redirect otherwise access will be
      // denied on redirect
      if ($slug == 'intelligence') {
        intel()->setup_role_caps();
      }
      wp_redirect($info['redirect']);
      exit;
    }
  }

  if (!function_exists('intel_d')) {
    function intel_d() {
      // check if user has access to this data
      static $access;
      if (!isset($access)) {
        //$access = Intel_Df::user_access('debug intel');
        $access = 1;
      }
      if (!$access) {
        return;
      }

      static $kint_aliases;
      $_ = func_get_args();

      if (class_exists('Kint')) {
        if (!Kint::enabled()) {
          return '';
        }

        // add to static aliases so the function caller info translates
        if (empty($kint_aliases)) {
          $kint_aliases = Kint::$aliases;
          $kint_aliases['functions'][] = 'intel_d';
          $kint_aliases['functions'][] = 'intel_dd';
          $kint_aliases['functions'][] = 'intel_print_var';
          Kint::$aliases = $kint_aliases;
        }

        ob_start();
        call_user_func_array(array('Kint', 'dump'), $_);
        $output = ob_get_clean();
        if (is_callable('intel') && intel()->is_intel_admin_page()) {
          Intel_Df::drupal_set_message($output);
        }
        else {
          print $output;
        }
      }
      else {
        if (is_callable('intel') && intel()->is_intel_admin_page()) {
          Intel_Df::drupal_set_message(json_encode($_[0]));
        }
        else {
          //print json_encode($_[0]);
          print '<script> console.log(' . json_encode($_[0]) . ');</script>';
        }
      }
    }
  }
}
