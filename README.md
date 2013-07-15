# Hiatus
A PHP library for executing shell commands with an optional timeout.

## Requirements
PHP 5.4 or newer is the only requirement for this library.

## Usage
This library is available through [Composer](http://getcomposer.org).  Add the
following to your `composer.json` to include this library into your code.
```json
{
    "require": {
        "nubs/hiatus": "dev-master"
    }
}
```

Composer's autoloader will automatically include the namespaced functions for
use in your project.

Here's an example of how to execute a simple command:
```php
<?php
// Get the directory listing of the directory given by the user.
// NOTE: This is probably not a good idea to let users run arbitrary directory
// listings.
list($exitStatus, $stdout, $stderr) = \Hiatus\exec('ls -l', [$_POST['dir']]);

if ($exitStatus !== 0) {
    throw new Exception('Command failed.');
}

echo $stdout;
```

Including a timeout is simple:
```php
<?php
// Download the url given by the user, but fail if it takes more than 10
// seconds.
// NOTE: This is probably not a good idea to let users download arbitrary urls.
list($exitStatus, $stdout, $stderr) = \Hiatus\exec(
    'curl',
    [$_POST['url']],
    10
);

if ($exitStatus !== 0) {
    throw new Exception('Command failed.');
}

echo $stdout;
```

## Contributing
Any changes, suggestions, or bug reports are welcome to be submitted on github.
Pull requests are encouraged!

## License
This project is licensed under the MIT License.
