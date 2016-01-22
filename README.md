⚠️ This is a work in progress. ⚠️

# Restable
The probabaly tiniest PHP extensible micro-framework there is.

Remember this 'framework' is very tiny and has not been benchmarked, but should be very very fast.

## Quick Start
The famous Hello World. Make sure to include the [`.htaccess`](./.htaccess).
```php
<?php

require_once 'Restable/Restable.php';
$app = new Restable();

$app->get('/', function() {
    echo 'Hello World';
});

$app->start();
```

That's it, that's the whole file! Now go to [`localhost`](http://localhost) or wherever you host your PHP and voilà.

## [`.htaccess`](./.htaccess)
Use this [`.htaccess`](./.htaccess) file to route everything to `index.php`. You can change the file you want to route to, by replacing the `index.php` with anything else.
```
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

## Documentation
First include the [*Restable source*](./Restable/Restable.php) and initialize *Restable* by creating a new instance. We will call it `app` for this documentation.
```php
require_once 'Restable/Restable.php';
$app = new Restable();
```

After registering your routes make sure to `start` *Restable*.
```php
$app->start();
```

### Routes
#### Registering Routes
You can use the `register` function to add a new route to be handled.
```php
$app->register('GET', '/path', function() {
    echo 'do something';
});
```

This is equivalent to the following.
```php
function some_function() {
    echo 'do something';
}

$app->register('GET', '/path', 'some_function');
```

You can specify the `HTTP Method`, the `path`, and the `function` that will be executed.

#### HTTP Method Shorthands
You also have a variety of shorthand register functions.

* `get`
* `post`
* `update`
* `delete`

You can use these shorthands just like the `$app->get()` in the Hello World tutorial.

*e.g.* `post`
```php
$app->post('/user', function() {
    echo 'add new user';
});
```

### Parameters
You can parse parameters from the resource URL. Just add a `:` in the `path`.

```php
$app->update('/user/:user_id', function($user_id) {
    echo 'update user ' . $user_id;
});
```

### Hooks
You can easily extend your routes by adding hooks. There are `before` and `after` hooks. You can pass an array of hooks as an additional optional argument to the routing registration.

```php
$app->get('/secret', function() {
    echo 'secret information';
}, array(
    'before' => function() {
        if (!is_allowed_to_read_secrets()) {
            echo 'Oops, you should\'t see this... Bye bye!';
            exit;
        }
    },
));
```

You can pass multiple hooks, even multiple hooks of the same kind.
*e.g.* multiple `before` hooks and an `after` hook

```php
function include_config() {
    require 'superFancyConfig.php';
}

$app->get('/secret', function() {
    echo 'secret information';
}, array(
    'before' => 'include_config',
    'before' => function() {
        if (!is_allowed_to_read_secrets()) {
            echo 'Oops, you should\'t see this... Bye bye!';
            exit;
        }
    },
    'after' => function() {
        echo 'this happens last';
    },
));
```

### Request & Response
***TODO:** status, bad path, json, request path, etc. *

## Todo
[x] Add simple JSON output
[ ] Add simple stats code
[ ] Add additional request and response types
[ ] Add multiple parameter capability
[ ] Add API blueprints
[ ] Add testing
[ ] Finish documentation
