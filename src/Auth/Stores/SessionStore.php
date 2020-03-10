<?php

namespace SMIT\SDK\Auth\Stores;

class SessionStore implements StoreInterface
{
    const BASE_NAME = 'smit_';

    const COOKIE_EXPIRES = 1209600;

    protected $session_base_name = self::BASE_NAME;

    protected $session_cookie_expires;

    public function __construct($base_name = self::BASE_NAME, $cookie_expires = self::COOKIE_EXPIRES)
    {
        $this->session_base_name = (string) $base_name;
        $this->session_cookie_expires = (int) $cookie_expires;
    }

    private function init()
    {
        if (!session_id()) {
            if (!empty($this->session_cookie_expires)) {
                @session_set_cookie_params($this->session_cookie_expires);
            }

            @session_start();
        }
    }

    public function set($key, $value)
    {
        $this->init();

        $_SESSION[$this->getSessionKeyName($key)] = $value;
    }

    public function get($key, $default = null)
    {
        $this->init();

        if (isset($_SESSION[$this->getSessionKeyName($key)])) {
            return $_SESSION[$this->getSessionKeyName($key)];
        }

        return $default;
    }

    public function has($key)
    {
        return !empty($this->get($key));
    }

    public function delete($key)
    {
        $this->init();

        unset($_SESSION[$this->getSessionKeyName($key)]);
    }

    /**
     * @todo remove only our own keys, this removes all sessions
     */
    public function flush()
    {
        $this->init();

        foreach (array_keys($_SESSION) as $key) {
            unset($_SESSION[$key]);
        }
    }

    private function getSessionKeyName($key)
    {
        $name = $key;

        if (!empty($this->session_base_name)) {
            $name = $this->session_base_name . '_' . $name;
        }

        return $name;
    }
}
