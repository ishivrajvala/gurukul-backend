<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\LeadResource\Pages;

use App\Filament\Admin\Resources\LeadResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    /**
     * Moving a lead on stamps WHO and WHEN, without asking anybody to remember to.
     *
     * The two fields exist so that "who spoke to this family, and when" is answerable months later.
     * Leaving them to be filled in by hand means they are filled in never, and a `handled_by` that
     * is right a third of the time is worse than one that is empty — it reads as fact.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['status'] ?? null) !== $this->record->getOriginal('status')) {
            $data['handled_at'] = now();
            $data['handled_by'] = auth()->id();
        }

        return $data;
    }
}
