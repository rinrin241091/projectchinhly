<?php

namespace App\Http\Controllers;

use App\Models\ArchiveRecord;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $totalRecords = ArchiveRecord::count(); // Get total number of archive records

        return Inertia::render('Dashboard', [
            'totalRecords' => $totalRecords,
        ]);
    }
}
