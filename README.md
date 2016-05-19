# Vicus

Roman neighborhood.

## Description

Built on symfony and symfony based components to build a decoupled, flexible and easy to follow framework.

This uses a container, event emitters / listeners and exception handlers.

## Install

```

"require": {
		"datacomp/vicus": "1.0.*",
},

"repositories": [
	{
		"type": "package",
		"package": {
			"name": "datacomp/vicus",
			"version": "1.0.*",
			"source": {
				"url": "git@gitlab.datacomp-intranet.com:datacomp/vicus",
				"type": "git",
				"reference": "1.0.*"
			},
			"autoload": {
				"psr-4": {
					"Datacomp\\Vicus\\": "src"
				}
			}
		}
	}
],

```

### front controller (app.php / index.php)

```php

$app = new \Datacomp\Vicus\Application($container);
$app->run();

```



## Versions

### 0.9.0
* Interface for Controller for `$this->_beforeAction`
* Base PHPMailer Provider - Dev
* Class formatting fix

### 0.8.0
* DeglobalizedMySQLSessionServiceProvider & Handler
* EnvironmentServiceProvider
* ConfigServiceProvider
* Updated DatabaseMangerProvider

### 0.7.0
* Added Smarty 3 support.
* Upgraded Bootable and Event Listener interfaces.

### 0.6.0
* Working setup. Restructured some code to be in the application front-controller instead of in vicus.


### 0.5.0
* Initial move from mhvillage code structure into its own framework to be used across other applications.
* This is a beta and is unstable.



[TODO]
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
