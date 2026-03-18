<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ArchiveRecord;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $totalRecords = ArchiveRecord::count(); // Get total number of archive records
        
        return view('filament.pages.dashboard', compact('totalRecords'));
    }
}
