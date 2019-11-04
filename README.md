# SMIT PHP SDK

## Auth

### Installation

Add the following repository to your `composer.json` file (without the ellipsis):

```json
"repositories":[
    ...
    {
        "type": "vcs",
        "url": "git@gitlab.com:pietersmitmediabv/packages/smit-sdk-php.git"
    }
    ...
]
```

Add the following to your `composer.json`'s `require` block (without the ellipsis):

```json
"require": {
  ...
  "smit/smit-sdk-php": "~1.0"
  ...
}
```

### Features

- [x] Authorize using authorization code grant
- [x] Ability to fetch access token
- [x] Ability to refresh access tokens up to 30 days
- [x] Ability to specify specific authorization scopes for more permissions
- [x] Fetch current user credentials with Authorization token
- [x] Logout current user with Authorization token
- [x] Ability to set a specific store resolver (default: PHP sessions)
- [x] Ability to add your own store resolver
- [ ] Register new user using SDK
- [ ] Request password reset link using SDK
- [ ] Ability to fetch user profile addresses using SDK
- [ ] Fetch events created by Auth module for current user

### Functions

#### Login

```php
$client->login()
```

If you'd wish to login with specific scopes:

```php
$client->login('email', 'profile', 'big_number', '...', '...')
```

#### Logout

```php
$client->logout()
```

#### User information

```php
$client->user()
```

#### Check if the user is logged in

```php
$client->isLoggedIn()
```

### Flows

#### Authorization code grant flow (default)

1. Configure the SDk with required credentials:
    - Domain (provided by a SMIT employee)
    - Client ID (provided by a SMIT employee)
    - Client Secret  (provided by a SMIT employee)
    - Redirect URI defined by developer for handling callback (for you to provide to a SMIT employee)
    
    ```php
    $client = new \SMIT\SDK\Auth([
       'domain' => 'https://**xyz**.auth.smit.net', 
       'client_id' => 'XYZ', 
       'client_secret' => 'XYZ', 
       'redirect_uri' => 'https://your-app-or-website.test/callback(.php)',
   ]);
   ``` 
2. Add `$client->login()` to the pages you wish the user to be authenticated.
3. Add `$client->logout()` to the pages you wish the user to be forced to logout.
4. Make use of `$client->user()` for a full user object or use it's easily accessible functions in order to get specific values.
    1. `$client->user()->getLastName()` in order to get the surname of the user.
    2. Preferably you'll want to assign `$client->user()` to a variable such as `$user` like `$user = $client->user()` for re-usability.
5. If you want to know what scopes are available you can hit the `domain` with path `/scopes` for example `https://**xyz**.auth.smit.net/scopes` 
