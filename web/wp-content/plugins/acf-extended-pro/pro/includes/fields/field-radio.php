<?php

if(!defined('ABSPATH')){
    exit;
}

if(!class_exists('acfe_pro_field_radio')):

class acfe_pro_field_radio extends acfe_field_extend{
    
    /**
     * initialize
     */
    function initialize(){
    
        $this->name = 'radio';
        $this->replace = array(
            'render_field'
        );
        
    }
    
    /**
     * render_field
     *
     * @param $field
     */
    function render_field($field){
        
        // vars
        $e  = '';
        $ul = array(
            'class'             => 'acf-radio-list',
            'data-allow_null'   => $field['allow_null'],
            'data-other_choice' => $field['other_choice'],
        );
        
        // append to class
        $ul['class'] .= ' ' . ( $field['layout'] == 'horizontal' ? 'acf-hl' : 'acf-bl' );
        $ul['class'] .= ' ' . $field['class'];
        
        // Determine selected value.
        $value = (string) $field['value'];
        
        // 1. Selected choice.
        if ( isset( $field['choices'][ $value ] ) ) {
            $checked = (string) $value;
            
        // 2. Custom choice.
        } elseif ( $field['other_choice'] && $value !== '' ) {
            $checked = 'other';
            
        // 3. Empty choice.
        } elseif ( $field['allow_null'] ) {
            $checked = '';
            
        // 4. Default to first choice.
        } else {
            $checked = (string) key( $field['choices'] );
        }
        
        // other choice
        $other_input = false;
        if ( $field['other_choice'] ) {
            
            // Define other input attrs.
            $other_input = array(
                'type'     => 'text',
                'name'     => $field['name'],
                'value'    => '',
                'disabled' => 'disabled',
                'class'    => 'acf-disabled',
            );
            
            // Select other choice if value is not a valid choice.
            if ( $checked === 'other' ) {
                unset( $other_input['disabled'] );
                $other_input['value'] = $field['value'];
            }
            
            // Ensure an 'other' choice is defined.
            if ( ! isset( $field['choices']['other'] ) ) {
                $field['choices']['other'] = '';
            }
        }
        
        // Bail early if no choices.
        if ( empty( $field['choices'] ) ) {
            return;
        }
        
        // Hiden input.
        $e .= acf_get_hidden_input( array( 'name' => $field['name'] ) );
        
        // Open <ul>.
        $e .= '<ul ' . acf_esc_attrs( $ul ) . '>';
        
        // Loop through choices.
        foreach ( $field['choices'] as $value => $label ) {
            $is_selected = false;
            
            // Ensure value is a string.
            $value = (string) $value;
            
            // Define input attrs.
            $attrs = array(
                'type'  => 'radio',
                'id'    => sanitize_title( $field['id'] . '-' . $value ),
                'name'  => $field['name'],
                'value' => $value,
            );
            
            // Check if selected.
            if ( esc_attr( $value ) === esc_attr( $checked ) ) {
                $attrs['checked'] = 'checked';
                $is_selected      = true;
            }
            
            // Check if is disabled.
            if ( isset( $field['disabled'] ) && acf_in_array( $value, $field['disabled'] ) ) {
                $attrs['disabled'] = 'disabled';
            }
            
            // vars
            $field_key     = $field['key'];
            $field_type    = $field['type'];
            $field_name    = $field['_name'];
            $input         = '<input ' . acf_esc_atts($attrs) . '/>';
            $label         = acf_esc_html($label);
            $choice_render = $input  . $label;
            
            // Additional HTML (the "Other" input).
            $additional_html = '';
            if($value === 'other' && $other_input){
                $additional_html = ' ' . acf_get_text_input($other_input);
                $label = $additional_html; // assign other input to label for hooks
            }
            
            // buffer
            ob_start();
            
            // actions
            do_action("acfe/render_choice",                     $input, $value, $label, $field);
            do_action("acfe/render_choice/type={$field_type}",  $input, $value, $label, $field);
            do_action("acfe/render_choice/name={$field_name}",  $input, $value, $label, $field);
            do_action("acfe/render_choice/key={$field_key}",    $input, $value, $label, $field);
            
            // retrieve buffer
            $buffer = ob_get_clean();
    
            // append
            if(!empty($buffer)){
                $choice_render = $buffer;
                $additional_html = ''; // reset additional html (passed to label)
            }
            
            // render
            $e .= '<li><label' . ($is_selected ? ' class="selected"' : '') . '>' . $choice_render . '</label>' . $additional_html . '</li>';
            
        }
        
        
        // close
        $e .= '</ul>';
        
        
        // return
        echo $e;
        
    }
    
}

acf_new_instance('acfe_pro_field_radio');

endif;