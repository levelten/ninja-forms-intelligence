<?php
/**
 * @file
 * Admin configuration management
 */

include_once INTEL_DIR . 'includes/intel.wizard.inc';

include_once INTEL_DIR . 'admin/intel.admin_setup.inc';

function nf_intel_admin_setup_wizard_info($items = array()) {
  $sys_meta = get_option('intel_system_meta', array());
  $info = array(
    'title' => __('Ninja Forms Intelligence setup'),
    'un' => 'nf_intel_setup',
    'callback_prefix' => 'nf_intel_admin_setup',
    'steps' => array(),
  );
  $info['steps']['start'] = array(
    'title' => Intel_Df::t('Start'),
    'submit_button_text' => Intel_Df::t('Start setup'),
    'submit_button_pre_text' => Intel_Df::t('when ready, click'),
    'action_img_src' => INTEL_URL . '/images/setup_start_action.png',
  );

  $info['steps']['intel_plugin'] = array(
    'title' => Intel_Df::t('Intelligence plugin'),
    'action_img_src' => INTEL_URL . '/images/setup_base_ga_action.png',
  );

  $info['steps']['intel_profile'] = array(
    'title' => Intel_Df::t('Intelligence connect'),
    'action_img_src' => INTEL_URL . '/images/setup_intel_action.png',
  );

  $info['steps']['default_goal'] = array(
    'title' => __('Default goal', 'nf_intel'),
    'action_img_src' => INTEL_URL . '/images/setup_intel_action.png',
  );


  $info['steps']['finish'] = array(
    'title' => Intel_Df::t('Finish'),
    'submit_button_text' => '',
    'completed' => 1,
  );

  return $info;
}

function nf_intel_admin_setup_page() {
  $wizard_info = nf_intel_admin_setup_wizard_info();
  $form = Intel_Form::drupal_get_form('intel_wizard_form', $wizard_info);
  return Intel_Df::render($form);
}

function nf_intel_admin_setup_start($form, &$form_state) {
  $f = array();

  $markup = '';
  $markup .= '<div class="row">';
  $markup .= '<div class="col-xs-7">';
  $f['markup_0'] = array(
    '#type' => 'markup',
    '#markup' => $markup,
  );

  $items = array();
  $items[] = '<div class="text-center">';
  $items[] = '<h3>' . Intel_Df::t('Results oriented Google Analytics made easy.') . '</h3>';
  $items[] = '<h4 class="lead text-muted">' . Intel_Df::t('measure what matters!') . '</h4>';
  $items[] = '<p>';
  $items[] = Intel_Df::t('The Intelligence setup wizard will walk you through the steps for setting up enhanced Google Analytics.');
  $l_options = array(
    'fragment' => 'setup-wizard',
    'attributes' => array(
      'target' => 'intelligencewp',
    )
  );
  $items[] = Intel_Df::t('For an overview of the process, see the !link.', array(
    '!link' => Intel_Df::l( Intel_Df::t('Installation Guide'), 'https://intelligencewp.com/doc/installation', $l_options)
  ));
  $items[] = '</p>';

  //$items[] = '<p>';
  //$items[] = Intel_Df::t('Intelligence extends standard Google Analytics to enable you to measure what really matters such as conversions and engagement.');
  //$items[] = Intel_Df::t('Our goal is to help you understand the true value (ROI) of your website and its components, empowering more insightful marketing, content, UX, and features.');
  //$items[] = '</p>';
/*
  $items[] = '<p>';
  $items[] = Intel_Df::t('After completing each step, click the <em>Next step</em> button to proceed.', array(), array('html' => 1));
  $items[] = Intel_Df::t('The wizard will save your progress so you can leave and come back at anytime right to where you left off.');
  //$items[] = Intel_Df::t('Click the <em>Next</em> button to begin.');
  $items[] = '</p>';
*/
  $items[] = '</div>';

  $f['instructions'] = array(
    '#type' => 'markup',
    '#markup' => implode(' ', $items),
  );


  $markup = '';
  $markup .= '</div>';
  $markup .= '<div class="col-xs-5">';
  $markup .= '<image src="' . INTEL_URL . '/images/setup_start_right.png" class="img-responsive" >';
  $markup .= '</div>';
  $markup .= '</div>';
  $f['markup_1'] = array(
    '#type' => 'markup',
    '#markup' => $markup,
  );

  return $f;
}

