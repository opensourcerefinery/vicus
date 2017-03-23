# Vicus

Roman neighborhood.

## Description

Built on symfony and symfony based components to build a decoupled, flexible and easy to follow framework.

This uses a container, event emitters / listeners and exception handlers.

## Install

```

"require": {
		"opensourcerefinery/vicus": "1.0.*",
},


```

### front controller (app.php / index.php)

```php

$app = new \Vicus\Application($container);
$app->run();

```



## Versions


## License

Vicus is licensed under the MIT license.


## [TODO]
* Exception handling
* sub request
* document code flow
* clean up commented code
* decouple config files
* add template engine
* session class
* session handler
* lots and lots more
* refactor config parameter to be part of service file
