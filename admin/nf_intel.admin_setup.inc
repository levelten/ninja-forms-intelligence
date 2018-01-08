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
    '#title' => __('Ninja Forms default goal', 'nf_intel'),
    '#collapsible' => FALSE,
  );

  $desc = '';
  $f['default']['goal_name'] = array(
    '#type' => 'textfield',
    '#title' => Intel_Df::t('Goal name'),
    '#default_value' => __('Form submission', 'nf_intel'),
    '#description' => $desc,
  );

  $f['default']['goal_value'] = array(
    '#type' => 'textfield',
    '#title' => Intel_Df::t('Goal value'),
    '#default_value' => INTEL_GOAL_VALUE_DEFAULT,
    '#size' => 10,
    '#description' => $desc,
  );

  $f['goal_bypass'] = array(
    '#type' => 'checkbox',
    '#title' => __('Do not setup a default goal right now.', 'nf_intel'),
    '#default_value' => '',
  );

  return $f;
}

function nf_intel_admin_setup_default_goal_check($form, &$form_state) {
  $status = array();
  /*
  if (is_callable('intel')) {
    $status['success'] = 1;
  }
  else {
    $status['error_msg'] = Intel_Df::t('Intelligence plugin has not been activated.');
    $status['error_msg'] .= ' ' . Intel_Df::t('Please install and activate before proceeding.');
  }
  */

  return $status;
}

function nf_intel_admin_setup_default_goal_validate($form, &$form_state, $status) {

}

function nf_intel_admin_setup_default_goal_submit($form, &$form_state) {

}

function nf_intel_admin_setup_finish($form, &$form_state) {
  $f = array();

  $items = array();

  $items[] = '<p>';
  $items[] = __('TODO', 'nf_intel');
  $items[] = '</p>';
  $items[] = '<br>';

  $f['instructions'] = array(
    '#type' => 'markup',
    '#markup' => implode(' ', $items),
  );

  // clear nj_setup as active setup wizard
  update_option('intel_setup', array());

  return $f;
}



