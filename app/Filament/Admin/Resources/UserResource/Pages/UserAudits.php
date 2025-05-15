<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use App\Models\User;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Columns\Column;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\View\View;
use OwenIt\Auditing\Models\Audit;

class UserAudits extends ManageRelatedRecords
{
    protected static string $resource = UserResource::class;

    protected static string $relationship = 'audits';

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $title = 'Audit Log';

    public static function getNavigationLabel(): string
    {
        return 'Audit Log';
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->query(function (Builder $query) {
                /** @var User $record */
                $record = $this->record;

                return Audit::query()
                    ->where('user_id', $record->id);
            })
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('auditable_type')
                    ->searchable(['auditable_type'])
                    ->formatStateUsing(fn ($record): HtmlString => $this->mapTypeToResource($record)),
                Tables\Columns\TextColumn::make('event')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->since()
                    ->sortable()
                    ->label('Created'),
                Tables\Columns\TextColumn::make('old_values')
                    ->formatStateUsing(fn (Column $column, $record, $state): View => view('filament-auditing::tables.columns.key-value', ['state' => $this->mapRelatedColumns($column->getState(), $record)]))
                    ->label(trans('filament-auditing::filament-auditing.column.old_values')),
                Tables\Columns\TextColumn::make('new_values')
                    ->formatStateUsing(fn (Column $column, $record, $state): View => view('filament-auditing::tables.columns.key-value', ['state' => $this->mapRelatedColumns($column->getState(), $record)]))
                    ->label(trans('filament-auditing::filament-auditing.column.new_values')),
            ])
            ->filters([
            ])
            ->headerActions([
            ])
            ->actions([
            ])
            ->bulkActions([
            ]);
    }

    // Copied from Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager
    protected function mapRelatedColumns(mixed $state, mixed $record): mixed
    {
        $relationshipsToUpdate = Arr::wrap(config('filament-auditing.mapping'));

        if (count($relationshipsToUpdate) !== 0) {
            foreach ($relationshipsToUpdate as $key => $relationship) {
                if (array_key_exists($key, $state)) {
                    $state[$relationship['label']] = $relationship['model']::find($state[$key])?->{$relationship['field']};
                    unset($state[$key]);
                }
            }
        }

        return $state;
    }

    /**
     * Map the auditable type to the corresponding Filament resource.
     *
     * $record is actually an instance of OwenIt\Auditing\Models\Audit,
     * but that model doesn't have it's attributes defined in a way that makes phpstsan happy
     */
    protected function mapTypeToResource(mixed $record): HtmlString
    {
        // autitable_type starts with the first character lowercase
        $baseClassName = Str::ucfirst($record->auditable_type);

        // Map the base class name to the corresponding Filament resource
        $resourceClass = "App\\Filament\\Admin\\Resources\\{$baseClassName}Resource";

        if (class_exists($resourceClass)) {
            $resourceUrl = $resourceClass::getUrl('view', ['record' => $record->auditable_id]);
            $icon = $resourceClass::getNavigationIcon();

            return new HtmlString(Blade::render(
                '<x-filament::link href="{{ $url }}" color="primary" icon="{{ $icon }}">{{ $label }}</x-filament::link>',
                [
                    'url' => $resourceUrl,
                    'label' => $baseClassName,
                    'icon' => $icon,
                ]
            ));
        }

        // Fallback if no resource is found
        return new HtmlString($baseClassName);
    }
}
