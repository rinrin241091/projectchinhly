<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\ArchiveRecordItem;
use App\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ArchiveRecordItemPageController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        $selectedOrganizationId = session('selected_archival_id');
        $search = trim((string) $request->query('q', ''));
        $perPage = (int) $request->query('per_page', 10);

        if (! in_array($perPage, [10, 25, 50], true)) {
            $perPage = 10;
        }

        $query = ArchiveRecordItem::query()
            ->with('organization:id,name');

        if ($user->role !== 'admin') {
            if (! $selectedOrganizationId || ! $user->hasOrganization((int) $selectedOrganizationId)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('organization_id', $selectedOrganizationId);
            }
        } elseif ($selectedOrganizationId) {
            $query->where('organization_id', $selectedOrganizationId);
        }

        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner->where('archive_record_item_code', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('document_date', 'like', "%{$search}%")
                    ->orWhereIn('organization_id', 
                        Organization::where('name', 'like', "%{$search}%")->pluck('id')->toArray()
                    );
            });
        }

        $paginator = $query
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $availableOrganizations = $user->role === 'admin'
            ? Organization::query()->orderBy('name')->get(['id', 'name'])
            : $user->organizations()->orderBy('name')->get(['organizations.id', 'organizations.name']);

        return Inertia::render('App/ArchiveRecordItems/Index', [
            'filters' => [
                'q' => $search,
                'per_page' => $perPage,
            ],
            'selectedOrganizationId' => $selectedOrganizationId ? (int) $selectedOrganizationId : null,
            'availableOrganizations' => $availableOrganizations
                ->map(fn ($org) => [
                    'id' => (int) $org->id,
                    'name' => $org->name,
                ])
                ->values()
                ->all(),
            'items' => [
                'data' => collect($paginator->items())
                    ->map(fn ($item) => [
                        'id' => $item->id,
                        'archive_record_item_code' => $item->archive_record_item_code,
                        'title' => $item->title,
                        'organization_name' => $item->organization?->name,
                        'document_date' => $item->document_date,
                        'description' => $item->description,
                        'edit_url' => route('filament.dashboard.resources.archive-record-items.edit', ['record' => $item->id]),
                        'view_url' => route('archive-record-items.view', ['id' => $item->id]),
                    ])
                    ->values()
                    ->all(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
                'links' => [
                    'prev' => $paginator->previousPageUrl(),
                    'next' => $paginator->nextPageUrl(),
                ],
            ],
        ]);
    }
}
