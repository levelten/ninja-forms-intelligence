<?php if ( ! defined( 'ABSPATH' ) ) exit;

$info = array();

$connect_desc = __('Not connected.', 'nf_intel');
$connect_desc .= ' ' . sprintf(
  __( ' %sSetup Intelligence%s', 'nf_intel' ),
  '<a href="admin.php?page=intel_config&plugin=nf_intel&q=admin/config/intel/settings/setup/nf_intel" class="button">', '</a>'
);
if(NF_Intel()->is_intel_installed()) {
  $connect_desc = __('Connected');
}

$info['nf_intel_connect'] = array(
  'id'    => 'nf_intel_connect',
  'type'  => 'desc',
  'label' => __( 'Intelligence API', 'nf_intel' ),
  'desc'  => $connect_desc
);

if (NF_Intel()->is_intel_installed()) {
  $eventgoal_options = intel_get_form_submission_eventgoal_options();
  $default_name = get_option('intel_form_track_submission_default', 'form_submission');
  $desc = !empty($eventgoal_options[$default_name]) ? $eventgoal_options[$default_name] : Intel_Df::t('(not set)');
  $l_options = Intel_Df::l_options_add_destination('wp-admin/admin.php?page=nf-settings');
  $l_options['attributes'] = array(
    'class' => array('button'),
  );
  $desc .= ' ' . Intel_Df::l(__('Change', 'nf_intel'), 'admin/config/intel/settings/form/default_tracking', $l_options);
  $info['nf_intel_form_submission_tracking_event_name_default'] = array(
    'id'    => 'nf_intel_form_submission_tracking_event_name_default',
    'type'  => 'desc',
    'label' => __( 'Default submission even/goal', 'nf_intel' ),
    'desc'  => $desc,
  );

  $default_value = get_option('intel_form_track_submission_value_default', '');
  $info['nf_intel_form_submission_tracking_event_value_default'] = array(
    'id'    => 'nf_intel_form_submission_tracking_event_value_default',
    'type'  => 'desc',
    'label' => __( 'Default submission value', 'nf_intel' ),
    'desc'  => !empty($default_value) ? $default_value : Intel_Df::t('(default)'),
  );
}

return apply_filters( 'nf_intel_plugin_settings', $info);

