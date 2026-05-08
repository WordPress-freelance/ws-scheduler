<?php
/**
 * Register all actions, filters and shortcodes for the plugin.
 *
 * @since      3.0.0
 * @package    WS_Scheduler
 * @subpackage WS_Scheduler/includes
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WS_Scheduler_Loader {

	protected $actions    = array();
	protected $filters    = array();
	protected $shortcodes = array();

	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}

	public function add_shortcode( $tag, $component, $callback ) {
		$this->shortcodes[] = array( 'tag' => $tag, 'component' => $component, 'callback' => $callback );
	}

	private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {
		$hooks[] = compact( 'hook', 'component', 'callback', 'priority', 'accepted_args' );
		return $hooks;
	}

	public function run() {
		foreach ( $this->filters as $hook ) {
			add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}
		foreach ( $this->actions as $hook ) {
			add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}
		foreach ( $this->shortcodes as $sc ) {
			add_shortcode( $sc['tag'], array( $sc['component'], $sc['callback'] ) );
		}
	}
}
