<?php

	add_action('plugins_loaded', function() {

		if(
			isset($_GET) && isset($_GET['elementor-preview'])	// phpcs:ignore
		) {

			// Disable debug
			add_filter('wsf_debug_enabled', function($debug_render) { return false; }, 10, 1);

			// Enqueue all WS Form scripts
			add_action('wp_enqueue_scripts', function() { do_action('wsf_enqueue_core'); });
		}
	});

	add_action('elementor/widgets/widgets_registered', function($widgets_manager) {

		// Unregister normal WordPress widget
		$widgets_manager->unregister_widget_type( 'wp-widget-ws_form_widget' );

		// Register Elementor widget
		class Elementor_WS_Form_Widget extends \Elementor\Widget_Base {

			public function __construct($data = [], $args = null) {

				parent::__construct($data, $args);

				if(
					isset($_GET) && isset($_GET['elementor-preview'])	// phpcs:ignore
				) {

					wp_register_script( 'wsf-elementor', WS_FORM_PLUGIN_DIR_URL . 'includes/third-party/elementor/elementor.js', [ 'elementor-frontend' ], WS_FORM_VERSION, true );
				} else {

					if(!is_admin()) {

						wp_register_script( 'wsf-elementor', WS_FORM_PLUGIN_DIR_URL . 'includes/third-party/elementor/elementor-public.js', [ 'elementor-frontend' ], WS_FORM_VERSION, true );
					}
				}
			}

			public function get_script_depends() {

				return [ 'wsf-elementor' ];
			}

			public function get_name() {

				return 'ws-form';
			}

			public function get_title() {

				return WS_FORM_NAME_PRESENTABLE;
			}

			public function get_icon() {

				return 'eicon-form-horizontal';
			}

			public function get_categories() {

				return [ 'basic' ];
			}

			protected function _register_controls() {

				// Build form list
				$ws_form_form = New WS_Form_Form();
				$forms = $ws_form_form->db_read_all('', "NOT (status = 'trash')", 'label ASC', '', '', false);
				$form_id_options = array('0' => __('Select form...', 'ws-form'));

				if($forms) {

					foreach($forms as $form) {

						$form_id_options[$form['id']] = $form['label'] . ' (ID: ' . $form['id'] . ')';
					}
				}

				$this->start_controls_section(

					'form_section',
					[
						'label' => __( 'WS Form', 'ws-form' ),
						'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
					]
				);

				$this->add_control(

					'form_id',
					[
						'label' => __( 'Form', 'ws-form' ),
						'type' => \Elementor\Controls_Manager::SELECT,
						'options' => $form_id_options,
						'label_block' => true
					]
				);

				$this->end_controls_section();
			}

			protected function render() {

				$settings = $this->get_settings_for_display();

				$form_id = isset($settings['form_id']) ? intval($settings['form_id']) : 0;

				if($form_id > 0) {

					echo sprintf('<div style="min-height:42px">%s</div>', do_shortcode(WS_Form_Common::shortcode($form_id)));
				}
			}
		}

		\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new \Elementor_WS_Form_Widget() );
	});