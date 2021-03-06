<?php
/**
 * @file
 * Administration of visitors
 * 
 * @author Tom McCracken <tomm@getlevelten.com>
 */


function intel_util() {
  return 'OK';
}

/**
 * Testing function
 */
function intel_util_temp() {

  //get_next_sub_seq( 1 );
  $form = Ninja_Forms()->form( 1 );

  $seq_num = NF_Database_Models_Form::get_next_sub_seq( 1 );

  intel_d($form);

  intel_d($seq_num);

  return '';

  $scorings = intel_get_scorings();
  intel_d($scorings);
  return '';

  include_once INTEL_DIR . 'includes/intel.ga.inc';

  $status = array();

  $ga_dimensions = intel_fetch_ga_custom_dimensions(TRUE);

  return '';

  include_once INTEL_DIR . 'includes/intel.imapi.inc';

  $response = intel_imapi_ga_goal_get();

  if (empty($response['1'])) {
    return 'goal not found';
  }

  $goal = $response['1'];
  $goal['name'] = 'Contact 4';
  $goal['details']['conditions'][0]['expression'] = $goal['name'] . '\+$';
  $response = intel_imapi_ga_goal_save($goal);

  $response = intel_imapi_ga_goal_get();

  return '';

  $ga_goal = array(
    'name'
  );
  $options = array();
  try {
    $response = intel_imapi_ga_goal_save($ga_goal, $options);
  }
  catch (Exception $e) {
    Intel_Df::drupal_set_message($e->getMessage());
  }

  return '';
}

/**
 * Form Test function
 */
