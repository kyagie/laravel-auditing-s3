# Laravel Auditing S3 Driver

This driver provides the ability to save your model audits in CSV files. It's integrated with Laravel's Storage system so you can use an S3 bucket specified in your application as the storage destinations of the audit files.

## Installation

This driver requires that you are using `owen-it/laravel-auditing: v13.0.0` or higher.

You can install the package via composer:

```bash
composer require kyagie/laravel-auditing-s3
```

## Setup

You need to add the following config entries in config/audit.php if you need to change the default behaviour of the driver.
The `drivers` key of the config file should look like so:

```php
    ...
     'drivers' => [
        'database' => [
            'table'      => 'audits',
            'connection' => null,
        ],
        'filesystem' => [
            'disk'         => 'local',
            'dir'          => 'audit',
            'filename'     => 'audit.csv',
            'logging_type' => 'single',
        ],
    ],
    ...
```
