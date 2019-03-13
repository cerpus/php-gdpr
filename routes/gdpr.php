<?php
Route::group([
    'namespace' => '\\Cerpus\\Gdpr\\Controllers',
    'middleware' => [
        Cerpus\Gdpr\Middleware\GdprMiddleware::class,
    ]
], function () {
    Route::post('/api/gdpr/delete', 'GdprDeleteController@store')->name('gdpr.store');
    Route::get('/api/gdpr/delete/status', 'GdprDeleteController@index')->name('gdpr.index');
    Route::get('/api/gdpr/{id}/status', 'GdprDeleteController@show')->name('gdpr.show');
});



