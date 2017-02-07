<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       getlevelten.com/blog/tom
 * @since      1.0.0
 *
 * @package    Intl
 * @subpackage Intl/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Intl
 * @subpackage Intl/includes
 * @author     Tom McCracken <tomm@getlevelten.com>
 */
class Intel_Submission_Controller extends Intel_Entity_Controller  {

	public function __construct($entityType, $entity_info) {
		parent::__construct($entityType, $entity_info);

		add_filter('intel_sync_submission', 'Intel_Submission_Controller::sync_ga');
	}

	public static function sync_ga($submission) {
		$gadata = self::fetch_ga_meta_data($submission->sid, $submission->type, $submission->fid, $submission->fsid);
		if (!empty($_GET['debug'])) {
			dpm('gadata'); dpm($gadata);//
		}
		if (empty($gadata)) {
			$submission->setSyncProcessStatus('ga', 0);
			return $submission;
		}

		$submission->data['location'] = $gadata['location'];
		$submission->data['environment'] = $gadata['environment'];
		$submission->data['analytics_session'] = $gadata['session'];
		$submission->save();
		//$submission->setVar('data', 'location', $gadata['location']);
		//$submission->setVar('data', 'environment', $gadata['environment']);
		//$submission->setVar('data', 'analytics_visit', $gadata['visit']);
		//$submission->setVar('ext', 'ga', $gadata);
		//$submission->merge();

		$submission->setSyncProcessStatus('ga', 1);

		return $submission;
	}

