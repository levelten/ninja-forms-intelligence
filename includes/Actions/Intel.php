<?php if ( ! defined( 'ABSPATH' ) || ! class_exists( 'NF_Abstracts_Action' )) exit;

/**
 * Class NF_MailChimp_Actions_MailChimp
 */
final class NF_Intel_Actions_Intel extends NF_Abstracts_Action
{
    /**
     * @var string
     */
    protected $_name  = 'intel';

    /**
     * @var array
     */
    protected $_tags = array( 'analytics' );

    //protected $_image = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAr0AAABkCAMAAAC8VHgkAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyRpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMy1jMDExIDY2LjE0NTY2MSwgMjAxMi8wMi8wNi0xNDo1NjoyNyAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENTNiAoTWFjaW50b3NoKSIgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDo0NDJFQjI3RDY4OEMxMUU2OTI1M0Y0QzVBODg4MzE2NCIgeG1wTU06RG9jdW1lbnRJRD0ieG1wLmRpZDo0NDJFQjI3RTY4OEMxMUU2OTI1M0Y0QzVBODg4MzE2NCI+IDx4bXBNTTpEZXJpdmVkRnJvbSBzdFJlZjppbnN0YW5jZUlEPSJ4bXAuaWlkOjQ0MkVCMjdCNjg4QzExRTY5MjUzRjRDNUE4ODgzMTY0IiBzdFJlZjpkb2N1bWVudElEPSJ4bXAuZGlkOjQ0MkVCMjdDNjg4QzExRTY5MjUzRjRDNUE4ODgzMTY0Ii8+IDwvcmRmOkRlc2NyaXB0aW9uPiA8L3JkZjpSREY+IDwveDp4bXBtZXRhPiA8P3hwYWNrZXQgZW5kPSJyIj8+u9p6HgAAAYBQTFRF/eC8/f//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';

    /**
     * @var string
     */
    protected $_timing = 'normal';

    /**
     * @var int
     */
    protected $_priority = '10';

  /**
   * @var array
   */
  protected $_settings = array();

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();

        $this->_nicename = __( 'Intelligence', 'nf_intel' );

        $this->init_settings();

        $settings = NF_Intel()->config( 'ActionIntelSettings' );

