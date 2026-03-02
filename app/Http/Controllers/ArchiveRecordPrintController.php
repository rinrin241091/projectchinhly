<?php

namespace App\Http\Controllers;

use App\Models\ArchiveRecordItem;
use Illuminate\Http\Request;
use NumberFormatter;

class ArchiveRecordPrintController extends Controller
{
    public function viewArchivalRecord($id)
    {
        $archiveRecordItem = ArchiveRecordItem::with('organization.archival', 'records', 'records.box')->findOrFail($id);

        // Sắp xếp danh sách hồ sơ
        $records = $archiveRecordItem->records->sortBy([
            ['box.code', 'asc'],
            ['code', 'asc'],
        ]);

        // Lấy giá trị nhỏ nhất và lớn nhất của hộp và hồ sơ
        $fromBox = $records->first()->box->code ?? 'N/A';
        $toBox = $records->last()->box->code ?? 'N/A';
        $fromRecord = $records->first()->code ?? 'N/A';
        $toRecord = $records->last()->code ?? 'N/A';

        // Tính số trang
        $recordsPerPage = 11;
        $pageCount = ceil($records->count() / $recordsPerPage);

        // Đếm số hộp
        $boxCount = $records->groupBy('box.code')->count();

        // Chuyển số thành chữ
        $formatter = new NumberFormatter('vi', NumberFormatter::SPELLOUT);
        $recordCountInWords = $formatter->format($records->count());
        $boxCountInWords = $formatter->format($boxCount);

        return view('archive_record', [
            'archiveRecordItem' => $archiveRecordItem,
            'records' => $records,
            'pageCount' => $pageCount,
            'fromBox' => $fromBox,
            'toBox' => $toBox,
            'fromRecord' => $fromRecord,
            'toRecord' => $toRecord,
            'boxCount' => $boxCount,
            'recordCountInWords' => ucfirst($recordCountInWords),
            'boxCountInWords' => ucfirst($boxCountInWords),
        ]);
    }

    public function updatePageNum(Request $request, $id)
    {
        $validated = $request->validate([
            'page_num' => 'required|numeric|min:1',
        ]);

        $archiveRecordItem = ArchiveRecordItem::findOrFail($id);
        $archiveRecordItem->page_num = $validated['page_num'];
        $archiveRecordItem->save();

        return redirect()->back()->with('status', 'Số trang đã được cập nhật.');
    }
}
