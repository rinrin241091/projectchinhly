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

// Route for changing organization/archival - available to all authenticated users
Route::post('/change-organization', function (\Illuminate\Http\Request $request) {
    $user = auth()->user();
    $organizationId = $request->input('organization_id');
    
    if (!$organizationId) {
        return response()->json(['success' => false, 'message' => 'Invalid organization ID'], 422);
    }
    
    // Check if user has access to this organization
    if ($user->role !== 'admin' && !$user->hasOrganization($organizationId)) {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
    }
    
    // Get organization with archival
    $organization = \App\Models\Organization::find($organizationId);
    if (!$organization) {
        return response()->json(['success' => false, 'message' => 'Organization not found'], 404);
    }
    
    session([
        'selected_archival_id' => $organizationId,
        'archival_id' => $organization->archival_id, // Load archival of the organization
        'selected_archive_record_item_id' => null, // Reset archive record item
    ]);
    
    return response()->json(['success' => true, 'message' => 'Organization changed successfully']);
})->name('change-organization')->middleware('auth');
