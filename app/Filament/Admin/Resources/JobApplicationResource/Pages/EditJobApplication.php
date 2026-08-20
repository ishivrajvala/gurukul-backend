<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\JobApplicationResource\Pages;

use App\Filament\Admin\Resources\JobApplicationResource;
use Filament\Actions;
use Illuminate\Support\Facades\Storage;
use Filament\Resources\Pages\EditRecord;

class EditJobApplication extends EditRecord
{
    protected static string $resource = JobApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            /* Streamed through the panel rather than linked: the file is on the private disk, so
               the session is the authorisation. */
            Actions\Action::make('cv')
                ->label('Download CV')
                ->icon('heroicon-m-arrow-down-tray')
                ->visible(fn (): bool => filled($this->record->cv_path))
                ->action(fn () => Storage::disk('local')->download(
                    $this->record->cv_path,
                    JobApplicationResource::cvFilename($this->record),
                )),
        ];
    }
}
