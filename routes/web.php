<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return 'x';
});

Route::get('/pay', function () {
    return view('google_pay');
});

use App\Http\Controllers\GooglePayController;
// routes/web.php

Route::post('/googlepay/process-payment', 'App\Http\Controllers\GooglePayController@processPayment');

