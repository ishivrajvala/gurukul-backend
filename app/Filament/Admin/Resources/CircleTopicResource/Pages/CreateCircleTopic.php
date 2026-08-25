<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CircleTopicResource\Pages;

use App\Filament\Admin\Resources\CircleTopicResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCircleTopic extends CreateRecord
{
    protected static string $resource = CircleTopicResource::class;
}
