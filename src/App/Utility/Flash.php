<?php

namespace App\Utility;

/**
 * Flash: stocke et récupère des messages flash en session.
 */
class Flash {

    private const KEY_DANGER = 'flash_danger';
    private const KEY_INFO = 'flash_info';
    private const KEY_SUCCESS = 'flash_success';
    private const KEY_WARNING = 'flash_warning';

    /**
     * Sets a session message or returns (and clears) the value of a specific key
     * of the session.
     * @access private
     * @param string $key
     * @param string $value [optional]
     * @return string|null
     */
    private static function session($key, $value = "") {
        if (!empty($value)) {
            $_SESSION[$key] = $value;
            return $value;
        }

        if (isset($_SESSION[$key])) {
            $message = $_SESSION[$key];
            unset($_SESSION[$key]);
            return $message;
        }

        return null;
    }

    /**
     * Danger: Sets a message or returns the value of the danger flash message.
     * @access public
     * @param string $value [optional]
     * @return string|null
     */
    public static function danger($value = "") {
        return self::session(self::KEY_DANGER, $value);
    }

    /**
     * Info: Sets a message or returns the value of the info flash message.
     * @access public
     * @param string $value [optional]
     * @return string|null
     */
    public static function info($value = "") {
        return self::session(self::KEY_INFO, $value);
    }

    /**
     * Success: Sets a message or returns the value of the success flash message.
     * @access public
     * @param string $value [optional]
     * @return string|null
     */
    public static function success($value = "") {
        return self::session(self::KEY_SUCCESS, $value);
    }

    /**
     * Warning: Sets a message or returns the value of the warning flash message.
     * @access public
     * @param string $value [optional]
     * @return string|null
     */
    public static function warning($value = "") {
        return self::session(self::KEY_WARNING, $value);
    }

}
