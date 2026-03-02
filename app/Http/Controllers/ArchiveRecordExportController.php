<?php

namespace App\Http\Controllers;

use App\Exports\ArchiveRecordsExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ArchiveRecordExportController extends Controller
{
    public function export(Request $request)
    {
        $query = ArchiveRecord::query(); // Adjust this query as needed
        return Excel::download(new ArchiveRecordsExport($query), 'archive_records.xlsx');
    }
}
