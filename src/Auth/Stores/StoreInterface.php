<?php

namespace SMIT\SDK\Auth\Stores;

interface StoreInterface
{
    public function set($key, $value);

    public function get($key, $default = null);

    public function has($key);

    public function delete($key);

    public function flush();
}
