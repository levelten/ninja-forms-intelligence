<?php

if (!is_callable('intel_setup')) {
  class Intel_Setup {
    const VERSION = '1.0.0';

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
    public $dir = '';

    /**
     * Plugin URL
     *
     * @since 3.0
     * @var string $url
     */
    public $url = '';

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
    public static function instance() {

      if (!isset(self::$instance) && !(self::$instance instanceof Intel_Setup)) {
        self::$instance = new Intel_Setup();


        //spl_autoload_register(array(self::$instance, 'autoloader'));
      }

      return self::$instance;
    }

    /**
     * Intel_Setup constructor.
     *
     */
    public function __construct() {
      $this->dir = plugin_dir_path(__FILE__);

      $this->url = plugin_dir_url(__FILE__);

    }
  }

  function intel_setup() {
    return Intel_Setup::instance();
  }

  intel_setup();

  function intel_setup_theme_install_plugin_card($vars) {
    wp_enqueue_style('intel-setup-plugin-card', intel_setup()->url . 'css/intel.setup-plugin-card.css');
    wp_enqueue_script('plugin-install');
    wp_enqueue_script('updates');
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

    $card_class_add = '';
    if (!empty($vars['card_class'])) {
      if (is_array($vars['card_class'])) {
        $card_class_add = ' ' . implode(' ', $vars['card_class']);
      }
      else {
        $card_class_add = ' ' . $vars['card_class'];
      }
    }

    $items[] = '<div id="plugin-filter' . $class_add . '">';
    $items[] = '<div class="plugin-card plugin-card-' . $vars['plugin_slug'] . $card_class_add . '">';

    $items[] = '<div class="action-links">';
    $items[] = '<ul class="plugin-action-buttons">';
    $items[] = '<li class="install-link">' . $vars['install_link'] . '</li>';
    $items[] = '<li class="details-link">' . $vars['details_link'] . '</li>';
    $items[] = '</ul>';
    $items[] = '</div>';

    $items[] = '</div>';
    $items[] = '</div>';

    return implode($items);
  }


  function intel_setup_process_install_plugin_card($vars = array()) {
    include_once(ABSPATH . 'wp-admin/includes/plugin-install.php'); //for plugins_api..

    $args = array(
      'slug' => !empty($vars['plugin_slug']) ? $vars['plugin_slug'] : 'intelligence',
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

        switch ($status['status']) {
          case 'install':
            if ($status['url']) {
              /* translators: 1: Plugin name and version. */
              $vars['install_link'] = $action_links[] = '<a class="install-now button" data-slug="' . esc_attr($plugin['slug']) . '" href="' . esc_url($status['url']) . '" aria-label="' . esc_attr(sprintf(__('Install %s now'), $name)) . '" data-name="' . esc_attr($name) . '">' . __('Install Now') . '</a>';
            }
            break;

          case 'update_available':
            if ($status['url']) {
              /* translators: 1: Plugin name and version */
              $vars['install_link'] = $action_links[] = '<a class="update-now button aria-button-if-js" data-plugin="' . esc_attr($status['file']) . '" data-slug="' . esc_attr($plugin['slug']) . '" href="' . esc_url($status['url']) . '" aria-label="' . esc_attr(sprintf(__('Update %s now'), $name)) . '" data-name="' . esc_attr($name) . '">' . __('Update Now') . '</a>';
            }
            break;

          case 'latest_installed':
          case 'newer_installed':
            if (is_plugin_active($status['file'])) {
              $vars['install_link'] = $action_links[] = '<button type="button" class="button button-disabled" disabled="disabled">' . _x('Active', 'plugin') . '</button>';
            }
            elseif (current_user_can('activate_plugins')) {
              $button_text = __('Activate');
              /* translators: %s: Plugin name */
              $button_label = _x('Activate %s', 'plugin');
              $activate_url = add_query_arg(array(
                '_wpnonce' => wp_create_nonce('activate-plugin_' . $status['file']),
                'action' => 'activate',
                'plugin' => $status['file'],
              ), network_admin_url('plugins.php'));

              if (is_network_admin()) {
                $button_text = __('Network Activate');
                /* translators: %s: Plugin name */
                $button_label = _x('Network Activate %s', 'plugin');
                $activate_url = add_query_arg(array('networkwide' => 1), $activate_url);
              }

              $vars['install_link'] = $action_links[] = sprintf(
                '<a href="%1$s" class="button activate-now" aria-label="%2$s">%3$s</a>',
                esc_url($activate_url),
                esc_attr(sprintf($button_label, $plugin['name'])),
                $button_text
              );
            }
            else {
              $vars['install_link'] = $action_links[] = '<button type="button" class="button button-disabled" disabled="disabled">' . _x('Installed', 'plugin') . '</button>';
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

    return $vars;
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
          print json_encode($_[0]);
        }
      }
    }
  }
}