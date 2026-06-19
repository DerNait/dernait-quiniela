<?php

use Illuminate\Support\Facades\Route;

// The Vue SPA owns all client-side routing. Every non-API path returns the
// same shell so deep links (e.g. /quinielas/1) work on refresh.
Route::view('/{any?}', 'app')->where('any', '^(?!api).*$');
