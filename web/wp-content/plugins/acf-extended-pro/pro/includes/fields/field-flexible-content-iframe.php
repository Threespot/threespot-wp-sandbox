<?php

if(!defined('ABSPATH')){
    exit;
}

if(!class_exists('acfe_pro_field_fc_iframe')):

class acfe_pro_field_fc_iframe{
    
    /**
     * construct
     */
    function __construct(){
        
        add_action('acfe/flexible/render/before_template', array($this, 'before_template'), 0, 3);
        add_action('acfe/flexible/render/after_template',  array($this, 'after_template'), 20, 3);
        add_action('acfe/flexible/render_field_settings',  array($this, 'render_field_settings'), 2);
        add_filter('acfe/flexible/wrapper_attributes',     array($this, 'wrapper_attributes'), 10, 2);
        
        add_action('acfe/flexible/render/before_template', array($this, 'before_template_google_map'), 1, 3);
        add_action('acfe/flexible/render/after_template',  array($this, 'after_template_google_map'), 19, 3);
        
        acfe_replace_action('acf/render_field/type=flexible_content', array('acfe_field_flexible_content_preview', 'render_field'), array($this, 'render_field'), 8);
        
    }
    
    
    /**
     * render_field_settings
     *
     * acfe/flexible/render_field_settings
     *
     * @param $field
     *
     * @return void
     */
    function render_field_settings($field){
        
        // iframe
        acf_render_field_setting($field, array(
            'label'         => __('Dynamic Preview: Iframe', 'acfe'),
            'name'          => 'acfe_flexible_layouts_previews_iframe',
            'key'           => 'acfe_flexible_layouts_previews_iframe',
            'instructions'  => __('Render dynamic previews in isolated iframes', 'acfe') . '. ' . '<a href="https://www.acf-extended.com/features/fields/flexible-content/dynamic-render-iframe" target="_blank">' . __('See documentation', 'acfe') . '</a>',
            'type'              => 'true_false',
            'message'           => '',
            'default_value'     => false,
            'ui'                => true,
            'ui_on_text'        => '',
            'ui_off_text'       => '',
            'conditional_logic' => array(
                array(
                    array(
                        'field'     => 'acfe_flexible_advanced',
                        'operator'  => '==',
                        'value'     => '1',
                    ),
                    array(
                        'field'     => 'acfe_flexible_layouts_templates',
                        'operator'  => '==',
                        'value'     => '1',
                    ),
                    array(
                        'field'     => 'acfe_flexible_layouts_previews',
                        'operator'  => '==',
                        'value'     => '1',
                    ),
                )
            )
        ));
        
        // iframe
        acf_render_field_setting($field, array(
            'label'         => __('Dynamic Preview: Responsive', 'acfe'),
            'name'          => 'acfe_flexible_layouts_previews_responsive',
            'key'           => 'acfe_flexible_layouts_previews_responsive',
            'instructions'  => __('Render responsive icons to switch the flexible content size', 'acfe') . '. ' . '<a href="https://www.acf-extended.com/features/fields/flexible-content/dynamic-render-iframe" target="_blank">' . __('See documentation', 'acfe') . '</a>',
            'type'              => 'true_false',
            'message'           => '',
            'default_value'     => false,
            'ui'                => true,
            'ui_on_text'        => '',
            'ui_off_text'       => '',
            'conditional_logic' => array(
                array(
                    array(
                        'field'     => 'acfe_flexible_advanced',
                        'operator'  => '==',
                        'value'     => '1',
                    ),
                    array(
                        'field'     => 'acfe_flexible_layouts_templates',
                        'operator'  => '==',
                        'value'     => '1',
                    ),
                    array(
                        'field'     => 'acfe_flexible_layouts_previews',
                        'operator'  => '==',
                        'value'     => '1',
                    ),
                    array(
                        'field'     => 'acfe_flexible_layouts_previews_iframe',
                        'operator'  => '==',
                        'value'     => '1',
                    ),
                )
            )
        ));
        
    }
    
    
    /**
     * wrapper_attributes
     *
     * @param $wrapper
     * @param $field
     *
     * @return mixed
     */
    function wrapper_attributes($wrapper, $field){
        
        // responsive
        if(!acf_maybe_get($field, 'acfe_flexible_layouts_previews_responsive')){
            return $wrapper;
        }
        
        // default config
        $responsive = array(
            'fullscreen' => array(
                'label'     => __('Fullscreen', 'acfe'),
                'icon'      => 'dashicons dashicons-fullscreen-alt',
                'icon_off'  => 'dashicons dashicons-fullscreen-exit-alt',
                'order'     => 'before',
                'separator' => true,
            ),
            'sizes' => array(
                array(
                    'label' => __('Desktop', 'acfe'),
                    'icon'  => 'dashicons dashicons-desktop',
                    'width' => '1200px',
                ),
                array(
                    'label' => __('Tablet', 'acfe'),
                    'icon'  => 'dashicons dashicons-tablet',
                    'width' => '768px',
                ),
                array(
                    'label' => __('Mobile', 'acfe'),
                    'icon'  => 'dashicons dashicons-smartphone',
                    'width' => '480px',
                ),
            ),
        );
        
        // filters
                        $responsive = apply_filters("acfe/flexible/responsive",                        $responsive, $field);
        if($responsive){$responsive = apply_filters("acfe/flexible/responsive/name={$field['_name']}", $responsive, $field);}
        if($responsive){$responsive = apply_filters("acfe/flexible/responsive/key={$field['key']}",    $responsive, $field);}
        
        // allow to return false to disable
        if(empty($responsive)){
            return $wrapper;
        }
        
        // wrapper
        $wrapper['data-acfe-flexible-responsive'] = $responsive;
        
        // return
        return $wrapper;
        
    }
    
    
    /**
     * render_field
     *
     * @param $field
     *
     * @return void
     */
    function render_field($field){
        
        // check setting
        if(!acf_maybe_get($field, 'acfe_flexible_layouts_templates') || !acf_maybe_get($field, 'acfe_flexible_layouts_previews') || !acf_maybe_get($field, 'acfe_flexible_layouts_previews_iframe')){
            acf_get_instance('acfe_field_flexible_content_preview')->render_field($field);
        }
        
    }
    
    
    /**
     * before_template
     *
     * @return void
     */
    function before_template($field, $layout, $is_preview){
        
        // validate
        if(!$is_preview || !acf_maybe_get($field, 'acfe_flexible_layouts_previews_iframe')){
            return;
        }
        
        global $acfe_iframe;
        $acfe_iframe = isset($acfe_iframe) ? $acfe_iframe+1 : 0;
        
        // capture enqueue
        acfe_capture_enqueue();
        
        // capture template
        ob_start();
        
    }
    
    
    /**
     * after_template
     *
     * @param $field
     * @param $layout
     * @param $is_preview
     *
     * @return void
     */
    function after_template($field, $layout, $is_preview){
        
        // validate
        if(!$is_preview || !acf_maybe_get($field, 'acfe_flexible_layouts_previews_iframe')){
            return;
        }
        
        // get template
        $template = ob_get_clean();
        
        // global enqueue
        acfe_flexible_render_enqueue($field);
        
        // layout enqueue
        acfe_flexible_render_layout_enqueue($layout, $field);
        
        // get enqueue
        $enqueue = acfe_get_captured_enqueue();
        
        global $acfe_iframe;
        
        // if already in an iframe
        if($acfe_iframe > 0){
            
            // don't generate another iframe
            // just render the template/enqueue
            echo $template.$enqueue;
            $acfe_iframe--;
            
            if($acfe_iframe === 0){
                $acfe_iframe = null; // reset iframe counter
            }
            
        // top-level iframe
        // generate iframe
        }else{
            
            // inline script
            $inline_script = '<script>document.body.style.visibility = "visible";</script>';
            
            // iframe document
            $doc = '<html><head><style>html,body{margin:0;padding:0;}body{visibility:hidden;overflow:hidden;}</style></head><body>'.$template.$enqueue.$inline_script.'</body></html>';
            
            // min height
            $height = absint(acf_maybe_get_POST('iframeHeight', 0));
            $height = empty($height) ? '100%' : $height . 'px';
            
            ?><iframe class="acfe-fc-iframe" onload="(function(f){function g(f){const d=f.contentDocument||f.contentWindow?.document;if(!d){return 0;}return Math.max(d.body.scrollHeight,d.body.offsetHeight,d.body.clientHeight,d.documentElement.scrollHeight,d.documentElement.offsetHeight,d.documentElement.clientHeight);}function h(){f.style.height='0';f.style.height=g(f)+'px';}h();const p=f.parentElement;if(p){let pw=p.offsetWidth;new ResizeObserver(e=>{for(let n of e){const nw=n.contentRect.width;if(nw!==pw){h();pw=nw;}}}).observe(p);}})(this);" srcdoc="<?php echo htmlentities($doc); ?>" style="height:<?php echo $height; ?>;"></iframe><?php
            
            // reset iframe counter
            $acfe_iframe = null;
            
        }
        
    }
    
    
    /**
     * before_template_google_map
     *
     * @return void
     */
    function before_template_google_map($field, $layout, $is_preview){
        
        // validate
        if(!$is_preview || !acf_maybe_get($field, 'acfe_flexible_layouts_previews_iframe')){
            return;
        }
        
        global $acfe_iframe;
        
        // if top level iframe
        if($acfe_iframe === 0){
            acf_get_instance('acfe_pro_google_map')->switch_store(acf_uniqid());
        }
        
    }
    
    
    /**
     * after_template_google_map
     *
     * @param $field
     * @param $layout
     * @param $is_preview
     *
     * @return void
     */
    function after_template_google_map($field, $layout, $is_preview){
        
        // validate
        if(!$is_preview || !acf_maybe_get($field, 'acfe_flexible_layouts_previews_iframe')){
            return;
        }
        
        global $acfe_iframe;
        
        // top-level iframe
        if(!isset($acfe_iframe) || $acfe_iframe === 0){
            acf_get_instance('acfe_pro_google_map')->wp_print_footer_scripts();
            acf_get_instance('acfe_pro_google_map')->reset_store();
        }
        
    }
    
}

// initialize
new acfe_pro_field_fc_iframe();

endif;