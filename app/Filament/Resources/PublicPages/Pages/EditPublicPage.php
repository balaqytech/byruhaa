<?php

namespace App\Filament\Resources\PublicPages\Pages;

use App\Filament\Resources\PublicPages\PublicPageResource;
use App\Modules\Content\Enums\PublicPageStatus;
use Filament\Resources\Pages\EditRecord;

class EditPublicPage extends EditRecord
{
    protected static string $resource = PublicPageResource::class;

    /** @param array<string, mixed> $data */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['status'] ?? null) === PublicPageStatus::Published->value && blank($data['published_at'] ?? null)) {
            $data['published_at'] = now();
        }

        if (($data['status'] ?? null) !== PublicPageStatus::Published->value) {
            $data['published_at'] = null;
        }

        return $data;
    }
}
