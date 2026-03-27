<?php

namespace App\Http\Controllers;

use App\Models\ArchiveRecordItem;
use Illuminate\Http\Request;
use NumberFormatter;
use Inertia\Inertia;

class ArchiveRecordPrintController extends Controller
{
    public function viewArchivalRecord($id)
    {
        $archiveRecordItem = ArchiveRecordItem::select('id', 'archive_record_item_code', 'title', 'organization_id')
            ->with([
                'organization' => fn($q) => $q->select('id', 'name', 'code', 'type'),
                'organization.archival' => fn($q) => $q->select('id', 'name'),
                'records' => fn($q) => $q->select('id', 'archive_record_item_id', 'box_id', 'code', 'reference_code', 'title', 'start_date', 'end_date', 'preservation_duration', 'page_count', 'note')->with('box:id,code'),
            ])
            ->findOrFail($id);

        // Sắp xếp danh sách hồ sơ
        $records = $archiveRecordItem->records->sortBy([
            ['box.code', 'asc'],
            ['code', 'asc'],
        ]);

        // Lấy giá trị nhỏ nhất và lớn nhất của hộp và hồ sơ
        $fromBox = $records->first()->box->code ?? '';
        $toBox = $records->last()->box->code ?? '';
        $fromRecord = $records->first()->code ?? '';
        $toRecord = $records->last()->code ?? '';

        // Tính số trang
        $recordsPerPage = 11;
        $pageCount = ceil($records->count() / $recordsPerPage);

        // Đếm số hộp
        $boxCount = $records->groupBy('box.code')->count();

        // Chuyển số thành chữ
        $formatter = new NumberFormatter('vi', NumberFormatter::SPELLOUT);
        $recordCountInWords = $formatter->format($records->count());
        $boxCountInWords = $formatter->format($boxCount);

        return Inertia::render('ArchiveRecord', [
            'archiveRecordItem' => [
                'id' => $archiveRecordItem->id,
                'archive_record_item_code' => $archiveRecordItem->archive_record_item_code,
                'title' => $archiveRecordItem->title,
                'description' => $archiveRecordItem->description,
                'document_date' => $archiveRecordItem->document_date,
                'page_num' => $archiveRecordItem->page_num,
                'organization' => [
                    'name' => $archiveRecordItem->organization?->name,
                    'code' => $archiveRecordItem->organization?->code,
                    'archival_name' => $archiveRecordItem->organization?->archival?->name,
                ],
            ],
            'records' => $records->map(fn ($record) => [
                'id' => $record->id,
                'box_code' => $record->box?->code,
                'code' => $record->code,
                'title' => $record->title,
                'start_date' => $record->start_date,
                'end_date' => $record->end_date,
                'preservation_duration' => $record->preservation_duration,
                'condition' => $record->condition,
                'page_count' => $record->page_count,
                'note' => $record->note,
            ])->values()->all(),
            'pageCount' => $pageCount,
            'fromBox' => $fromBox,
            'toBox' => $toBox,
            'fromRecord' => $fromRecord,
            'toRecord' => $toRecord,
            'boxCount' => $boxCount,
            'recordCountInWords' => ucfirst($recordCountInWords),
            'boxCountInWords' => ucfirst($boxCountInWords),
            'updatePageNumUrl' => route('archive-record-items.update-page-num', ['id' => $archiveRecordItem->id]),
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
