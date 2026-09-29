<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Acá había un `GET /estudiantes/{est_codigo}/padres` SIN autenticación. No
// llegaba a exponerse porque routes/web.php declara la misma URI después y la
// sobrescribe en el RouteCollection (esa sí exige login), pero era un descuido
// a un reordenamiento de distancia. La ruta viva es la de web.php.
