<?php

namespace App\Filament\Resources\TimeEntries\Pages;

use App\Enums\TimeEntryStatus;
use App\Filament\Resources\TimeEntries\TimeEntryResource;
use App\Models\TimeEntry;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTimeEntry extends ViewRecord
{
    protected static string $resource = TimeEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(
                    fn (TimeEntry $record): bool => in_array(
                        $record->status,
                        [
                            TimeEntryStatus::DRAFT,
                            TimeEntryStatus::REJECTED,
                        ],
                        true
                    )
                ),
        ];
    }
}