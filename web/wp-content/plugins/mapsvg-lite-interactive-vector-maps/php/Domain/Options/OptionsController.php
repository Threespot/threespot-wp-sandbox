<?php

namespace MapSVG;

/**
 * Options Controller.
 * Contains just one "update" method that updates options in the database.
 * @package MapSVG
 */
class OptionsController extends Controller
{

    public static function update($request)
    {

        $options = json_decode($request['options'], true);
        foreach ($options as $key => $value) {
            if (is_string($value)) {
                $value = trim($value);
            }
            Options::set($key, $value);
        }

        return new \WP_REST_Response(Options::getAll(), 200);
    }
}
