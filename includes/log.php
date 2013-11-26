<?php

class WP_Stream_Log {

	/**
	 * Log handler
	 * @var \WP_Stream_Log
	 */
	public static $instance = null;

	/**
	 * Previous Stream record ID, used for chaining same-session records
	 * @var int
	 */
	public $prev_record;

	/**
	 * Load log handler class, filterable by extensions
	 * 
	 * @return void
	 */
	public static function load() {
		$log_handler    = apply_filters( 'wp_stream_log_handler', __CLASS__ );
		self::$instance = new $log_handler;
	}

	/**
	 * Return active instance of this class
	 * @return WP_Stream_Log
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			$class = __CLASS__;
			self::$instance = new $class;
		}
		return self::$instance;
	}

	/**
	 * Log handler
	 * @param  string $message   sprintf-ready error message string
	 * @param  array  $args      sprintf (and extra) arguments to use
	 * @param  int    $object_id Target object id
	 * @param  string $action    Action performed (stream_action)
	 * @param  int    $user_id   User responsible for the action
	 * @param  array  $contexts  Contexts of the action
	 * @return void
	 */
	public function log( $connector, $message, $args, $object_id, $contexts, $user_id = null ) {
		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$recordarr = array(
			'author'    => $user_id,
			'created'   => current_time( 'mysql' ), // TODO: use GMT (get_gmt_from_date)
			'summary'   => vsprintf( $message, $args ),
			'parent'    => self::$instance->prev_record,
			'connector' => $connector,
			'contexts'  => $contexts,
			'meta'      => array_merge(
				$args,
				array(
					'object_id'  => $object_id,
					'ip_address' => filter_input( INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP ),
					)
				),
			);

		$record_id = WP_Stream_DB::get_instance()->insert( $recordarr );
		
		return $record_id;
	}

}