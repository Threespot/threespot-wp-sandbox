<?php

	/**
	 * Register all actions and filters for the plugin.
	 */

	class WS_Form_Loader {

		/**
		 * The array of actions registered with WordPress.
		 */
		protected $actions;

		/**
		 * The array of filters registered with WordPress.
		 */
		protected $filters;

		/**
		 * The array of shortcodes registered with WordPress.
		 */
		protected $shortcodes;

		/**
		 * Initialize the collections used to maintain the actions and filters.
		 */
		public function __construct() {

			$this->actions = array();
			$this->filters = array();
			$this->shortcodes = array();
		}

		/**
		 * Add a new action to the collection to be registered with WordPress.
		 */
		public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {

			$this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
		}

		/**
		 * Add a new filter to the collection to be registered with WordPress.
		 */
		public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {

			$this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
		}

		/**
		* Add a new shortcode to the collection to be registered with WordPress
		*/
		public function add_shortcode($tag, $component, $callback) {

			$this->shortcodes = $this->add($this->shortcodes, $tag, $component, $callback);
		}

		/**
		 * A utility function that is used to register the actions and hooks into a single
		 * collection.
		 */
		private function add($hooks, $hook, $component, $callback, $priority = 0, $accepted_args = null) {

			$hooks[] = array(
				'hook'          => $hook,
				'component'     => $component,
				'callback'      => $callback,
				'priority'      => $priority,
				'accepted_args' => $accepted_args
			);

			return $hooks;
		}

		/**
		 * Register the filters and actions with WordPress.
		 */
		public function run() {

			foreach($this->filters as $hook) {

				add_filter($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
			}

			foreach($this->actions as $hook) {

				add_action($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
			}

			foreach($this->shortcodes as $hook) {

				add_shortcode($hook['hook'], array($hook['component'], $hook['callback']));
			}
		}
	}
