<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CampaignResource\Pages;

use App\Filament\Admin\Resources\CampaignResource;
use App\Models\Campaign;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class EditCampaign extends EditRecord
{
    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            /* Preview sits here as well as on the row: this is where you are when you finish writing. */
            Actions\Action::make('preview')
                ->label('Preview')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->modalHeading(fn (): string => $this->record->subject ?: 'Preview')
                ->modalDescription('Exactly what arrives — the same template the send uses.')
                ->modalWidth(MaxWidth::TwoExtraLarge)
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->modalContent(fn (): Htmlable => new HtmlString(
                    view('filament.admin.campaign-preview', ['html' => $this->record->renderHtml()])->render(),
                )),

            Actions\DeleteAction::make()
                ->visible(fn (): bool => $this->record->status !== Campaign::STATUS_SENT),
        ];
    }

    /**
     * A SENT CAMPAIGN IS NOT EDITABLE, and this is the last line of that.
     *
     * The form hides the fields and the table hides the action, but a stale browser tab left open
     * before the send can still post to this page. Once the words are in somebody's inbox they are
     * not ours to change, and a record that no longer matches what was delivered is worse than no
     * record at all.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! $this->record->isEditable()) {
            abort(403, 'This campaign has already been sent.');
        }

        return $data;
    }
}
