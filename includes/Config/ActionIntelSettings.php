<?php if ( ! defined( 'ABSPATH' ) ) exit;


$settings = array();

/*
$submission_goals = intel_get_event_goal_info('submission');

$options = array();
$options[] = array(
  'label' => esc_html__( '-- None --', 'gravityformsintel' ),
  'value' => '',
);
$options[] = array(
  'label' => esc_html__( 'Event: Form submission', 'gravityformsintel' ),
  'value' => 'form_submission-',
);
$options[] = array(
  'label' => esc_html__( 'Valued event: Form submission!', 'gravityformsintel' ),
  'value' => 'form_submission',
);

foreach ($submission_goals AS $key => $goal) {
    $options[] = array(
      'label' => esc_html__( 'Goal: ', 'intel') . $goal['goal_title'],
      'value' => $key,
    );
}

$settings['tracking_event_name'] = array(
  'name' => 'tracking_event_name',
  'type' => 'select',
  'label' => __( 'Tracking event name', 'nf_intel' ),
  'options' => $options,
  'group' => 'tracking',
  'width' => 'full',
);

$settings['tracking_event_value'] = array(
  'name' => 'tracking_event_value',
  'type' => 'textbox',
  'label' => __( 'Tracking event value', 'nf_intel' ),
  'group' => 'tracking',
  'width' => 'full',
);
*/

return apply_filters( 'nf_intel_action_intel_settings', $settings );
