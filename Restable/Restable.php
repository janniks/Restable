<?php

/**
 * Restable.php
 *
 * @author  Jannik Siebert <hi@janniks.com>
 * @version 1.0.0
 *
 * The probabaly tiniest PHP extensible micro-framework
 * 
 * Requires .htaccess with mod rewrite rules:
 * 		RewriteEngine On
 * 		RewriteCond %{REQUEST_FILENAME} !-f
 * 		RewriteCond %{REQUEST_FILENAME} !-d
 * 		RewriteRule ^(.*)$ index.php [QSA,L]
 *
 * @todo add blueprints for REST API
 */
class Restable {

	/**
	 * The array holds a set of arrays that hold all variables for the routes
	 * @var array
	 */
	private $actions;

	/**
	 * The callable function is executed when no route is found
	 * @var function
	 */
	private $bad_path;

	// __ Methods ==============================================================

	/**
	 * The constructor initializes an empty $actions array and sets a standard $bad_path function
	 */
	public function __construct() {

		// set empty array for routes
		$this->actions = array();

		// set standard function
		$this->bad_path = function() {
			self::json(array(
				'error' => '404 - not found',
			));
		};
	}

	/**
	 * Overrides setter function for $bad_path property if the function is callable
	 * @param string $property name of the property
	 * @param mixed  $value    callable function to set
	 */
	public function __set($property, $value) {
		if (!property_exists($this, $property)) return;

		// property to set is 'bad_path'
		if ($property === 'bad_path' && is_callable($value)) {
			$this->bad_path = $value;
		}
	}

	// Register Methods ========================================================

	/**
	 * Convenience GET method register
	 * @param string   $resource path of request
	 * @param function $callable function executed on request
	 * @param array    $hooks    array of callable functions 'before' and 'after' entries
	 */
	public function get($resource, $callable, $hooks = null) {
		$this->register('GET', $resource, $callable, $hooks);
	}

	/**
	 * Convenience POST method register
	 * @param string   $resource path of request
	 * @param function $callable function executed on request
	 * @param array    $hooks    array of callable functions 'before' and 'after' entries
	 */
	public function post($resource, $callable, $hooks = null) {
		$this->register('POST', $resource, $callable, $hooks);
	}

	/**
	 * Convenience PUT method register
	 * @param string   $resource path of request
	 * @param function $callable function executed on request
	 * @param array    $hooks    array of callable functions 'before' and 'after' entries
	 */
	public function put($resource, $callable, $hooks = null) {
		$this->register('PUT', $resource, $callable, $hooks);
	}

	/**
	 * Convenience DELETE method register
	 * @param string   $resource path of request
	 * @param function $callable function executed on request
	 * @param array    $hooks    array of callable functions 'before' and 'after' entries
	 */
	public function delete($resource, $callable, $hooks = null) {
		$this->register('DELETE', $resource, $callable, $hooks);
	}

	/**
	 * Register route to action array
	 * @param string   $method   HTTP method
	 * @param string   $resource path of request
	 * @param function $callable function executed on request
	 * @param array    $hooks    array of callable functions 'before' and 'after' entries
	 */
	public function register($method, $resource, $callable, $hooks = null) {
		
		// abort if function not callable
		if (!is_callable($callable))
			self::fatal('Function not callable!');

		// match resource to path
		$matches = array();
		preg_match("!^([^\:]*\/)(\:[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)?$|^([^\:]+)$!", $resource, $matches);

		// abort if path not valid
		if (empty($matches))
			self::fatal('Path expression not valid!');

		// set action parameters
		$this->actions[] = array(
			'method' => $method,
			'path' => (isset($matches[3])) ? $matches[3] : $matches[1] . ':',
			'callable' => $callable,
			'hooks' => $hooks,
		);
	}

	/**
	 * Starts calling requested actions when available
	 */
	public function start() {

		// iterate through possible actions
		foreach ($this->get_callable_method_actions() as $action) {
			self::call_action($action);
			return;
		}

		// no possible action found
		$this->bad_path();
	}

	// Call Methods ============================================================

	/**
	 * Gets all viable actions for request method and path
	 * @return array viable array of action arrays
	 */
	private function get_callable_method_actions() {
		return array_filter($this->actions, function($e) {
			return ($e['method'] === self::request_method() &&
					is_callable($e['callable']) &&
					strpos(self::request_path(), rtrim($e['path'], ':')) === 0 &&
					($e['path'] === self::request_path() || substr($e['path'], strlen($e['path']) - 1) === ':'));
		});
	}

	/**
	 * Return parsed request parameter
	 * @param  string $path instance resource path
	 * @return string       resource parameter
	 */
	public static function parse_parameter($path) {
		return end((explode(substr($path, 0, strlen($path) - 1), self::request_path())));
	}

	/**
	 * Calls before action hooks, callable function, and after action hooks
	 * @param array $action array with action parameter
	 */
	private static function call_action($action) {
		
		// call before hook
		self::call_hooks('before', $action);

		// call function with possible variable
		call_user_func($action['callable'], self::parse_parameter($action['path']));

		// call after hook
		self::call_hooks('after', $action);
	}

	/**
	 * Calls action hooks
	 * @param string $hook   which hooks to execute
	 * @param array  $action array with action parameter
	 */
	private static function call_hooks($hooks, $action) {
		if (empty($action) || empty($action['hooks']) || empty($action['hooks'][$hooks]))
			return;

		// if array of hooks call each one
		if (is_array($action['hooks'][$hooks])) {
			foreach($action['hooks'][$hooks] as $hook) {
				if (is_callable($hook)) {
					call_user_func($hook);
				}
			}
		} else if (is_callable($action['hooks'][$hooks])) {
			call_user_func($action['hooks'][$hooks]);
		}
	}

	// Misc Methods ============================================================

	/**
	 * Return servers request path
	 * @return string instance request path
	 */
	public static function request_path() {
		$path=rtrim($_SERVER['REQUEST_URI'], '/');
	        $path = empty($path) ? '/' :$path;
	
		return  $path;
	}

	/**
	 * Return servers request method
	 * @return string instance request method
	 */
	public static function request_method() {
		return $_SERVER['REQUEST_METHOD'];
	}

	/**
	 * Sets HTTP status code
	 * @param  int    $code  HTTP status code
	 * @return object        this
	 */
	public function status($code) {
		if (is_int($code))
			http_response_code($code);
		return $this;
	}

	/**
	 * Executes the $bad_path function when no routes found
	 */
	private function bad_path() {
		call_user_func($this->status(404)->bad_path);
	}

	/**
	 * Output JSON encoded object
	 * @param mixed $object object to be encoded to JSON
	 */
	public static function json($object) {

		// set necessary headers
		if (!headers_sent()) {
			header('Access-Control-Allow-Origin: *');
			header('Content-Type: application/json');
		}

		// output JSON
		echo json_encode($object);
	}

	// Error Methods ===========================================================

	/**
	 * Prints and logs error message
	 * @param string $message message that will be printed and logged to PHP error log
	 */
	private static function error($message) {
		$error = 'Restable Framework - Error: ' . $message;
		error_log($error);
		echo '<p style="padding:6px;border-radius:3px;font-family:monospace;color:#B02100;border:1px solid #B02100;background:#FBEDEB;">' . $error . '</p>';
	}

	/**
	 * Abort execution and die
	 * @param string $message message that will be sent to error() function
	 */
	private static function fatal($message) {
		self::error($message);
		die('Restable Framework - Fatal Error');
	}
}
