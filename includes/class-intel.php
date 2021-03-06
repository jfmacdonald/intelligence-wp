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
 * @package    Intel
 * @subpackage Intel/includes
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
 * @package    Intel
 * @subpackage Intel/includes
 * @author     Tom McCracken <tomm@getlevelten.com>
 */
class Intel {

	/**
	 * Singleton container
	 *
	 */
	private static $instance;

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Intel_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	public $created;

	public $admin;

	public $tracker;

	public $gapi;

	protected $page_title;

	public $q;

	public $request_time;

	public $time_delta;

	protected $js_inline = array(
		array('name' => 'default')
	);

	protected $js_settings = array();

	// whether or not url schema is https
	public $is_https;

	public $base_url;

	public $base_secure_url;

	public $base_insecure_url;

	public $base_path;

	public $base_root;

	private $session_hash;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->created = microtime(TRUE);

		$this->plugin_name = 'intel';
		$this->version = '1.0.0';
		$this->includedLibraryFiles = array();
		$this->time_delta = get_option('intel_time_delta', 0);
		$this->request_time = $this->time();

		// setup globals
		$this->is_https = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on';

		// Create base URL.
		$http_protocol = $this->is_https ? 'https' : 'http';
		$this->base_root = $http_protocol . '://' . $_SERVER['HTTP_HOST'];

		$this->base_url = $this->base_root;

