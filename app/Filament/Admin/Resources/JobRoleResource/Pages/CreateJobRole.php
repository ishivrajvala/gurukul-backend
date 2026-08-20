<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\JobRoleResource\Pages;

use App\Filament\Admin\Resources\JobRoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJobRole extends CreateRecord
{
    protected static string $resource = JobRoleResource::class;
}
