<?php

namespace App\Http\Controllers;

use App\Exports\ArchiveRecordsExport;
use App\Models\ArchiveRecord;
use App\Models\Organization;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ArchiveRecordExportController extends Controller
{
    public function export(Request $request)
    {
        $query = ArchiveRecord::query();
        $organizationId = session('selected_archival_id');

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        if ($archiveRecordItemId = session('selected_archive_record_item_id')) {
            $query->where('archive_record_item_id', $archiveRecordItemId);
        }

        $organization = $organizationId ? Organization::find($organizationId) : null;

        return Excel::download(new ArchiveRecordsExport($query, $organization), 'archive_records.xlsx');
    }
}
