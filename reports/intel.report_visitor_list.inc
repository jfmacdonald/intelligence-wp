<?php
/**
 * @file
 * Generates visitor reports
 * 
 * @author Tom McCracken <tomm@getlevelten.com>
 */

function intel_visitor_list_report_page($report_params = '-', $report_subtype = '-', $report_subfilter = '-', $entity_type = '-', $entity = '-') {
  require_once INTEL_DIR . "includes/intel.reports.inc";
  require_once INTEL_DIR . "includes/intel.ga.inc";
  $output = '';

  $vars = array();
  if (!empty($entity_type) && $entity_type != '-') {
    $vars['entity_type'] = $entity_type;
    $vars['entity'] = $entity;
  }
  $vars = intel_init_reports_vars('visitor_list', 'visitor', $report_params, $report_subtype, $report_subfilter, $vars);

  $output = intel_build_report($vars);
  return $output;
}

//function intel_visitor_list_report($vars['filters'] = array(), $vars['context'] = 'site', $sub_index = '-') {
function intel_visitor_list_report($vars) {
  intel_include_library_file('ga/class.ga_model.php'); 

  $output = '';

  $vars['indexBy'] = $vars['report_modes'][0];

  $timeops = 'l30d';
  if ($vars['report_modes'][1] == 'recent') {
    $vars['timeops'] = 'l30dfn';
    $vars['cache_options'] = array('refresh' => 1);
    list($vars['start_date'], $vars['end_date'], $vars['number_of_days']) = _intel_get_report_dates_from_ops($vars['timeops'], $vars['cache_options']);
  }

  $ga_data = new LevelTen\Intel\GAModel();
  $ga_data->setContext($vars['context']);
  $ga_data->setContextMode($vars['context_mode']);
  $ga_data->setAttributeInfoAll($vars['attribute_info']);
  $ga_data->buildFilters($vars['filters'], $vars['subsite']);
  $ga_data->setReportModes($vars['report_modes']);
  $ga_data->buildFilters($vars['filters'], $vars['context']);
//$ga_data->setDebug(1);
  $ga_data->setDateRange($vars['start_date'], $vars['end_date']);
  $ga_data->setRequestCallback('intel_ga_feed_request_callback', array('cache_options' => $vars['cache_options']));
  $ga_data->setRequestSetting('indexBy', $vars['indexBy']);
  $ga_data->setRequestSetting('details', 0);

  //$vars['feed_rows'] = 20;
  $ga_data->setRequestDefaultParam('max_results', 1 * $vars['feed_rows']);

  /*
  if ($vars['report_modes'][1] != 'entrance') {
    $ga_data->setRequestDefaultParam('max_results', 2 * $vars['feed_rows']);
    $ga_data->loadFeedData('pageviews');
  }

  $ga_data->setRequestDefaultParam('max_results', 1 * $vars['feed_rows']);
  $ga_data->loadFeedData('entrances');

  $ga_data->loadFeedData('pageviews_events_valued');
  $ga_data->setRequestDefaultParam('max_results', 2 * $vars['feed_rows']);
  */
  $ga_data->loadFeedData('entrances');
  $ga_data->loadFeedData('entrances_events_valued');
  $ga_data->loadFeedData('visit_info');
  
  $d = $ga_data->data;

  $vtkids = array();
  $i = 0;

  foreach ($d[$vars['indexBy']] AS $index => $de) {
    $score_components = array();
    if ($vars['report_modes'][1] == 'recent') {
      $d[$vars['indexBy']][$index]['entrance']['pageviews'] = $d[$vars['indexBy']][$index]['pageview']['pageviews'];
    }

    $d[$vars['indexBy']][$index]['score'] = intel_score_item($de, 1, $score_components, '', 'trafficsource');
    $d[$vars['indexBy']][$index]['score_components'] = $score_components;

    if (!empty($de['i'])) {
      if ($vars['report_modes'][0] == 'visit') {
        $a = explode('-', $de['i']);
        $vtkid = $a[0];
      }
      else {
        $vtkid = $de['i'];
      }
      $vtkids[] = $vtkid;
      // we only need to load enough for the report
      if ($i++ >= $vars['row_count']) {
        //break;
      }
    }
    if (isset($vtkid)) {
      $d[$vars['indexBy']][$index]['vtkid'] = $vtkid;
      $d[$vars['indexBy']][$index]['links'] = array();
      $filter = 'vtk:' . $vtkid;

      $link_keys = array();
      $link_keys[] = array(
        'type' => 'scorecard',
      );
      $link_keys[] = array(
        'type' => 'sources',
      );
      $link_keys[] = array(
        'type' => 'content',
      );
      $d[$vars['indexBy']][$index]['links'] = intel_build_report_links($link_keys, $filter, $vars['report_params']);
      $d[$vars['indexBy']][$index]['links'][] = Intel_Df::l(Intel_Df::t('clickstream'), intel_build_report_path('visitor', array(), 'clickstream', $filter));

/*
      $params = $vars['report_params'];
      $params['_filter_add'] = $filter;
      $d[$vars['indexBy']][$index]['links'][] = l(t('+'), intel_build_report_path('scorecard', $params));

      $params = $vars['report_params'];
      $params['_filter_only'] = $filter;
      $d[$vars['indexBy']][$index]['links'][] = l(t('scorecard'), intel_build_report_path('scorecard', $params));
      $d[$vars['indexBy']][$index]['links'][] = l(t('content'), intel_build_report_path('content', $params));
      $d[$vars['indexBy']][$index]['links'][] = l(t('sources'), intel_build_report_path('trafficsource', $params));
      $d[$vars['indexBy']][$index]['links'][] = l(t('clickstream'), intel_build_report_path('visitor', array(), 'clickstream', $filter));
*/
    }
  }

  //$visitors = intel_visitor_load_multiple($vtkids, array(), FALSE, 'vtkid');
  //dsm($visitors);
  foreach ($d[$vars['indexBy']] AS $index => $de) {
    $visitor = intel_visitor_load($index, TRUE, 'vtkid');
    if (!empty($visitor->name)) {
      $d[$vars['indexBy']][$index]['info'] = array(
        'title' => $visitor->name,
        'uri' => '/visitor/' . $index,
      );
    }
    else {
      $d[$vars['indexBy']][$index]['info'] = array(
        'title' => Intel_Df::t('anon') . ' (' . substr($index, 0, 10) . ')',
        'uri' => '/visitor/' . $index . '/clickstream',
      );
    }
  }

//dsm($d);
  $vars['data'] = $d;
  $output .= theme_intel_report($vars);

  return $output;  
}