function intel_util_temp_form($form, &$form_state) {

  $defaults = get_option('intel_test_form', array());

  $form['fs'] = array(
    '#type' => 'fieldset',
    '#title' => Intel_Df::t('Fieldset'),
    '#description' => Intel_Df::t('Fieldset description'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );

  $form['fs']['textfield'] = array(
    '#type' => 'textfield',
    '#title' => Intel_Df::t('Textfield'),
    '#default_value' => !empty($defaults['test']) ? $defaults['test'] : '',
    '#description' => Intel_Df::t('Textfield description.'),
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => Intel_Df::t('Submit'),
    '#prefix' => '<br><br>',
  );

  return $form;
}

/**
 * Form test validate
 */
function intel_util_temp_form_validate($form, &$form_state) {

}

/**
 * Form test validate
 */
function intel_util_temp_form_submit($form, &$form_state) {
  $values = $form_state['values'];
}

function intel_util_init_options() {
  return 'OK - options initialized';
}

function intel_util_test() {
  return Intel_Df::t('Placeholder page');
}

function intel_util_test_webform_page() {
  include_once ( INTEL_DIR . 'includes/class-intel-form.php' );

  $form = Intel_Form::drupal_get_form('intel_util_test_webform_form');
  return $form;
}

/**
 * Form Test function
 */
function intel_util_test_webform_form($form, &$form_state) {

  $account = wp_get_current_user();

  $form['data.givenName'] = array(
    '#type' => 'textfield',
    '#title' => Intel_Df::t('Given name'),
    '#default_value' => !empty($account->user_firstname) ? $account->user_firstname : Intel_Df::t('Tommy'),
    //'#description' => Intel_Df::t('Input family name.'),
  );

  $form['data.familyName'] = array(
    '#type' => 'textfield',
    '#title' => Intel_Df::t('Family name'),
    '#default_value' => !empty($account->user_lastname) ? $account->user_lastname : Intel_Df::t('Tester'),
    //'#default_value' => !empty($defaults['test']) ? $defaults['test'] : '',
    //'#description' => Intel_Df::t('Input family name.'),
  );

  $form['data.email'] = array(
    '#type' => 'textfield',
    '#title' => Intel_Df::t('Email'),
    '#default_value' => !empty($account->user_email) ? $account->user_email : '',
    //'#default_value' => !empty($defaults['test']) ? $defaults['test'] : '',
    //'#description' => Intel_Df::t('Input family name.'),
  );

  $form['advanced'] = array(
    '#type' => 'fieldset',
    '#title' => Intel_Df::t('Advanced'),
    '#description' => Intel_Df::t('Configure the Google Analytics event/goal to trigger upon submission.'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );

  $submission_goals = intel_get_event_goal_info('submission');

  $options = array();
  $options[''] = esc_html__( '-- None --', 'wpcf7_intel' );
  $options['form_submission-'] = esc_html__( 'Event: Form submission', 'wpcf7_intel' );
  $options['form_submission'] = esc_html__( 'Valued Event: Form submission!', 'wpcf7_intel' );

  foreach ($submission_goals AS $key => $goal) {
    $options[$key] = esc_html__( 'Goal: ', 'intel') . $goal['goal_title'];
  }
  // Set #tree to group all cf7_intel data in POST
  $form['advanced']['tracking_event_name'] = array(
    '#type' => 'select',
    '#title' => Intel_Df::t('Tracking event'),
    '#options' => $options,
    '#default_value' => !empty($settings['tracking_event_name']) ? $settings['tracking_event_name'] : '',
    //'#description' => $desc,
    //'#size' => 32,
  );

  $form['advanced']['tracking_event_value'] = array(
    '#type' => 'textfield',
    '#title' => Intel_Df::t('Tracking value'),
    '#default_value' => !empty($settings['tracking_event_value']) ? $settings['tracking_event_value'] : '',
    //'#description' => $desc,
    '#size' => 8,
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => Intel_Df::t('Submit'),
    '#prefix' => '<br>',
  );

  return $form;
}

function intel_util_test_webform_form_validate($form, &$form_state) {
  $_SESSION['intel_weform_test']['time0'] = microtime (TRUE);
}

function intel_util_test_webform_form_submit($form, &$form_state) {
  $msg = Intel_Df::t('Form submitted');
  $values = $form_state['values'];
  intel_d($values);

  // get initialied var structure
  $vars = intel_form_submission_vars_default();

  // create pointer aliases
  $submission = &$vars['submission'];
  $track = &$vars['track'];

  // set visitor properties from webform values
  $vp_info = intel()->visitor_property_info();
  foreach ($values as $k => $v) {
    if (!empty($vp_info[$k])) {
      $vars['visitor_properties'][$k] = $v;
    }
  }
  //$vars['visitor_properties']

  // set type of submission, e.g. gravityform, cf7, webform
  $submission->type = 'intel_form';
  // if form type allows multiple form, set id of form that was submitted
  $submission->fid = $values['form_id'];
  // if form submision creates a submission record, set it here
  $submission->fsid = 0;
  //$submission->submission_uri = "/wp-admin/admin.php?page=gf_entries&view=entry&id={$submission->fid}&lid={$submission->fsid}";
  // set title of form
  $submission->form_title = !empty($form_state['form_title']) ? $form_state['form_title'] : ucwords(str_replace('_', ' ', $values['form_id']));

  if (!empty($values['tracking_event_name'])) {
    $track['name'] = $values['tracking_event_name'];
    if (substr($track['name'], -1) == '-') {
      $track['name'] = substr($track['name'], 0, -1);
      $track['valued_event'] = 0;
    }
    if (!empty($values['tracking_event_value'])) {
      $track['value'] = $values['tracking_event_value'];
    }
  }

  $time1 = microtime(TRUE);
  $msg .= " (t1d=" .  number_format($time1 - $_SESSION['intel_weform_test']['time0'], 3) . 'sec';

  intel_d($vars);

  // process submission data
  intel_process_form_submission($vars);

  $msg .= ", t2d=" .  number_format(microtime(TRUE) - $time1, 3) . "sec)";

  $msg .= "\n" . intel()->tracker->get_pushes_script();
  intel_d($msg);
  Intel_Df::drupal_set_message($msg, 'status');

  unset($_SESSION['intel_weform_test']);
}

function intel_util_test_url_parsing() {
  $urls = array(
    'http://www.alpha.com',
    '//www.beta.com',
    'http://www.alpha.com/blog/alpha',
    '//www.beta.com/blog/beta',
    'http://www.alpha.com/blog/alpha?id=4',
    '//www.beta.com/blog/beta?id=4&view=full',
    'admin/config/intel/settings',
    'mailto:tom@example.com',
    'tel:+1-214-555-1212',
    'urn::post:1',
    ':post:1',
    //'urn::gravityform:1',
    //':gravityform:1',
    'urn:isbn:0451450523',
    'news:comp.unix',
  );

  $urls = apply_filters('intel_test_url_parsing_alter', $urls);

  $vars = array();
  $vars['header'] = array(
    Intel_Df::t('URI src'),
    Intel_Df::t('URI out'),
    Intel_Df::t('Location'),
    Intel_Df::t('Scheme'),
    Intel_Df::t('Host'),
    Intel_Df::t('Path'),
    Intel_Df::t('Query'),
    Intel_Df::t('Fragment'),
  );
  $vars['rows'] = array();
  foreach ($urls as $url) {
    $parse = intel_parse_url($url);
    $url_out = Intel_Df::url($url);
    $vars['rows'][] = array(
      $url,
      $url_out,
      isset($parse['location']) ? $parse['location'] : '(notset)',
      isset($parse['scheme']) ? $parse['scheme'] : '(notset)',
      isset($parse['host']) ? $parse['host'] : '(notset)',
      isset($parse['path']) ? $parse['path'] : '(notset)',
      isset($parse['query']) ? $parse['query'] : '(notset)',
      isset($parse['fragment']) ? $parse['fragment'] : '(notset)',
    );
  }


  $out = Intel_Df::theme('table', $vars);
  return $out;

}

function intel_util_reset_site() {
  if (empty($_GET['pw']) || $_GET['pw'] != 'please') {
    return __('What is the password?', 'intel');
  }
  if (empty($_GET['flags'])) {
    return __('No flags set', 'intel');
  }
  $flags = $_GET['flags'];
  if ($flags == '_') {
    $flags = 'c,v,f';
  }
  $flags = explode(',', $flags);
  global $wpdb;

  $plugins_enabled = array(
    'gadwp' => is_plugin_active( 'google-analytics-dashboard-for-wp/gadwp.php' ),
    'flamingo' => is_plugin_active( 'flamingo/flamingo.php' )
  );

  if (in_array('test', $flags)) {
    $identifiers = array(
      'email' => 'tomm@getlevelten.com',
    );
    $visitor = intel_visitor_load_by_identifiers($identifiers);
    if (!empty($visitor->vid)) {
      intel_visitor_delete($visitor->vid);
      Intel_Df::drupal_set_message(Intel_Df::t('Tommy Tester deleted.'));
    }
    else {
      Intel_Df::drupal_set_message(Intel_Df::t('Tommy Tester not in database.'));
    }

    // clear visitor sync requests
    update_option('intel_sync_visitor_requests', array());
    update_option('intel_sync_visitor_requests_queue', array());
  }

  // clear comments
  if (in_array('c', $flags)) {
    $delete = $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}comments");
    $delete = $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}commentmeta");
    Intel_Df::drupal_set_message(Intel_Df::t('Comments truncated.'));
  }

  // clear visitor data
  if (in_array('v', $flags)) {
    $delete = $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}intel_visitor");
    $delete = $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}intel_visitor_identifier");
    $delete = $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}intel_submission");

    //clear out cron data
    delete_option('intel_sync_visitor_requests');
    delete_option('intel_sync_visitor_requests_queue');
    Intel_Df::drupal_set_message(Intel_Df::t('Intel visitors truncated.'));
  }

  // clear form data
  if (in_array('f', $flags)) {
    if (is_plugin_active( 'gravityforms/gravityforms.php' )) {
      $delete = $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}rg_incomplete_submissions");
      $delete = $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}rg_lead");
      $delete = $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}rg_lead_detail");
      $delete = $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}rg_lead_detail_long");
      $delete = $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}rg_lead_meta");
      $delete = $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}rg_lead_notes");

      Intel_Df::drupal_set_message(Intel_Df::t('Gravity Forms form entries truncated.'));
    }

    if (!empty($plugins_enabled['flamingo'])) {
      // flamingo data is stored as posts.
      $sql = "
        SELECT ID
        FROM {$wpdb->prefix}posts
        WHERE post_type = %s
          OR post_type = %s
          OR post_type = %s
      ";
      $data = array(
        'flamingo_contact',
        'flamingo_inbound',
        'flamingo_outbound',
      );

      $results = $wpdb->get_results( $wpdb->prepare($sql, $data) );

      foreach ($results as $row) {
        wp_delete_post($row->ID);
        //$wpdb->delete("{$wpdb->prefix}posts", array('ID' => $row->ID), array('%d'));
        //$wpdb->delete("{$wpdb->prefix}postmeta", array('post_id' => $row->ID), array('%d'));
        //$ids[] = $row->ID;
      }

      Intel_Df::drupal_set_message(Intel_Df::t('Flamingo contacts and messages truncated.'));
    }
  }

  $del_options = array(
    'intel_addons_enabled',
    'intel_api_level',
    'intel_apikey',
    'intel_custom_embed_script',
    'intel_debug_ga_debug',
    'intel_debug_mode',
    //'intel_extended_mode',
    'intel_ga_data_source',
    'intel_ga_profile',
    'intel_ga_tid',
    'intel_ga_view',
    'intel_goals',
    'intel_icon_urls',
    //'intel_imapi_url',
    'intel_intel_scripts_enabled',
    'intel_l10iapi_connector',
    'intel_l10ipai_custom_params',
    'intel_l10iapi_js_embed_style',
    'intel_l10iapi_js_ver',
    'intel_l10iapi_url',
    'intel_scorings',
    'intel_sync_visitor_requests',
    'intel_sync_visitordata_fullcontact',
    'intel_system_meta',
    'intel_track_emailclicks',
    'intel_track_phonecalls',
    'intel_track_realtime',
  );

  if (in_array('is', $flags)) {
    if (!empty($plugins_enabled['gadwp'])) {
      // see google-analytics-dashboard-for-wp/install/install.php
      $del_keys = array(
        'ga_dash_tableid_jail' => '',
        'ga_dash_token' => '',
        'ga_dash_profile_list' => array(),
      );
      $gadwp_options = get_option('gadash_options', '{}');
      $gadwp_options = json_decode($gadwp_options, 1);
      foreach ($del_keys as $k => $v) {
        $gadwp_options[$k] = $v;
      }
      update_option('gadash_options', json_encode( $gadwp_options) );

      Intel_Df::drupal_set_message(Intel_Df::t('GADWP options cleared.'));
    }

    foreach ($del_options as $option) {
      $wpdb->delete( $wpdb->prefix . 'options', array( 'option_name' => $option ), array( '%s') );
    }
    Intel_Df::drupal_set_message(Intel_Df::t('Intelligence options cleared.'));
  }


}

function intel_util_clear_test_visitors() {
  $query = db_select('intel_visitor_identifier', 'i')
    ->fields('i')
    ->condition('type', 'email')
    ->condition('value', '%@example.com', 'LIKE');

  $visitors = $query->execute()->fetchAll();
  $vids = array();
  if (is_array($visitors)) {
    foreach ($visitors as $obj) {
      $vids[] = $obj->vid;
      intel_visitor_delete($obj->vid);
    }
  }
  return Intel_Df::t('deleted: ') . implode(', ', $vids);
}

