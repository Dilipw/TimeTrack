<?php

namespace App\Filament\Resources\TimeEntries\Schemas;

use App\Enums\TimeEntryStatus;
use App\Enums\TimeEntryType;
use App\Models\TimeEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TimeEntryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('employee.employee_code')
                    ->label('Employee')
                    ->formatStateUsing(function (?string $state, TimeEntry $record): string {
                        if (! $record->employee) {
                            return '-';
                        }

                        return "{$record->employee->employee_code} - {$record->employee->first_name} {$record->employee->last_name}";
                    })
                    ->weight('bold'),

                TextEntry::make('project.name')
                    ->label('Project')
                    ->placeholder('-'),

                TextEntry::make('task.title')
                    ->label('Task')
                    ->placeholder('-'),

                TextEntry::make('work_date')
                    ->label('Work Date')
                    ->date('d M Y'),

                TextEntry::make('start_time')
                    ->label('Start Time')
                    ->time('h:i A'),

                TextEntry::make('end_time')
                    ->label('End Time')
                    ->time('h:i A'),

                TextEntry::make('break_minutes')
                    ->label('Break')
                    ->formatStateUsing(
                        fn (?int $state): string => $state === null
                            ? '-'
                            : "{$state} minutes"
                    ),

                TextEntry::make('working_minutes')
                    ->label('Working Time')
                    ->formatStateUsing(
                        fn (?int $state): string => $state === null
                            ? '-'
                            : "{$state} minutes"
                    )
                    ->weight('bold'),

                TextEntry::make('entry_type')
                    ->label('Entry Type')
                    ->badge()
                    ->formatStateUsing(
                        fn (TimeEntryType $state): string => match ($state) {
                            TimeEntryType::REGULAR => 'Regular',
                            TimeEntryType::OVERTIME => 'Overtime',
                        }
                    )
                    ->color(
                        fn (TimeEntryType $state): string => match ($state) {
                            TimeEntryType::REGULAR => 'gray',
                            TimeEntryType::OVERTIME => 'warning',
                        }
                    ),

                TextEntry::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(
                        fn (TimeEntryStatus $state): string => match ($state) {
                            TimeEntryStatus::DRAFT => 'Draft',
                            TimeEntryStatus::SUBMITTED => 'Submitted',
                            TimeEntryStatus::APPROVED => 'Approved',
                            TimeEntryStatus::REJECTED => 'Rejected',
                            TimeEntryStatus::CANCELLED => 'Cancelled',
                        }
                    )
                    ->color(
                        fn (TimeEntryStatus $state): string => match ($state) {
                            TimeEntryStatus::DRAFT => 'gray',
                            TimeEntryStatus::SUBMITTED => 'info',
                            TimeEntryStatus::APPROVED => 'success',
                            TimeEntryStatus::REJECTED => 'danger',
                            TimeEntryStatus::CANCELLED => 'warning',
                        }
                    ),

                TextEntry::make('submitted_at')
                    ->label('Submitted At')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('-'),

                TextEntry::make('approved_at')
                    ->label('Approved At')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('-'),

                TextEntry::make('rejected_at')
                    ->label('Rejected At')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('-'),

                TextEntry::make('rejection_reason')
                    ->label('Rejection Reason')
                    ->placeholder('-')
                    ->columnSpanFull()
                    ->prose()
                    ->visible(
                        fn (TimeEntry $record): bool =>
                            $record->status === TimeEntryStatus::REJECTED
                            && filled($record->rejection_reason)
                    ),

                TextEntry::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('-'),

                TextEntry::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('-'),

                TextEntry::make('deleted_at')
                    ->label('Deleted')
                    ->dateTime('d M Y, h:i A')
                    ->visible(
                        fn (TimeEntry $record): bool => $record->trashed()
                    ),
            ]);
    }
}