/*
function intel_scorecard_apply_filters_to_request($request, $vars['filters']tr, $segmentstr) {
  if ($vars['filters']tr) {
    $request['filters'] .= (($request['filters']) ? ';' : '') . $vars['filters']tr;
  }
  if ($segmentstr) {
    $request['segment'] .= (($request['segment']) ? ';' : '') . $segmentstr;
  }
  return $request;
}
*/

function theme_intel_visitor_list_report($vars) {
  intel_include_library_file("reports/class.visitor_report_view.php");
  
  $output = '';

  $report_view = new LevelTen\Intel\VisitorReportView();
  $report_view->setData($vars['data']);
  $report_view->setTableRowCount($vars['row_count']);
  $report_view->setModes($vars['report_modes']);
  $report_view->setParam('context', $vars['context']);
  $report_view->setParam('context_mode', $vars['context_mode']);
  $report_view->setParam('indexBy', $vars['indexBy']);
  $report_view->setParam('indexByLabel', $vars['indexByLabel']);
  $report_view->setDateRange($vars['start_date'], $vars['end_date']);
  $report_view->setTargets(intel_get_targets());
  \LevelTen\Intel\ReportPageHeader::setAddScriptCallback('intel_report_add_js_callback');
  $output .= $report_view->renderReport();
  
  return $output;
}