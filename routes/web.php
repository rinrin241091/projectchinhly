<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/print-box-labels', [\App\Http\Controllers\BoxLabelPrintController::class, 'print'])->name('print.box.labels');

// New export route
Route::get('/export-archive-records', [\App\Http\Controllers\ArchiveRecordExportController::class, 'export'])->name('export.archive.records');

use App\Http\Controllers\ArchiveRecordPrintController;
use App\Http\Controllers\DocumentListController;

Route::get('/archive-record-items/{id}/print', [ArchiveRecordPrintController::class, 'viewArchivalRecord'])
    ->name('archive-record-items.view');

Route::get('/archive-records/{id}/documents', [DocumentListController::class, 'show'])
    ->name('archive-records.documents');

/* Route::get('/archive-records/{id}/documents/export-pdf', [DocumentListController::class, 'exportPdf'])
    ->name('archive-records.documents.export-pdf'); */

Route::get('/archive-records/{id}/documents/export-excel', [DocumentListController::class, 'exportExcel'])
    ->name('archive-records.documents.export-excel');

Route::post('/archive-record-items/{id}/update-page-num', [ArchiveRecordPrintController::class, 'updatePageNum'])
    ->name('archive-record-items.update-page-num');
