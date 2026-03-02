<?php

namespace App\Filament\Resources\ShelveResource\Pages;

use App\Filament\Resources\ShelveResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListShelves extends ListRecords
{
    protected static string $resource = ShelveResource::class;

    protected static ?string $title = 'Danh sách Kệ';

}
