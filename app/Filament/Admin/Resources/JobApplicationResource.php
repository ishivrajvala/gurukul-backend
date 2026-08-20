<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\JobApplicationResource\Pages;
use App\Models\JobApplication;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Applications.
 *
 * ITS OWN LIST, not filtered out of the general enquiries inbox. An application read three weeks
 * late is a candidate who has taken another job, and interleaving them with newsletter signups is
 * how that happens. The list defaults to pending and shows a badge on the sidebar, because this is
 * the one inbox on this site where the cost of not looking falls on somebody else.
 *
 * THE CV IS ON THE PRIVATE DISK and there is no URL to it. Download streams it through the panel,
 * so the session is the authorisation — a CV carries a home address and a phone number, and the
 * public disk is readable by anybody who guesses a filename.
 *
 * NOTHING HERE IS EDITABLE except the status. Somebody's application is a record of what they sent;
 * an admin screen that lets a reviewer retype a candidate's own words is a screen that eventually
 * does.
 */
class JobApplicationResource extends Resource
{
    protected static ?string $model = JobApplication::class;

    /*
     * FOUND BY, and CALLED. Global search stays off until a resource answers both: `$recordTitle`
     * is what a result reads as in the list, and the attributes are what it matches on.
     *
     * Deliberately narrow. Searching a body of text finds every article that mentions a word, which
     * is a research tool rather than a way to reach the one record somebody has in mind.
     */
    protected static ?string $recordTitleAttribute = 'email';

    /** @return array<string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        return 'Application: '.\Illuminate\Support\Str::limit((string) $record->email, 60);
    }

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Careers';

    protected static ?string $navigationLabel = 'Applications';

    protected static ?string $modelLabel = 'application';

    protected static ?int $navigationSort = 2;

    /** Pending count on the sidebar. The one inbox whose delay costs a candidate, not us. */
    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::where('status', 'pending')->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->disabled(),
            Forms\Components\TextInput::make('email')->disabled(),
            Forms\Components\TextInput::make('phone')->disabled(),

            Forms\Components\Select::make('job_role_id')
                ->label('Applied for')
                ->relationship('role', 'title')
                ->disabled(),

            Forms\Components\TextInput::make('portfolio_url')
                ->label('Portfolio')
                ->helperText('What several of these roles are actually judged on.')
                ->disabled()
                ->columnSpanFull(),

            Forms\Components\Textarea::make('note')
                ->label('What they wanted us to know')
                ->disabled()
                ->rows(5)
                ->columnSpanFull(),

            Forms\Components\Placeholder::make('cv')
                ->label('CV')
                ->content(fn (?JobApplication $record): string => $record?->cv_path
                    ? 'Attached. It is on the private disk, not the public one — use Download CV above to read it.'
                    : 'None.')
                ->columnSpanFull(),

            Forms\Components\Select::make('status')
                ->options([
                    'pending' => 'Pending',
                    'shortlisted' => 'Shortlisted',
                    'declined' => 'Declined',
                    'hired' => 'Hired',
                ])
                ->required(),

            Forms\Components\DateTimePicker::make('reviewed_at')->seconds(false),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Applied')->date('j M Y')->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('role.title')->label('For')->searchable()->wrap(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'shortlisted' => 'success',
                        'hired' => 'success',
                        'declined' => 'danger',
                        default => 'warning',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('job_role_id')
                    ->label('Role')
                    ->relationship('role', 'title'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'shortlisted' => 'Shortlisted',
                        'declined' => 'Declined',
                        'hired' => 'Hired',
                    ])
                    ->default('pending'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Open'),
                Tables\Actions\Action::make('cv')
                    ->label('Download CV')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->visible(fn (JobApplication $record): bool => filled($record->cv_path))
                    ->action(fn (JobApplication $record) => Storage::disk('local')->download(
                        $record->cv_path,
                        static::cvFilename($record),
                    )),
            ]);
    }

    /**
     * What the download is called on the reviewer's machine.
     *
     * The stored name is a random hash, which is right on disk and useless in a downloads folder
     * once three of them are sitting there.
     */
    public static function cvFilename(JobApplication $record): string
    {
        $stem = Str::slug(implode(' - ', array_filter([
            $record->name ?: $record->email,
            $record->role?->title,
        ]))) ?: 'cv';

        return $stem.'.'.pathinfo($record->cv_path, PATHINFO_EXTENSION);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJobApplications::route('/'),
            'edit' => Pages\EditJobApplication::route('/{record}/edit'),
        ];
    }
}
