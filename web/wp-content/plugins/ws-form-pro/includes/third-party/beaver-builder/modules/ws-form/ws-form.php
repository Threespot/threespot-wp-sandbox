<?php

/**
 * This is an example module with only the basic
 * setup necessary to get it working.
 *
 * @class FLWSFormModule
 */
class FLWSFormModule extends FLBuilderModule {

    /** 
     * Constructor function for the module. You must pass the
     * name, description, dir and url in an array to the parent class.
     *
     * @method __construct
     */  
    public function __construct()
    {
        parent::__construct(array(
            'name'          => __('WS Form', 'ws-form'),
            'description'   => __('Add a form.', 'ws-form'),
            'category'		=> __('Basic', 'ws-form'),
            'dir'           => FL_WS_FORM_DIR . 'modules/ws-form/',
            'url'           => FL_WS_FORM_URL . 'modules/ws-form/',
            'editor_export' => true, // Defaults to true and can be omitted.
            'enabled'       => true, // Defaults to true and can be omitted.
            'icon'          => 'icon.svg'
        ));
    }
}

/**
 * Register the module and its form settings.
 */
FLBuilder::register_module('FLWSFormModule', array(
    'general'       => array( // Tab
        'title'         => __('General', 'ws-form'), // Tab title
        'sections'      => array( // Tab Sections
            'general'       => array( // Section
                'title'         => __('Form', 'ws-form'), // Section Title
                'fields'        => array( // Section Fields
                    'form_id'     => array(
                        'type'          => 'select',
                        'label'         => __('Form', 'ws-form'),
                        'default'       => '',
					    'options'       => self::get_forms(),
                        'help'         	=> 'Choose the form you want to show in this module.',
                        'preview'         => array(
							'type' => 'none'
                        )
                    ),
                )
            )
        )
    )
));