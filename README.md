# Vicus

A Roman neighborhood / It takes a village.


## Description

Built on silex and symfony based components to build a decoupled, flexible and easy to follow framework.

Vicus is a thin layer over an upgraded silex install. It was designed to meet the needs of small projects that had already very coupled and unstructured code needing to be transitioned into a structured modern codebase. From that it was also used for small projects that needed clean and clear APIs along with a simple way to manage and define clean urls. It is currently running a few commercial projects (ones with 300k monthly users), a few personal projects and a side project.

The hope for vicus now is to provide help on getting a site running quickly without having to commit to a complicated robust framework like symfony.

This uses a container, event emitters / listeners and exception handlers.

## Install

```
"require": {
    "opensourcerefinery/vicus": "1.0.*",
},
```

`Please use the Vicus Planimetria repo to get started`



### front controller (app.php / index.php)

```php
$app = new \Vicus\Application($container);
$app->run();
```

## Versions

#### 1.0.0

* License was added

## License

Vicus is licensed under the MIT license.

## [TODO]

- Exception handling
- sub request
- document code flow
- clean up commented code
- lots and lots more
- refactor config parameter to be part of service file
