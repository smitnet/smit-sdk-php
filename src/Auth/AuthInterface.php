<?php

namespace SMIT\SDK\Auth;

interface AuthInterface
{
    public function login(array $scopes, string $returnTo);

    public function refresh();

    public function scopes();

    public function user();

    public function logout(string $returnTo, bool $federated, array $options);

    public function callback();

    public function isLoggedIn(): bool;
}