function nf_intel_admin_setup_start_check($form, &$form_state) {
  $status = array();

  $status['success'] = 1;

  return $status;
}

function nf_intel_admin_setup_start_submit($form, &$form_state) {
  $values = $form_state['values'];

  $wizard_state = &$form_state['wizard_state'];
  if (!in_array('start', $wizard_state['successes'])) {
    $wizard_state['successes'][] = 'start';
  }
}

function nf_intel_admin_setup_intel_plugin($form, &$form_state) {
  $f = array();

  //$instructions = nf_intel_admin_setup_intel_plugin_instructions();

  $instructions = NF_Intel()->setup_intel_plugin_instructions();


  $f['instructions'] = array(
    '#type' => 'markup',
    '#markup' => $instructions,
  );

  return $f;
}

function nf_intel_admin_setup_intel_plugin_check($form, &$form_state) {
  include_once INTEL_DIR . 'includes/intel.ga.inc';

  $status = array();

  if (is_callable('intel')) {
    $status['success'] = 1;
  }
  else {
    $status['error_msg'] = Intel_Df::t('Intelligence plugin has not been activated.');
    $status['error_msg'] .= ' ' . Intel_Df::t('Please install and activate before proceeding.');
  }

  return $status;
}

function nf_intel_admin_setup_intel_plugin_validate($form, &$form_state) {
  if (!empty($status['error_msg'])) {
    Intel_Form::form_set_error('none', $status['error_msg']);
  }
}

function nf_intel_admin_setup_intel_profile($form, &$form_state) {
  include_once INTEL_DIR . 'admin/intel.admin_setup.inc';
  $options = array(
    'imapi_property_setup' => array(
      'callback_destination' => Intel_Df::url('admin/config/intel/settings/setup/nf_intel'),
    ),
  );
  return intel_admin_setup_intel_profile($form, $form_state, $options);
}

function nf_intel_admin_setup_intel_profile_check($form, &$form_state) {
  include_once INTEL_DIR . 'admin/intel.admin_setup.inc';
  return intel_admin_setup_intel_profile_check($form, $form_state);
}

function nf_intel_admin_setup_intel_profile_validate($form, &$form_state, $status) {
  include_once INTEL_DIR . 'admin/intel.admin_setup.inc';
  return intel_admin_setup_intel_profile_validate($form, $form_state, $status);
}

function nf_intel_admin_setup_intel_profile_submit($form, &$form_state) {
  include_once INTEL_DIR . 'admin/intel.admin_setup.inc';
  return intel_admin_setup_intel_profile_submit($form, $form_state);
}