	public static function fetch_ga_meta_data($sid, $form_type, $fid, $fsid) {
		require_once INTEL_DIR . "includes/intel.ga.inc";
		intel_include_library_file('ga/class.ga_model.php');

		$ret = array(
			'location' => array(),
			'environment' => array(),
			'lasthit' => 0,
		);

		list($start_date, $end_date, $number_of_days) = _intel_get_report_dates("-1 year", "Today");
		$cache = array(
			'refresh' => 1,
		);

		//$segment = 'dynamic::ga:customVarValue5==' . implode(',dynamic::ga:customVarValue5==', $vtkids);
		//$segment = 'users::condition::ga:customVarValue5==' . implode(',ga:customVarValue5==', $vtkids); // users has a limit of 90 days time frame
		//$segment = 'sessions::condition::ga:customVarValue5==' . implode(',ga:customVarValue5==', $vtkids);
		//$segment = 'sessions::condition::ga:dimension8=@&r2k=' . $fsid . '&';
		$segment = 'sessions::condition::ga:dimension13=@&3ri=:intel_submission:' . $sid . '&';

		$request = array(
			'dimensions' => array('ga:browser', 'ga:browserVersion', 'ga:operatingSystem', 'ga:operatingSystemVersion', 'ga:language', 'ga:screenResolution', 'ga:dimension4'),
			'metrics' => array('ga:totalEvents'),
			'sort_metric' => 'ga:dimension4',
			'start_date' => $start_date,
			'end_date' => $end_date,
			'segment' => $segment,
			'max_results' => 1,
		);

		$data = intel_ga_api_data($request, $cache);

		$rows = intel_get_ga_feed_rows($data);
		if (!empty($rows) && is_array($rows)) {
			foreach ($rows AS $row) {
				$ts = (int)$row['dimension4'];
				if ($ts > $ret['lasthit']) {
					$ret['lasthit'] = $ts;
				}
				$ret['environment'] = array();
				$ret['environment']['browser'] = $row['browser'];
				$ret['environment']['browserVersion'] = $row['browserVersion'];
				$ret['environment']['operatingSystem'] = $row['operatingSystem'];
				$ret['environment']['operatingSystemVersion'] = $row['operatingSystemVersion'];
				$ret['environment']['language'] = $row['language'];
				$ret['environment']['screenResolution'] = $row['screenResolution'];
			}
		}

		$request['dimensions'] = array('ga:country', 'ga:region', 'ga:city', 'ga:metro', 'ga:latitude', 'ga:longitude', 'ga:dimension4');
		$request['metrics'] = array('ga:entrances');

		$data = intel_ga_api_data($request, $cache);

		$rows = intel_get_ga_feed_rows($data);
		if (!empty($rows) && is_array($rows)) {
			foreach ($rows AS $row) {
				$ts = (int)$row['dimension4'];
				if ($ts > $ret['lasthit']) {
					$visitor['lasthit'] = $ts;
				}
				$ret['location']['country'] = $row['country'];
				$ret['location']['region'] = $row['region'];
				$ret['location']['city'] = $row['city'];
				$ret['location']['metro'] = $row['metro'];
				$ret['location']['latitude'] = $row['latitude'];
				$ret['location']['longitude'] = $row['longitude'];
			}
		}

		$ret['session'] = array(
			'trafficsource' => array(),
			'steps' => array(),
		);

		$request['filters'] = '';
		$request['dimensions'] = array('ga:medium', 'ga:source', 'ga:referralPath', 'ga:keyword', 'ga:socialNetwork', 'ga:campaign', 'ga:dimension4');
		$request['metrics'] = array('ga:entrances');
		$request['sort_metric'] = '-ga:dimension4';

		$data = intel_ga_api_data($request, $cache);
		$rows = intel_get_ga_feed_rows($data);
		if (!empty($rows) && is_array($rows)) {
			foreach ($rows AS $row) {
				$ret['session']['trafficsource']['medium'] = $row['medium'];
				$ret['session']['trafficsource']['source'] = $row['source'];
				$ret['session']['trafficsource']['referralPath'] = $row['referralPath'];
				$ret['session']['trafficsource']['keyword'] = $row['keyword'];
				$ret['session']['trafficsource']['socialNetwork'] = $row['socialNetwork'];
				$ret['session']['trafficsource']['campaign'] = $row['campaign'];
			}
		}

		$request['dimensions'] = array('ga:hostname', 'ga:pagePath', 'ga:pageTitle', 'ga:dimension4');
		$request['metrics'] = array('ga:pageviews');
		$request['filter'] = array('ga:pageviews>0');  // not sure if this is needed, trying to filter out events
		$request['max_results'] = 100;

		$data = intel_ga_api_data($request, $cache);

		$rows = intel_get_ga_feed_rows($data);
		if (!empty($rows) && is_array($rows)) {
			foreach ($rows AS $row) {
				$ts = (int)$row['dimension4'];
				if ($ts > $ret['lasthit']) {
					$visitor['lasthit'] = $ts;
				}
				$step = array();
				$step['time'] = (int) $ts;
				$step['type'] = 'page';
				$step['hostname'] = $row['hostname'];
				$step['pagePath'] = $row['pagePath'];
				$step['pageTitle'] = $row['pageTitle'];
				$ret['session']['steps'][] = $step;
			}
		}

		$request['dimensions'] = array('ga:eventCategory', 'ga:eventAction', 'ga:hostname', 'ga:pagePath', 'ga:dimension4');
		$request['metrics'] = array('ga:totalEvents', 'ga:uniqueEvents', 'ga:eventValue');
		$request['filter'] = array();
		$data = intel_ga_api_data($request, $cache);

		$rows = intel_get_ga_feed_rows($data);
		if (!empty($rows) && is_array($rows)) {
			foreach ($rows AS $row) {
				$ts = (int)$row['dimension4'];
				if ($ts > $ret['lasthit']) {
					$visitor['lasthit'] = $ts;
				}
				$step = array(
					'time' => (int) $ts,
					'type' => 'event',
					'category' => $row['eventCategory'],
					'action' => $row['eventAction'],
					'value' => (int)$row['eventValue'],
				);
				$ret['session']['steps'][] = $step;
			}
		}

		$goals = array();
		$submission_goals = get_option('intel_submission_goals', intel_get_submission_goals_default());
		$request['dimensions'] = array('ga:dimension4');
		$request['metrics'] = array();
		$goal_names = array();
		$i = 0;
		foreach ($submission_goals AS $key => $goal) {
			$id = $submission_goals[$key]['ga_id'];
			$request['metrics'][] = "ga:goal{$id}Completions";
			$request['metrics'][] = "ga:goal{$id}Value";
			$goal_names[$id] = $submission_goals[$key]['title'];
			$i++;
			// maximum dimensions is 10 or run if no more goals to process
			if (count($request['metrics']) >= 9 || (count($submission_goals) == $i)) {
				$data = intel_ga_api_data($request, $cache);
				d($data);
				$rows = intel_get_ga_feed_rows($data);
				d($rows);
				$goal_id = 0;
				if (!empty($rows) && is_array($rows)) {
					foreach ($rows AS $row) {
						$goal_id = 0;
						$goal_value = 0;
						foreach ($row as $rk => $rv) {
							if ((substr($rk, -11) == 'Completions') && (int)$rv > 0) {
								// extract goalId from key in format goalXXCompletions
								$goal_id = substr($rk, 4); //
								$goal_id = (int)substr($goal_id, 0, -11);
								break;
							}
						}
						$step = array(
							'time' => (int) $row['dimension4'],
							'type' => 'goal',
							'id' => $goal_id,
							'name' => $goal_names[$goal_id],
							'value' => (int) $row["goal{$goal_id}Value"],
						);
						$ret['session']['steps'][] = $step;
					}
				}
				$request['metrics'] = array();
			}
		}

		uasort($ret['session']['steps'], '_intel_sort_session_steps');

		return $ret;
  }
}