<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\ProjectMember;
use App\Services\ProjectMemberService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProjectMembersRelationManager extends RelationManager
{
    protected static string $relationship = 'projectMembers';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $title = 'Project Members';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query): Builder => $query
                    ->whereNull('removed_at')
                    ->with('employee')
            )
            ->columns([
                TextColumn::make('employee.employee_code')
                    ->label('Employee ID')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('employee.first_name')
                    ->label('First Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('employee.last_name')
                    ->label('Last Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('employee.designation.name')
                    ->label('Designation')
                    ->placeholder('-'),

                TextColumn::make('assigned_at')
                    ->label('Assigned At')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('addMember')
                    ->label('Add Member')
                    ->icon('heroicon-o-user-plus')
                    ->form([
                        Select::make('employee_id')
                            ->label('Employee')
                            ->options(function (ProjectMembersRelationManager $livewire): array {
                                $projectId = $livewire->getOwnerRecord()->getKey();

                                return Employee::query()
                                    ->where('status', EmployeeStatus::ACTIVE)
                                    ->whereNotExists(function ($query) use ($projectId): void {
                                        $query
                                            ->selectRaw('1')
                                            ->from('project_members')
                                            ->whereColumn(
                                                'project_members.employee_id',
                                                'employees.id'
                                            )
                                            ->where('project_members.project_id', $projectId)
                                            ->whereNull('project_members.removed_at');
                                    })
                                    ->orderBy('employee_code')
                                    ->get()
                                    ->mapWithKeys(
                                        fn (Employee $employee): array => [
                                            $employee->id => "{$employee->employee_code} - {$employee->first_name} {$employee->last_name}",
                                        ]
                                    )
                                    ->all();
                            })
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->action(function (
                        array $data,
                        ProjectMembersRelationManager $livewire,
                    ): void {
                        app(ProjectMemberService::class)->add(
                            actor: auth()->user(),
                            project: $livewire->getOwnerRecord(),
                            employeeId: (int) $data['employee_id'],
                        );
                    }),
            ])
            ->recordActions([
                Action::make('removeMember')
                    ->label('Remove')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Remove project member')
                    ->modalDescription(
                        'This will remove the employee from the active project members. Membership history will be preserved.'
                    )
                    ->action(function (
                        ProjectMember $record,
                        ProjectMembersRelationManager $livewire,
                    ): void {
                        app(ProjectMemberService::class)->remove(
                            actor: auth()->user(),
                            project: $livewire->getOwnerRecord(),
                            employeeId: $record->employee_id,
                        );
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ])
            ->defaultSort('assigned_at', 'desc');
    }
}