function nf_intel_admin_setup_default_goal($form, &$form_state) {
  $f = array();

  $items = array();

  $items[] = '<p>';
  $items[] = __('You can custom configure submission goals per form.', 'nf_intel');
  $items[] = __('It is recommended to setup a default goal to trigger for all forms that don\'t have custom configured triggers.', 'nf_intel');
  $items[] = '</p>';
  $items[] = '<br>';

  $f['instructions'] = array(
    '#type' => 'markup',
    '#markup' => implode(' ', $items),
  );

  $f['default'] = array(
    '#type' => 'fieldset',
    '#title' => __('Default form submission tracking', 'nf_intel'),
    '#collapsible' => FALSE,
  );

  $f['default']['inline_wrapper_1'] = array(
    '#type' => 'markup',
    '#markup' => '<div class="pull-left">',
  );

  // load goals in case ga profile already has goals
  $options = array(
    'index_by' => 'ga_id',
    'refresh' => !empty($_GET['refresh']) && is_numeric($_GET['refresh']) ? intval($_GET['refresh']) : 3600,
  );

  $form_state['intel_goals'] = $goals = intel_goal_load(null, $options);
  $form_state['intel_ga_goals'] = $ga_goals = intel_ga_goal_load();

  $eventgoal_options = intel_get_form_submission_eventgoal_options();
  $l_options = Intel_Df::l_options_add_destination(Intel_Df::current_path());
  $f['default']['intel_tracking_event_name_default'] = array(
    '#type' => 'select',
    '#title' => Intel_Df::t('Tracking event/goal'),
    '#options' => $eventgoal_options,
    '#default_value' => get_option('intel_tracking_event_name_default', 'form_submission'),
    '#description' => __('Select the goal or event you would like to trigger to be tracked in analytics when a form is submitted.', 'nf_intel'),
    '#suffix' => '<div class="add-goal-link text-right" style="margin-top: -12px;">' . Intel_Df::l(Intel_Df::t('Add Goal'), 'admin/config/intel/settings/goal/add', $l_options) . '</div>',
  );

  $f['default']['inline_wrapper_2'] = array(
    '#type' => 'markup',
    '#markup' => '</div><div class="clearfix"></div>',
  );

  $desc = __('Set a value to be passed with the default goal. Leave blank to use the goal default value.', 'nf_intel');
  $f['default']['intel_tracking_event_value'] = array(
    '#type' => 'textfield',
    '#title' => Intel_Df::t('Tracking value'),
    '#default_value' => get_option('intel_tracking_event_value_default', ''),
    '#description' => $desc,
    '#size' => 8,
  );

  return $f;
}

function nf_intel_admin_setup_default_goal_check($form, &$form_state) {
  $status = array();

  $event_name = get_option('intel_tracking_event_name_default', -1);
  if (isset($form_state['values']['intel_tracking_event_name_default'])) {
    $event_name = $form_state['values']['intel_tracking_event_name_default'];
  }

  if ($event_name != -1) {
    $status['success'] = 1;
  }

  return $status;
}

function nf_intel_admin_setup_default_goal_validate($form, &$form_state, $status) {

}

function nf_intel_admin_setup_default_goal_submit($form, &$form_state) {
  update_option('intel_tracking_event_name_default', $form_state['values']['intel_tracking_event_name_default']);
  update_option('intel_tracking_event_value_default', $form_state['values']['intel_tracking_event_value_default']);
}

function nf_intel_admin_setup_finish($form, &$form_state) {
  $f = array();

  $markup = '';
  $markup .= '<div class="row">';
  $markup .= '<div class="col-xs-7">';
  $f['markup_0'] = array(
    '#type' => 'markup',
    '#markup' => $markup,
  );

  $items = array();

  $items[] = '<div class="text-center">';
  $items[] = '<h3>' . Intel_Df::t('Congratulations!') . '</h3>';

  $items[] = '<p>';
  $items[] = __('Your Ninja Forms submission are now being tracked.', 'nf_intel');
  $items[] = '</p>';


  $items[] = '<p>';
  $items[] = Intel_Df::t(__('To customize form tracking settings in !nf_link or view the !intel_link.', 'nf_intel'),
    array(
      '!nf_link' => Intel_Df::l(__('Ninja Forms', 'nf_intel'), 'wp-admin/admin.php?page=ninja-forms'),
      '!intel_link' => Intel_Df::l(__('Intelligence form settings list', 'nf_intel'), 'admin/config/intel/settings/form'),
    ));
  $items[] = '</p>';

  $items[] = '</div>';

  $f['instructions'] = array(
    '#type' => 'markup',
    '#markup' => implode(' ', $items),
  );

  $markup = '';
  $markup .= '</div>';
  $markup .= '<div class="col-xs-5">';
  $markup .= '<image src="' . INTEL_URL . '/images/setup_finish_right.png" class="img-responsive" >';
  $markup .= '</div>';
  $markup .= '</div>';
  $f['markup_1'] = array(
    '#type' => 'markup',
    '#markup' => $markup,
  );

  // clear nf_setup as active setup wizard
  update_option('intel_setup', array());

  return $f;
}



