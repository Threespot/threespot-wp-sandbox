<?php

if(!defined('ABSPATH')){
    exit;
}

if(!class_exists('acfe_pro_enqueue')):

class acfe_pro_enqueue{
    
    var $storage = array();
    var $styles_default = array();
    var $scripts_default = array();
    
    /**
     * construct
     */
    function __construct(){
        // ...
    }
    
    
    /**
     * capture_enqueue
     *
     * @return void
     */
    function capture_enqueue(){
        
        if(empty($this->storage)){
            
            global $wp_styles, $wp_scripts;
            $this->styles_default = $wp_styles->queue;
            $this->scripts_default = $wp_scripts->queue;
            
        }
        
        $this->storage[] = acf_uniqid();
        
    }
    
    
    /**
     * print_captured_enqueue
     *
     * @param $handles
     *
     * @return void
     */
    function print_captured_enqueue($handles = false){
        
        if(!$handles){
            
            $handles = array(
                'styles' => $this->get_captured_styles_handles(),
                'scripts' => $this->get_captured_scripts_handles(),
            );
            
        }
        
        // create new instance
        // this avoid to mess with $wp_scripts and $wp_styles queue/done
        $acfe_styles = new WP_Styles();
        $acfe_scripts = new WP_Scripts();
        
        // assign registered scripts and styles to new instance
        $acfe_styles->registered = wp_styles()->registered;
        $acfe_scripts->registered = wp_scripts()->registered;
        
        if(!empty($handles['styles'])){
            $acfe_styles->do_items($handles['styles']);
        }
        
        if(!empty($handles['scripts'])){
            $acfe_scripts->do_items($handles['scripts']);
        }
        
        // remove last capture
        array_pop($this->storage);
        
    }
    
    
    /**
     * get_captured_styles_handles
     *
     * @return string[]
     */
    function get_captured_styles_handles(){
        
        global $wp_styles;
        
        // get difference from default state
        $diff = array_diff($wp_styles->queue, $this->styles_default);
        
        foreach($diff as $handle){
            
            // dequeue from wp_styles
            wp_dequeue_style($handle);
            
        }
        
        return $diff;
        
    }
    
    
    /**
     * get_captured_scripts_handles
     *
     * @return string[]
     */
    function get_captured_scripts_handles(){
        
        global $wp_scripts;
        
        // get difference from default state
        $diff = array_diff($wp_scripts->queue, $this->scripts_default);
        
        foreach($diff as $handle){
            
            // dequeue from wp_scripts
            wp_dequeue_script($handle);
            
        }
        
        return $diff;
        
    }
    
}

acf_new_instance('acfe_pro_enqueue');

endif;


/**
 * acfe_capture_enqueue
 *
 * @return mixed
 */
function acfe_capture_enqueue(){
    return acf_get_instance('acfe_pro_enqueue')->capture_enqueue();
}


/**
 * acfe_get_captured_enqueue
 *
 * @param $handles
 *
 * @return false|string
 */
function acfe_get_captured_enqueue($handles = false){
    
    ob_start();
    acf_get_instance('acfe_pro_enqueue')->print_captured_enqueue($handles);
    return ob_get_clean();
    
}


/**
 * acfe_print_captured_enqueue
 *
 * @param $handles
 *
 * @return void
 */
function acfe_print_captured_enqueue($handles = false){
    echo acf_get_instance('acfe_pro_enqueue')->print_captured_enqueue($handles);
}