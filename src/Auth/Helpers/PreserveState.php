<?php

namespace SMIT\SDK\Auth\Helpers;

trait PreserveState
{
    /**
     * Preserved state parameters.
     *
     * @var array
     */
    private $preservedState = [];

    public function getTransientState()
    {
        if (!count($this->preservedState)) {
            return null;
        }

        return base64_encode(json_encode($this->preservedState));
    }

    public function getState(string $key = null)
    {
        if (isset($_GET['state'])) {
            $state = json_decode(base64_decode($_GET['state']), true);

            if (!is_null($key)) {
                if (array_key_exists($key, $state)) {
                    return $state[$key];
                }

                return false;
            }
        }

        return [];
    }

    /**
     * @param array $data
     */
    public function setState(array $data)
    {
        $state = array_merge($this->preservedState, $data);

        $state = array_filter($state);

        $this->preservedState = $state;

        return $this;
    }
}
