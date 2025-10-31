<?php

use Illuminate\Support\Facades\Route;

Route::get('/{any}', function () {
    $path = public_path('index.html');
    if (File::exists($path)) {
        return File::get($path);
    }
    abort(404);
})->where('any', '^(?!api).*$');

Route::get('/', function () {
    return view('welcome');
});
