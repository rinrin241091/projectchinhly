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

Route::get('/dashboard/borrowings/pending-count-check', function () {
    $user = auth()->user();

    if (! $user || $user->role !== 'admin') {
        return response()->json(['count' => 0]);
    }

    $count = \App\Models\Borrowing::query()
        ->where('approval_status', 'pending')
        ->count();

    return response()->json(['count' => $count]);
})->name('borrowings.pending-count')->middleware('auth');

// Report export routes
Route::middleware(['auth'])->group(function () {
    Route::get('/reports/summary/pdf', [\App\Http\Controllers\ReportSummaryController::class, 'pdf'])
        ->name('report.summary.pdf');
    Route::get('/reports/summary/excel', [\App\Http\Controllers\ReportSummaryController::class, 'excel'])
        ->name('report.summary.excel');

    Route::get('/reports/progress/pdf', [\App\Http\Controllers\ReportExportController::class, 'progressPdf'])
        ->name('report.progress.pdf');
    Route::get('/reports/progress/excel', [\App\Http\Controllers\ReportExportController::class, 'progressExcel'])
        ->name('report.progress.excel');

    Route::get('/reports/record-statistics/pdf', [\App\Http\Controllers\ReportExportController::class, 'recordStatisticsPdf'])
        ->name('report.record-statistics.pdf');
    Route::get('/reports/record-statistics/excel', [\App\Http\Controllers\ReportExportController::class, 'recordStatisticsExcel'])
        ->name('report.record-statistics.excel');

    Route::get('/reports/record-document/pdf', [\App\Http\Controllers\ReportExportController::class, 'recordDocumentPdf'])
        ->name('report.record-document.pdf');
    Route::get('/reports/record-document/excel', [\App\Http\Controllers\ReportExportController::class, 'recordDocumentExcel'])
        ->name('report.record-document.excel');

    Route::get('/reports/room-directory/pdf', [\App\Http\Controllers\ReportExportController::class, 'roomDirectoryPdf'])
        ->name('report.room-directory.pdf');
    Route::get('/reports/room-directory/excel', [\App\Http\Controllers\ReportExportController::class, 'roomDirectoryExcel'])
        ->name('report.room-directory.excel');
});
