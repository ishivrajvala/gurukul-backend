<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\StorySubmissionResource\Pages;
use App\Models\StorySubmission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Families offering their story.
 *
 * NOTHING AUTO-PUBLISHES, and the form told the parent so before they pressed the button. That
 * promise is the reason many of them submitted at all, so approving here creates nothing on its
 * own: somebody writes the Story record from this.
 *
 * THE CONSENT WORDING IS SHOWN, not just a tick. Avdhara's framework requires explicit family
 * consent for identifiable child stories and images, and `consent_text` holds the exact sentence
 * that was on screen when they agreed. Read it before publishing anything showing a child —
 * consent given to one wording is not consent to a later one.
 */
class StorySubmissionResource extends Resource
{
    protected static ?string $model = StorySubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope-open';

    protected static ?string $navigationGroup = 'Inbox';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Story submissions';

    public static function getNavigationBadge(): ?string
    {
        $waiting = static::getModel()::where('status', 'pending')->count();

        return $waiting > 0 ? (string) $waiting : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('The family')->schema([
                Forms\Components\TextInput::make('name')->disabled(),
                Forms\Components\TextInput::make('email')->disabled(),
                Forms\Components\Select::make('age_stage_id')->relationship('ageStage', 'name')->label('Child\'s age')->disabled(),
            ])->columns(3),

            Forms\Components\Section::make('What they sent')->schema([
                Forms\Components\Textarea::make('story')->disabled()->rows(8)->columnSpanFull(),
                Forms\Components\FileUpload::make('photo_path')->image()->disk('public')->disabled()->label('Photo'),
                Forms\Components\FileUpload::make('video_path')->disk('public')->disabled()->label('Video'),
            ])->columns(2),

            Forms\Components\Section::make('Consent')->schema([
                Forms\Components\Placeholder::make('consent_given')
                    ->label('')
                    ->content(fn (?StorySubmission $record): string => $record?->has_consent
                        ? 'Consent given. The exact wording they agreed to is below — read it before publishing anything that shows a child.'
                        : 'NO CONSENT RECORDED. Nothing from this submission may be published.')
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('consent_text')
                    ->label('What they agreed to')
                    ->disabled()
                    ->rows(3)
                    ->columnSpanFull(),
            ]),

            Forms\Components\Section::make('Where it has got to')->schema([
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending — not yet read',
                        'approved' => 'Approved — to be written up',
                        'published' => 'Published as a Story',
                        'declined' => 'Not taken forward',
                    ])
                    ->required()
                    ->helperText('Approving does not create anything. Somebody writes the Story record from this.'),

                Forms\Components\Select::make('story_id')
                    ->relationship('story', 'title')
                    ->label('Published as')
                    ->searchable()
                    ->preload()
                    ->helperText('Link the Story once it exists, so this submission is traceable to what went live.'),

                Forms\Components\DateTimePicker::make('handled_at')->seconds(false),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Came in')->since()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->weight('semibold'),
                Tables\Columns\TextColumn::make('story')->limit(60)->wrap()->label('Story')->toggleable(),
                Tables\Columns\IconColumn::make('video_path')->label('Video')->boolean()->state(fn ($record): bool => filled($record->video_path)),
                Tables\Columns\IconColumn::make('has_consent')->label('Consent')->boolean()->trueColor('success')->falseColor('danger'),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning', 'approved' => 'primary', 'published' => 'success', default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending' => 'Pending', 'approved' => 'Approved', 'published' => 'Published', 'declined' => 'Not taken forward',
                ])->default('pending'),
            ])
            ->actions([Tables\Actions\EditAction::make()->label('Open')]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStorySubmissions::route('/'),
            'edit' => Pages\EditStorySubmission::route('/{record}/edit'),
        ];
    }
}
