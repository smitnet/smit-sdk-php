<?php

namespace SMIT\SDK\Auth\Models;

class UserModel
{
    /**
     * @var array
     */
    private $data = [];

    /**
     * UserModel constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getId() : string
    {
        return $this->data['id'] ?? '';
    }

    /**
     * @return string
     */
    public function getEmail() : string
    {
        return $this->data['email'] ?? '';
    }

    /**
     * @return string
     */
    public function getInitials() : string
    {
        return $this->data['initials'] ?? '';
    }

    /**
     * @return string
     */
    public function getFirstName() : string
    {
        return $this->data['first_name'] ?? '';
    }

    /**
     * @param bool $withPrefix
     * @param bool $commaSeparated
     * @return string
     */
    public function getLastName($withPrefix = true, $commaSeparated = false) : string
    {
        $result = [
            $this->data['last_name_prefix'] ?? '',
            $this->data['last_name'] ?? '',
        ];

        if ($withPrefix) {
            return $commaSeparated
                ? trim(implode(', ', array_reverse($result)))
                : trim(implode(' ', $result));
        }

        return $this->data['last_name'] ?? '';
    }

    /**
     * @param bool $useFormalNotation
     * @return string
     */
    public function getName($useFormalNotation = false) : string
    {
        return $useFormalNotation
            ? trim(implode(' ', [$this->getInitials(), $this->getLastName()]))
            : trim(implode(' ', [$this->getFirstName(), $this->getLastName()]));
    }

    /**
     * @return string
     */
    public function getFormalName() : string
    {
        return $this->getName(true);
    }

    /**
     * @return string
     */
    public function getFullName() : string
    {
        return $this->getName();
    }

    /**
     * @todo move entitlement onto API for manageability
     * @todo add default language based on application settings
     *
     * @return string
     */
    public function getTitle() : string
    {
        switch ($this->data['gender'])
        {
            case 'M':
                switch ($this->getLocale()) {
                    case 'nl': return 'heer';
                }

                return 'Mr';
            case 'F':
                switch ($this->getLocale()) {
                    case 'nl': return 'mevrouw';
                }

                return 'Ms';
            case 'O': default:
                switch ($this->getLocale()) {
                    case 'nl': return 'heer/mevrouw';
                }

                return 'Sir or Madam';
        }
    }

    /**
     * @return array|mixed
     */
    public function getUserMetadata()
    {
        if (array_key_exists('user_metadata', $this->data)) {
            return $this->data['user_metadata'] ?? [];
        }

        return [];
    }

    /**
     * @param string $defaultTimezone Europe/Amsterdam
     *
     * @return string
     */
    public function getTimezone($defaultTimezone = 'Europe/Amsterdam') : string
    {
        if (array_key_exists('timezone', $this->getUserMetadata())) {
            return $this->getUserMetadata()['timezone'] ?? $defaultTimezone;
        }

        return $defaultTimezone;
    }

    /**
     * @param string $defaultLocale nl
     *
     * @return string
     */
    public function getLocale($defaultLocale = 'nl') : string
    {
        if (array_key_exists('locale', $this->getUserMetadata())) {
            return $this->getUserMetadata()['locale'] ?? $defaultLocale;
        }

        return $defaultLocale;
    }

    /**
     * @return array|mixed
     */
    public function getAppMetadata()
    {
        if (array_key_exists('app_metadata', $this->data)) {
            return $this->data['app_metadata'] ?? [];
        }

        return [];
    }

    /**
     * @return array
     */
    public function getScopes() : array
    {
        if (array_key_exists('scopes', $this->getAppMetadata())) {
            return $this->getAppMetadata()['scopes'] ?? [];
        }

        return [];
    }

    public function getAttributes() : array
    {
        if (array_key_exists('attributes', $this->getUserMetadata())) {
            return $this->getUserMetadata()['attributes'] ?? [];
        }

        return [];
    }

    public function getProfessions() : array
    {
        if (array_key_exists('professions', $this->getUserMetadata())) {
            return $this->getUserMetadata()['professions'] ?? [];
        }

        return [];
    }

    public function getRoles() : array
    {
        if (array_key_exists('roles', $this->getUserMetadata())) {
            return $this->getUserMetadata()['roles'] ?? [];
        }

        return [];
    }

    public function getAddresses() : array
    {
        if (array_key_exists('addresses', $this->getUserMetadata())) {
            return $this->getUserMetadata()['addresses'] ?? [];
        }

        return [];
    }
}
