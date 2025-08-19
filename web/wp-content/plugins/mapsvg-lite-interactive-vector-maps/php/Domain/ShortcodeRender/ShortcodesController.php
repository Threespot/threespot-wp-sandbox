<?php

namespace MapSVG;

class ShortcodesController extends Controller {
    public static function get($request){
        $shortcode = stripslashes(urldecode($request['shortcode']) );
        $shortcode = do_shortcode( $shortcode );
        return self::render($shortcode, 200);
    }
}
