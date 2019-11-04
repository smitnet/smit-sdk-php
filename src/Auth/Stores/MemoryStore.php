<?php

namespace SMIT\SDK\Auth\Stores;

class MemoryStore implements StoreInterface
{
    protected $data = [];

    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function get($key, $default = null)
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }

        return $default;
    }

    public function delete($key)
    {
        unset($this->data[$key]);
    }
}
