<?php

Route::group(['middleware' => ['web', 'auth', 'tenant', 'service.accounting']], function() {

	Route::prefix('credit-notes')->group(function () {

        //Route::get('summary', 'Rutatiina\Estimate\Http\Controllers\CreditNoteController@summary');
        Route::post('export-to-excel', 'Rutatiina\CreditNote\Http\Controllers\CreditNoteController@exportToExcel');
        Route::post('{id}/approve', 'Rutatiina\CreditNote\Http\Controllers\CreditNoteController@approve');
        Route::get('{id}/copy', 'Rutatiina\CreditNote\Http\Controllers\CreditNoteController@copy');

    });

    Route::resource('credit-notes/settings', 'Rutatiina\CreditNote\Http\Controllers\CreditNoteSettingsController');
    Route::resource('credit-notes', 'Rutatiina\CreditNote\Http\Controllers\CreditNoteController');

});
