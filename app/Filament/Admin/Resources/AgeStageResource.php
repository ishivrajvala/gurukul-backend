<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AgeStageResource\Pages;
use App\Models\AgeStage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * The six developmental stages.
 *
 * THESE ARE THE PATHWAYS THE SITE ROUTES ON (/seekers … /visionaries), so `key` is not a label —
 * it is a URL. Read-only after creation for that reason. Comps have variously called 8-11
 * "Connectors" and 14-16 "Pathfinders"; renaming here would rename live pathways.
 */
class AgeStageResource extends Resource
{
    protected static ?string $model = AgeStage::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Taxonomy';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Age stages';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->helperText('Pathway name, e.g. "Builders".')
                ->required(),

            Forms\Components\TextInput::make('range_label')
                ->label('Range')
                ->helperText('The chip label. Uses an en dash, e.g. 6–8.')
                ->required(),

            Forms\Components\TextInput::make('age_from')->numeric()->required(),
            Forms\Components\TextInput::make('age_to')->numeric()->required(),

            Forms\Components\TextInput::make('key')
                ->helperText('The pathway route the site already uses. Changing it breaks /seekers and friends.')
                ->required()
                ->disabledOn('edit')
                ->unique(ignoreRecord: true),

            Forms\Components\TextInput::make('position')->numeric()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->columns([
                Tables\Columns\TextColumn::make('range_label')->label('Ages')->weight('semibold'),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('key')->color('gray'),
            ])
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAgeStages::route('/'),
            'create' => Pages\CreateAgeStage::route('/create'),
            'edit' => Pages\EditAgeStage::route('/{record}/edit'),
        ];
    }
}
