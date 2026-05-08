<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WS_Scheduler_I18n {
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'ws-scheduler', false, dirname( WS_SCHEDULER_PLUGIN_BASENAME ) . '/languages/' );
	}
}
