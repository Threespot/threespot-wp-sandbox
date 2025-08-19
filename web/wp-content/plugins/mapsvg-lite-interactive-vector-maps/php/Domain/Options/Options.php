<?php

namespace MapSVG;


/**
 * Сlass that manages global MapSVG settings in the database
 * @package MapSVG
 */
class Options
{

    static $options;
    static $protected = [];
    static $cache = [];

    private static function setCache($optionsOrKey, $value = null)
    {
        if (!isset(static::$options)) {
            static::$options = [];
        }
        if (is_string($optionsOrKey) && isset($value)) {
            static::$options[$optionsOrKey] = $value;
        } else {
            if (is_array($optionsOrKey)) {
                static::$options = $optionsOrKey;
            }
        }
    }

    private static function getCache($field = null)
    {
        if (isset($field) && isset(static::$options[$field])) {
            return static::$options[$field];
        } elseif (!isset($field) && isset(static::$options)) {
            return static::$options;
        } else {
            return null;
        }
    }

    /**
     * Returns the list of all MapSVG options (key/value pairs).
     * @return array
     */
    public static function getAll()
    {
        $db = Database::get();
        $res = $db->get_results("SELECT * FROM " . $db->mapsvg_prefix . "settings", ARRAY_A);
        $response = array();
        foreach ($res as $field) {
            $response[$field['key']] = static::decodeValue($field['key'], $field['value']);
        }
        static::setCache($response);
        return $response;
    }

    /**
     * Returns an option value by its name
     * @return mixed
     */
    public static function get($field)
    {
        $cache = static::getCache($field);
        if ($cache) {
            return $cache;
        }
        $db = Database::get();
        $value = $db->get_var("SELECT value FROM " . $db->mapsvg_prefix . "settings WHERE `key`='" . esc_sql($field) . "'");
        $value = static::decodeValue($field, $value);
        static::setCache($field, $value);
        return $value;
    }

    /**
     * Sets an option value
     * @return void
     */
    public static function set($field, $value)
    {
        if (static::hasAccessTo($field)) {
            $db = Database::get();
            $value = static::encodeValue($field, $value);
            $db->replace($db->mapsvg_prefix . "settings", ["key" => $field, "value" => $value]);
            static::setCache($field, $value);
        }
    }

    /**
     * Sets an option value
     * @param array $fields
     * @return void
     */
    public static function setAll($fields)
    {
        $db = Database::get();
        foreach ($fields as $key => $value) {
            static::set($key, $value);
        }
    }

    /**
     * Deletes an option from the database
     * @param string $field The name of the option to delete
     * @return bool True if the option was successfully deleted, false otherwise
     */
    public static function delete($field)
    {
        if (static::hasAccessTo($field)) {
            $db = Database::get();
            $result = $db->delete(
                $db->mapsvg_prefix . "settings",
                ['key' => $field]
            );
            if ($result !== false) {
                static::clearCache($field);
                return true;
            }
        }
        return false;
    }

    /**
     * Clears the cache for a specific field or all fields
     * @param string|null $field The field to clear from cache, or null to clear all
     */
    private static function clearCache($field = null)
    {
        if ($field === null) {
            static::$cache = [];
        } else {
            unset(static::$cache[$field]);
        }
    }

    public static function decodeValue($field, $value)
    {
        if (in_array($field, ['seen_whats_new', 'debug', 'chatConsentAccepted'])) {
            $value = (bool)$value;
            return $value;
        } elseif (in_array($field, ['mappable_post_types'])) {
            return json_decode($value ? $value : '', true);
        } else {
            return $value;
        }
    }
    public static function encodeValue($field, $value)
    {
        if ($field === 'mappable_post_types') {
            return wp_json_encode($value, JSON_UNESCAPED_UNICODE);
        } else {
            return $value;
        }
    }

    public static function hasAccessTo($fieldName)
    {
        $isProtected = in_array($fieldName, static::$protected);
        if ($isProtected) {
            return current_user_can("create_users"); // is admin
        } else {
            return true;
        }
    }
}
