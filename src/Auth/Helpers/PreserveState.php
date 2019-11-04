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

    public function getTransferState()
    {
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
        if (array_key_exists('return_to', $data)) {
            $this->preservedState['return_to'] = $data['return_to'];
        }
    }
}
