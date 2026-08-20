<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

/**
 * Who can sign in.
 *
 * THERE WAS NO SCREEN FOR THIS AT ALL, which meant adding a colleague to the panel required a
 * database client and a hashed password. One person could reach the admin, and the only way to
 * change that was not in the admin.
 *
 * A ROLE IS WHAT GRANTS ACCESS. `User::canAccessPanel` admits anybody holding any role, so a user
 * with none is a user who cannot sign in — which is why the field is required rather than a
 * convenience. It was previously a hardcoded list of three role names, two of which did not exist
 * in this database, so the one real non-admin role was locked out with a flat 403.
 *
 * PASSWORDS ARE WRITE-ONLY. The field is blank on edit and only sets a password when something is
 * typed into it, so opening a colleague's record and pressing Save does not wipe their login.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Team';

    protected static ?string $modelLabel = 'team member';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(120),

            Forms\Components\TextInput::make('email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(190),

            Forms\Components\Select::make('roles')
                ->relationship('roles', 'name')
                ->multiple()
                ->preload()
                ->required()
                ->helperText('A role is what lets somebody sign in. A team member with none cannot reach the panel at all.'),

            Forms\Components\TextInput::make('password')
                ->password()
                ->revealable()
                ->helperText('Leave blank to keep the current password. Filling it in replaces theirs.')
                ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                /* Without this, saving an edit with the field untouched would write null over the
                   existing hash and lock the person out of their own account. */
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->required(fn (string $operation): bool => $operation === 'create')
                ->minLength(12)
                ->maxLength(200),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->placeholder('No role — cannot sign in'),
                Tables\Columns\TextColumn::make('created_at')->label('Added')->date('j M Y')->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    /* Deleting the account you are signed in as logs you out mid-request and, if it
                       is the only one holding super_admin, locks everybody out permanently. */
                    ->visible(fn (User $record): bool => $record->id !== auth()->id()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
