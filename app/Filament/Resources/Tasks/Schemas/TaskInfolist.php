<?php

namespace App\Filament\Resources\Tasks\Schemas;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TaskInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('project.name')
                    ->label('Project')
                    ->weight('bold'),

                TextEntry::make('parent.title')
                    ->label('Parent Task')
                    ->placeholder('-'),

                TextEntry::make('title')
                    ->label('Task Title')
                    ->weight('bold'),

                TextEntry::make('description')
                    ->label('Description')
                    ->placeholder('No description provided.')
                    ->columnSpanFull()
                    ->prose(),

                TextEntry::make('priority')
                    ->label('Priority')
                    ->badge()
                    ->formatStateUsing(
                        fn (TaskPriority $state): string => match ($state) {
                            TaskPriority::LOW => 'Low',
                            TaskPriority::MEDIUM => 'Medium',
                            TaskPriority::HIGH => 'High',
                            TaskPriority::URGENT => 'Urgent',
                        }
                    )
                    ->color(
                        fn (TaskPriority $state): string => match ($state) {
                            TaskPriority::LOW => 'gray',
                            TaskPriority::MEDIUM => 'info',
                            TaskPriority::HIGH => 'warning',
                            TaskPriority::URGENT => 'danger',
                        }
                    ),

                TextEntry::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(
                        fn (TaskStatus $state): string => match ($state) {
                            TaskStatus::TODO => 'To Do',
                            TaskStatus::IN_PROGRESS => 'In Progress',
                            TaskStatus::COMPLETED => 'Completed',
                            TaskStatus::CANCELLED => 'Cancelled',
                        }
                    )
                    ->color(
                        fn (TaskStatus $state): string => match ($state) {
                            TaskStatus::TODO => 'gray',
                            TaskStatus::IN_PROGRESS => 'info',
                            TaskStatus::COMPLETED => 'success',
                            TaskStatus::CANCELLED => 'danger',
                        }
                    ),

                TextEntry::make('due_date')
                    ->label('Due Date')
                    ->date('d M Y')
                    ->placeholder('-'),

                TextEntry::make('estimated_minutes')
                    ->label('Estimated Time')
                    ->formatStateUsing(
                        fn (?int $state): string => $state === null
                            ? '-'
                            : "{$state} minutes"
                    ),

                TextEntry::make('assignees')
                    ->label('Assignees')
                    ->state(function (Task $record): string {
                        $assignees = $record->activeAssignees
                            ->map(
                                fn ($employee): string => "{$employee->employee_code} - {$employee->first_name} {$employee->last_name}"
                            )
                            ->implode(', ');

                        return $assignees !== '' ? $assignees : '-';
                    })
                    ->columnSpanFull(),

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
                    ->visible(fn (Task $record): bool => $record->trashed()),
            ]);
    }
}
