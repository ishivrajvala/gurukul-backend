<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\JobRoleResource\Pages;
use App\Models\JobRole;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Open roles.
 *
 * WHAT YOU WRITE HERE REACHES JOB BOARDS. The careers page emits each row as JobPosting structured
 * data, so `location`, `commitment` and the posted date are read by Google as facts about a real
 * vacancy, not as page copy. A role marked Full time in Ahmedabad that is actually a remote
 * contract is a wrong answer given to every candidate who searches before they ever reach the site.
 *
 * CLOSE A ROLE, DO NOT DELETE IT. Unticking Published takes it off the site immediately and keeps
 * the applications attached to something that says what they were for. Deleting a role that has
 * applications is refused by the database on purpose.
 *
 * "Openings" is a COUNT OF SEATS, and it is the one number this site does show. It is not social
 * proof — nobody is more likely to apply because two people will be hired — it is a fact a
 * candidate uses to judge their odds, which is the opposite of what a follower count does.
 */
class JobRoleResource extends Resource
{
    protected static ?string $model = JobRole::class;

    /*
     * FOUND BY, and CALLED. Global search stays off until a resource answers both: `$recordTitle`
     * is what a result reads as in the list, and the attributes are what it matches on.
     *
     * Deliberately narrow. Searching a body of text finds every article that mentions a word, which
     * is a research tool rather than a way to reach the one record somebody has in mind.
     */
    protected static ?string $recordTitleAttribute = 'title';

    /** @return array<string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'team'];
    }

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        return 'Open role: '.\Illuminate\Support\Str::limit((string) $record->title, 60);
    }

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationGroup = 'Careers';

    protected static ?string $navigationLabel = 'Open roles';

    protected static ?string $modelLabel = 'role';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')
                ->helperText('The job title as a candidate would search for it.')
                ->required()
                ->maxLength(120)
                ->columnSpanFull(),

            Forms\Components\TextInput::make('slug')
                ->helperText('The anchor a card links to on the careers page. Changing it breaks any link already shared.')
                ->required()
                ->disabledOn('edit')
                ->unique(ignoreRecord: true),

            Forms\Components\TextInput::make('team')
                ->helperText('e.g. Design, Content, Community.')
                ->required()
                ->maxLength(60),

            Forms\Components\TextInput::make('location')
                ->helperText('e.g. Ahmedabad, or "Remote, India". This is published as structured data — a wrong value reaches job boards as fact.')
                ->required()
                ->maxLength(120),

            Forms\Components\TextInput::make('commitment')
                ->helperText('Full time, Part time, Contract. Also published as structured data.')
                ->required()
                ->datalist(['Full time', 'Part time', 'Contract', 'Internship'])
                ->maxLength(60),

            Forms\Components\TextInput::make('openings')
                ->label('Seats')
                ->helperText('How many people are being hired into this role. Shown as "2 openings".')
                ->numeric()
                ->minValue(1)
                ->default(1)
                ->required(),

            Forms\Components\TextInput::make('experience')
                ->helperText('A range, e.g. "1 to 2 years". Freshers read "0 to 2 years".')
                ->maxLength(60),

            Forms\Components\DatePicker::make('date_posted')
                ->helperText('Required by the structured data. Job boards sort and expire listings on it.')
                ->required()
                ->default(now()),

            Forms\Components\Textarea::make('summary')
                ->helperText('One or two sentences, shown on the card before anyone opens the role.')
                ->required()
                ->rows(3)
                ->columnSpanFull(),

            /*
             * Repeaters rather than a textarea of lines: they are rendered as separate bullets, and
             * a textarea makes an editor guess whether a blank line, a dash or a bullet character
             * is what the page splits on.
             */
            Forms\Components\Repeater::make('responsibilities')
                ->label('What they would actually do')
                ->simple(Forms\Components\TextInput::make('item')->required()->maxLength(300))
                ->helperText('One per bullet. Four is the length these read best at.')
                ->defaultItems(4)
                ->columnSpanFull(),

            Forms\Components\Repeater::make('looking')
                ->label('What we are hoping to find')
                ->simple(Forms\Components\TextInput::make('item')->required()->maxLength(300))
                ->helperText('Hopes, not hard requirements. A wall of must-haves is what stops good people applying.')
                ->defaultItems(4)
                ->columnSpanFull(),

            Forms\Components\Toggle::make('is_published')
                ->label('Published')
                ->helperText('Untick to close the role. It leaves the site immediately and its applications stay attached to it.')
                ->default(true),

            Forms\Components\TextInput::make('position')
                ->helperText('Lowest first.')
                ->numeric()
                ->default(0),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->wrap(),
                Tables\Columns\TextColumn::make('team')->searchable(),
                Tables\Columns\TextColumn::make('location')->searchable(),
                Tables\Columns\TextColumn::make('commitment'),
                Tables\Columns\TextColumn::make('openings')->label('Seats'),
                Tables\Columns\TextColumn::make('applications_count')
                    ->label('Applications')
                    ->counts('applications'),
                Tables\Columns\IconColumn::make('is_published')->label('Live')->boolean(),
                Tables\Columns\TextColumn::make('date_posted')->date('j M Y')->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')->label('Published'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                /* No delete action. The foreign key refuses it once anybody has applied, and a
                   button that fails for the roles that matter most is worse than no button. */
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJobRoles::route('/'),
            'create' => Pages\CreateJobRole::route('/create'),
            'edit' => Pages\EditJobRole::route('/{record}/edit'),
        ];
    }
}
