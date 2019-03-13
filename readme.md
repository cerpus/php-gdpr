#GDPR helper service
This is a Laravel package to help implement GDPR compliance in a system

## Installation
```bash
composer require cerpus/gdpr
```

If you are running on Laravel 5.4 or less you must add the service provider to `config/app.php`.
```php
'providers' => [
    ...
    Cerpus\Gdpr\ServiceProvider::class,

];
```
In Laravel 5.5 and up the package will auto register.

Publish artifacts to your app.
```bash
php artisan vendor:publish --provider="Cerpus\Gdpr\GdprServiceProvider"
```

This will publish the config file to `config/gdpr.php`. The GdprService will have a default deletion file, and you have to make a class that implements the `Cerpus\Gdpr\Contracts\GdprDeletionContract` interface. Change the `config/gdpr.php` file to point to your implementation.
```php
<?php
return [
  'deletion-class' => Cerpus\Gdpr\DummyDeletion::class,
  ...
];
```
`php artisan migrate` to run the published migration(s).

## Usage
To implement the GDPR deletion in your system create a class somewhere in your app that implements the `Cerpus\Gdpr\Contracts\GdprDeletionContract`.

See `src/DummyDeletion.php` for the default example. You can copy this and rename it to start you deletion class.

As an example if you create a file in `app/Gdpr/GdprDelete.php` you must update the `config/gdpr.php` like this:
```php
return [
  'deletion-class' => App\Gdpr\GdprDelete::class,
  ...  
];
```

The `delete` method will receive a `GdprDeletionRequest` as parameter. The request itself is in $deletionRequest->payload and will at least include an AuthId (userId).

You can log the progress of the deletion using `$deletionRequest->log('processing', <your message here>);`

When the deletion request is finished you MUST add a log with the status `finished` like so:

`$deletionRequest->log('finished', <A message can be included if you want>);`

 