		// $_SERVER['SCRIPT_NAME'] can, in contrast to $_SERVER['PHP_SELF'], not
		// be modified by a visitor.
		if ($dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '\/')) {
			if ($dir == '/wp-admin') {
				$dir = '';
			}
			$this->base_path = $dir;
			$this->base_url .= $this->base_path;
			$this->base_path .= '/';
		}
		else {
			$this->base_path = '/';
		}
		$this->base_secure_url = str_replace('http://', 'https://', $this->base_url);
		$this->base_insecure_url = str_replace('https://', 'http://', $this->base_url);

		// initialize constants
		self::setup();

		require_once INTEL_DIR . 'includes/class-intel-tracker.php';

		$this->tracker = Intel_Tracker::getInstance();

		$this->load_dependencies();
		$this->set_locale();
		$this->define_global_hooks();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	public static function getInstance() {
		if (empty(self::$instance)) {
			self::$instance = new Intel();
			self::$instance->run();
		}
		return self::$instance;
	}

	public function setup() {
		// Plugin Path
		if ( ! defined( 'INTEL_DIR' ) ) {
			define( 'INTEL_DIR', plugin_dir_path( dirname( __FILE__ ) ) );
		}

		// Plugin URL
		if ( ! defined( 'INTEL_URL' ) ) {
			define( 'INTEL_URL', plugin_dir_url( dirname( __FILE__ ) ) );
		}

		// Plugin main File
		if ( ! defined( 'INTEL_FILE' ) ) {
			define( 'INTEL_FILE', dirname( __FILE__ ) );
		}

		if ( ! defined( 'REQUEST_TIME' ) ) {
			define( 'REQUEST_TIME', time());
		}

		// include core files
		/**
		 * Include core functions
		 */
		//require INTEL_DIR . 'includes/class-intel-df.php';
		require_once INTEL_DIR . 'intel.inc';
		require_once INTEL_DIR . 'intel_df.inc';
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Intel_Loader. Orchestrates the hooks of the plugin.
	 * - Intel_i18n. Defines internationalization functionality.
	 * - Intel_Admin. Defines all hooks for the admin area.
	 * - Intel_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-intel-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-intel-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-intel-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-intel-public.php';

		/**
		 * Crud super class
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-intel-entity-controller.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-intel-entity.php';

		$this->loader = new Intel_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Intel_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Intel_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	private function define_global_hooks() {
		add_filter('intel_theme_info', 'intel_theme');
		add_filter('intel_theme_info', 'intel_df_theme');


	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$this->admin = $plugin_admin = new Intel_Admin( $this->get_plugin_name(), $this->get_version() );



		$this->loader->add_action( 'admin_init', $this, 'setup_role_caps');

		$this->loader->add_action( 'admin_init', $this, 'setup_cron');



		// on intel admin pages, buffer page output and create sessions
		if (self::is_intel_admin_page()) {
			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

			// page buffer management hooks
			$this->loader->add_action( 'admin_init', $plugin_admin, 'ob_start' );
			$this->loader->add_action( 'admin_footer', $plugin_admin, 'ob_end' );

			// session management hooks
			$this->loader->add_action( 'admin_init', $plugin_admin, 'session_start' );
			$this->loader->add_action( 'wp_login', $plugin_admin, 'session_end' );
			$this->loader->add_action( 'wp_logout', $plugin_admin, 'session_end' );
		}

		// site menu
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'site_menu' );

		// column headers
		$this->loader->add_filter('manage_intelligence_page_intel_visitor_columns', $plugin_admin, 'contacts_column_headers');

		// admin notices
  	$this->loader->add_action( 'admin_notices', $plugin_admin, 'admin_setup_notice' );

		// plugin action links
		$this->loader->add_action( 'plugin_action_links_intel/intel.php', $plugin_admin, 'plugin_action_links' );
	}

	public function setup_cron() {
		// setup intel_cron_hook
		$timestamp = wp_next_scheduled('intel_cron_hook');
		if ($timestamp == FALSE) {
			wp_schedule_event(time(), 'intel_cron_interval', 'intel_cron_hook');
		}

		// setup intel_cron_queue_hook
		$timestamp = wp_next_scheduled('intel_cron_queue_hook');
		if ($timestamp == FALSE) {
			wp_schedule_event(time(), 'intel_cron_queue_interval', 'intel_cron_queue_hook');
		}
	}

	public function is_intel_admin_page() {
		$flag = 0;
		if (!empty($_GET['page']) && substr($_GET['page'], 0, 6) == 'intel_') {
			$flag = 1;
		}
		$flag = apply_filters('is_intel_admin_page_alter', $flag);
		return $flag;
	}

	/**
	 * Assigns capacity to roles based on hook_permissions
	 */
	public function setup_role_caps() {
		$roles = array();
		$permissions = intel_permission();

		foreach ($permissions as $perm_name => $info) {
			if (!isset($info['roles'])) {
				$info['roles'] = array();
			}
			if (!in_array('administrator', $info['roles'])) {
				$info['roles'][] = 'administrator';
			}
			foreach ($info['roles'] as $role_name) {
				if (!isset($roles[$role_name])) {
					$roles[$role_name] = get_role($role_name);
				}
				$roles[$role_name]->add_cap($perm_name);
			}
		}
	}



	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Intel_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'init', $this, 'setup_cron');

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		add_action( 'wp_head', array( $this->tracker, 'tracking_code' ), 99 );

		$this->loader->add_action( 'init', $this, 'quick_session_init' );
		$this->loader->add_filter( 'wp_redirect', $this, 'wp_redirect_quick_session_cache', 10, 2 );

		//$this->loader->add_action( 'wp_footer', $this, 'process_js_settings' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Intel_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	public function is_debug() {
		return !empty($_GET['debug']);
	}

	public function quick_session_init($options = array()) {
		include_once INTEL_DIR . 'includes/class-intel-visitor.php';

		$_SESSION['intel'] = !empty($_SESSION['intel']) ? $_SESSION['intel'] : array();

		$this->session_hash = Intel_Visitor::extractVtk();
		if (empty($this->session_hash)) {
			$this->session_hash = Intel_Visitor::extractCid();
		}

		if (!empty($this->session_hash)) {

			$this->session_hash = substr($this->session_hash, 0, 20);

			$cache = get_transient('intel_session_' . $this->session_hash);
			if ($cache !== FALSE) {
				$_SESSION['intel'] = $cache;
				delete_transient('intel_session_' . $this->session_hash);
			}
		}
	}

	protected function quick_session_cache() {
		if (!empty($this->session_hash) && !empty($_SESSION['intel'])) {
			set_transient('intel_session_' . $this->session_hash, $_SESSION['intel'], 1800);
		}
	}

	public function wp_redirect_quick_session_cache($location, $status) {
		$this->quick_session_cache();
		return $location;
	}

	// TODO
	public function time() {
		return time() + $this->time_delta;
	}

	public function request_time() {
		return $this->request_time;
	}

	public function set_page_title($title) {
		$this->page_title = $title;
	}

	public function get_page_title() {
		return $this->page_title;
	}

	public function add_js($data = NULL, $options = array()) {
		$script_count = &Intel_Df::drupal_static(__FUNCTION__, 0);
		if (is_string($options)) {
			$options = array(
				'type' => $options,
			);
		}
		$options += array(
			'type' => 'file',
			'group' => 0,
			'every_page' => FALSE,
			'weight' => 0,
			'requires_jquery' => TRUE,
			'scope' => 'header',
			'cache' => TRUE,
			'defer' => FALSE,
			'preprocess' => TRUE,
			'version' => NULL,
			'data' => $data,
			'name' => 'intel-' . $script_count,
		);
		if ($options['type'] == 'file') {
			wp_enqueue_script($options['name'], $data);
		}
		else if ($options['type'] == 'external') {
			wp_enqueue_script($options['name'], $data);
		}
		else if ($options['type'] == 'inline') {
			$this->js_inline[] = $options;
			//wp_add_inline_script($options['name'], $options['data']);
		}
		else if ($options['type'] == 'setting') {
			$this->js_settings = array_merge_recursive ( $data , $this->js_settings);
			$script_count--;
		}
		$script_count++;
	}

	public function get_js_settings() {
		return $this->js_settings;
	}

	public function process_js_header() {
		print "<!-- intel js inline header start ->\n";
		print_r($this->js_inline);
		foreach ($this->js_inline as $js) {
			print $js['data'];
		}
		print "<!-- intel js inline header end ->\n";
	}

	public function process_js_settings() {
		print "<!-- intel js settings start ->\n";
		$settings = array(
			'intel_settings' => $this->js_settings,
		);
		print json_encode($settings);
		print "<!-- intel js settings end ->\n";
	}
	public function get_js_settings_json() {
		$settings = array(
			'intel_settings' => $this->js_settings,
		);
		return json_encode($settings);
	}

	public function libraries_get_path($name) {
		$name = strtolower($name);
		if ($name == 'levelten') {
			return INTEL_DIR . 'vendor/levelten/';
		}
		else if ($name == 'timeline') {
			return INTEL_DIR . 'vendor/TimelineJS/';
		}
	}

	// alias of get_entity_controller
	public function entity_get_controller($entity_type) {
		return get_entity_controller($entity_type);
	}

	public function get_entity_controller($entity_type) {
		$files = &Intel_Df::drupal_static(__FUNCTION__, array());
		$entity_info = self::entity_info();

		if (empty($entity_info[$entity_type])) {
			return FALSE;
		}
		$info = $entity_info[$entity_type];

		// include required files
		if (!empty($info['file'])) {
			if (is_array($info['file'])) {
				foreach ($info['file'] as $file) {
					if (empty($files[$file])) {
						include_once INTEL_DIR . $file;
						$files[$file] = 1;
					}
				}
			}
		}

		if (!empty($info['controller class']) && class_exists($info['controller class'])) {
			$controller_class = $info['controller class'];
		}
		else {
			$controller_class = 'Intel_Entity_Controller';
		}

		return new $controller_class($entity_type, $info);
	}


	public function build_info($type) {
		static $infos = array();
		if (!isset($infos[$type])) {

			$infos[$type] = array();

			// implement hook_TYPE_info to enable plugins to add info data
			$infos[$type] = apply_filters('intel_' . $type .'_info', $infos[$type]);

			// implement hook_TYPE_info_alter to allow plugins to alter info
			$infos[$type] = apply_filters('intel_' . $type .'_info_alter', $infos[$type]);
		}
		return $infos[$type];
	}

	public function addon_info($name = NULL) {
		$info = self::build_info('addon');
		if (!isset($name)) {
			return $info;
		}
		return !empty($info[$name]) ? $info[$name] : NULL;
	}

	public function intel_script_info($name = NULL) {
		$info = self::build_info('intel_script');
		if (!isset($name)) {
			return $info;
		}
		return !empty($info[$name]) ? $info[$name] : NULL;
	}

	public function cron_queue_info($name = NULL) {
		$info = self::build_info('cron_queue');
		if (!isset($name)) {
			return $info;
		}
		return !empty($info[$name]) ? $info[$name] : NULL;
	}

	/**
	 * Provides visitor_property_info
	 */
	public function element_info($name = NULL) {
		$info = self::build_info('element');
		if (!isset($name)) {
			return $info;
		}
		return !empty($info[$name]) ? $info[$name] : array();
	}

	public function entity_info($name = NULL) {
  	$info = self::build_info('entity');
		if (!isset($name)) {
			return $info;
		}
		return !empty($info[$name]) ? $info[$name] : array();
	}

	/**
	 * Provides menu_info
	 */
	public function menu_info() {
		$menu_info = array();
		$menu_info = apply_filters('intel_menu_info', $menu_info);

		// allow plugins to alter theme_info
		$menu_info = apply_filters('intel_menu_info_alter', $menu_info);

		return $menu_info;
	}

	/**
	 * Provides theme_info
	 */
	public function theme_info() {
		$theme_info = array();
		$theme_info = apply_filters('intel_theme_info', $theme_info);

		// allow plugins to alter theme_info
		$theme_info = apply_filters('intel_theme_info_alter', $theme_info);

		return $theme_info;
	}

	/**
	 * Provides visitor_property_info
	 */
	public function visitor_property_info($name = NULL) {
		static $info;
		if (!isset($info)) {
			$info = self::build_info('visitor_property');
			// set defaults
			foreach ($info as $k => $v) {
				if (!isset($v['variables'])) {
					$info[$k]['variables'] = array(
						'@value' => NULL,
					);
				}
			}
		}

		if (!isset($name)) {
			return $info;
		}
		return !empty($info[$name]) ? $info[$name] : NULL;
	}

	public function visitor_property_webform_info($name = NULL) {
		$info = self::build_info('visitor_property_webform');
		if (!isset($name)) {
			return $info;
		}
		return !empty($info[$name]) ? $info[$name] : array();
	}

	/**
	 * Generates tracking code
	 */
	public function tracking_code() {
		$ga_tid = get_option( 'intel_ga_tid' );
		require_once INTEL_DIR . 'public/partials/intel-tracking-code.php';
	}


}
