<?php

namespace App\Http\Controllers;

use App\Models\Box;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class BoxLabelPrintController extends Controller
{
    public function print(Request $request)
    {
        $boxIds = $request->input('ids', []);
        $boxes = Box::whereIn('id', $boxIds)->get();

        $pdf = Pdf::loadView('pdf.box-labels', compact('boxes'));
        return $pdf->download('nhan-hop.pdf');
    }
}