        $this->_settings =  array_merge( $this->_settings, $settings );

//Intel_Df::watchdog('NF_Intel_Actions_Intel', 'YEAH!');
    }

    public function init_settings() {
      if (!NF_Intel()->is_intel_installed()) {
        return;
      }
      $prefix = $this->get_name() . '_';


      $eventgoal_options = intel_get_form_submission_eventgoal_options();
      $options = array();
      foreach ($eventgoal_options as $k => $l) {
        $options[] = array(
          'value' => $k,
          'label' => $l,
        );
      }

      $settings = array();

      //$settings[$prefix . 'tracking_event_name'] = array(
      $help = Intel_Df::t('Select a goal or event to trigger in Google Analytics when the form is submitted.');
      //$l_options = Intel_Df::l_options_add_destination();
      //$l_options = Intel_Df::l_options_add_target('admin_intel_goal');
      $form_id = !empty($_GET['form_id']) ? $_GET['form_id'] : '';
      $l_options = Intel_Df::l_options_add_destination('wp-admin/admin.php?page=ninja-forms&form_id=' . $form_id);
      $add_goal = Intel_Df::l( '+' . Intel_Df::t('Add goal'), 'admin/config/intel/settings/goal/add', $l_options);
      $help = ' ' . Intel_Df::t('You can use the goal admin to !add_goal', array(
          '!add_goal' => $add_goal
      ));
      $settings[] = array(
        'name' => $prefix . 'tracking_event_name',
        'type' => 'select',
        'label' => __( 'Tracking event/goal', 'nf_intel' ) . ' ' . $add_goal . '',
        'options' => $options,
        'group' => 'primary',
        'width' => 'full',
        //'help' => $help,
      );

      //$settings[$prefix . 'tracking_event_value'] = array(
      $settings[] = array(
        'name' => $prefix . 'tracking_event_value',
        'type' => 'textbox',
        'label' => __( 'Tracking value', 'nf_intel' ),
        'group' => 'primary',
        'width' => 'full',
      );

      $this->_settings[ $prefix . 'tracking_fields' ] = array(
        'name' => $prefix . 'tracking_fields',
        'label' => __( 'Submission tracking', 'nf_intel' ),
        'type' => 'fieldset',
        'group' => 'primary',
        'settings' => $settings
      );

      $settings = array();

      $prop_info = intel()->visitor_property_info();

      $prop_wf_info = intel()->visitor_property_webform_info();

      $priority = array(
        'data.name' => 1,
        'data.givenName' => 1,
        'data.familyName' => 1,
        'data.email' => 1,
      );

      $fp = array();
      $fa = array();
      //foreach ($prop_info as $k => $v) {
      foreach ($prop_wf_info as $k => $v) {
        $pi = $prop_info[$k];
        if (!empty($priority[$k])) {
          $f = &$fp;
        }
        else {
          $f = &$fa;
        }

        if (array_key_exists('@value', $pi['variables'])) {
          $key = $k;
          $title = !empty($v['title']) ? $v['title'] : $pi['title'];
          $f[] = array(
            'name' => $prefix . 'prop_' . $key,
            'type' => 'textbox',
            'label' => $title,
            'width' => 'full',
            'use_merge_tags' => array(
              'exclude' => array(
                'user', 'post', 'system', 'querystrings'
              )
            )
          );
        }
        if (!empty($v['variables'])) {
          foreach ($v['variables'] as $kk => $vv) {
            if ($pi['variables'][$kk] != '@value') {
              $key2 = $prefix . 'prop_' . $key . "__$kk";
              $f[] = array(
                'name' => $key2,
                'type' => 'textbox',
                'label' => $title  . ': ' . (!empty($vv['title']) ? $vv['title'] : $kk),
                'width' => 'full',
                'use_merge_tags' => array(
                  'exclude' => array(
                    'user', 'post', 'system', 'querystrings'
                  )
                )
              );
            }
          }
        }

      }

      $settings = array_merge($fp, $fa);

      $this->_settings[ $prefix . 'field_map_fields' ] = array(
        'name' => 'field_map_fields',
        'label' => __( 'Field map', 'nf_intel' ),
        'type' => 'fieldset',
        'group' => 'primary',
        'settings' => $settings
      );
    }

    /*
    * PUBLIC METHODS
    */

    public function save( $action_settings ) {

    }

    public function process( $settings, $form_id, $data ) {
      //Intel_Df::watchdog('nf_process settings', json_encode($settings));
      $data = NF_Intel()->add_form_submission_vars($data, $settings);

      return $data;
    }

    protected function is_opt_in( $data )
    {
        $opt_in = TRUE;
        foreach( $data[ 'fields' ]as $field ){

            if( 'mailchimp-optin' != $field[ 'type' ] ) continue;

            if( ! $field[ 'value' ] ) $opt_in = FALSE;
        }
        return $opt_in;
    }

    protected function get_merge_vars( $action_settings, $list_id )
    {
        $merge_vars = array();
        foreach( $action_settings as $key => $value ){

            if( FALSE === strpos( $key, $list_id ) ) continue;

            $field = str_replace( $list_id . '_', '', $key );

            if( FALSE !== strpos( $key, 'group_' ) ){

                $key = str_replace( $list_id . '_group_', '', $key );

                if( $value ) {
                    $group = explode('_', $key);
                    $merge_vars[ 'groupings' ][ $group[ 0 ] ][ 'id' ] = $group[ 0 ];
                    $merge_vars[ 'groupings' ][ $group[ 0 ] ][ 'groups' ][] = $group[ 1 ];
                }
            } else {
                $merge_vars[ $field ] = $value;
            }
        }
        return $merge_vars;
    }

    protected function get_lists()
    {
        return NF_Intel()->get_lists();
    }
}
