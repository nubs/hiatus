# Hiatus
A PHP library for executing shell commands with an optional timeout.
> DEPRECATED: This library is no longer being actively maintained.
> [symfony/process](https://github.com/symfony/Process) has all the features of
> Hiatus and more.  It is recommended as an alternative.

## Requirements
PHP 5.4 or newer is the only requirement for this library.

## Installation
This package uses [composer](https://getcomposer.org) so you can just add
`nubs/hiatus` as a dependency to your `composer.json` file or execute the
following command:

```bash
composer require nubs/hiatus
```

## Usage
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

An exception-generating variant is also included:
```php
<?php
try {
    list($stdout, $stderr) = \Hiatus\execX('ls /foo');
} catch (Exception $e) {
    echo "Error occurred: {$e->getMessage()}\n";
    exit(1);
}

echo $stdout;
```

Both `exec` and `execX` can be given a string to pass on stdin:
```php
<?php
list($exitStatus, $stdout, $stderr) = \Hiatus\exec(
    'wc -c',
    [],
    null,
    'stdin test'
);

if ((int)$stdout !== 10) {
    echo "Well, this is awkward.\n";
}
```

## Contributing
Any changes, suggestions, or bug reports are welcome to be submitted on github.
Pull requests are encouraged!

## License
This project is licensed under the MIT License.
