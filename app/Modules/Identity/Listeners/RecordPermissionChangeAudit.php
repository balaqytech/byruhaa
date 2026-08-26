<?php

namespace App\Modules\Identity\Listeners;

use App\Modules\Identity\Contracts\AuditsIdentityRelations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Permission\Events\PermissionAttachedEvent;
use Spatie\Permission\Events\PermissionDetachedEvent;
use Spatie\Permission\Events\RoleAttachedEvent;
use Spatie\Permission\Events\RoleDetachedEvent;

final class RecordPermissionChangeAudit
{
    public function handle(
        PermissionAttachedEvent|PermissionDetachedEvent|RoleAttachedEvent|RoleDetachedEvent $event,
    ): void {
        $model = $event->model;

        if (! $model instanceof AuditsIdentityRelations) {
            return;
        }

        [$relation, $attached, $values] = match (true) {
            $event instanceof RoleAttachedEvent => ['roles', true, $event->rolesOrIds],
            $event instanceof RoleDetachedEvent => ['roles', false, $event->rolesOrIds],
            $event instanceof PermissionAttachedEvent => ['permissions', true, $event->permissionsOrIds],
            $event instanceof PermissionDetachedEvent => ['permissions', false, $event->permissionsOrIds],
        };

        $values = $this->normalizeValues($values);

        if ($values === []) {
            return;
        }

        $model->recordCustomAudit(
            $attached ? 'attached' : 'detached',
            $attached ? [] : [$relation => $values],
            $attached ? [$relation => $values] : [],
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizeValues(mixed $values): array
    {
        $items = match (true) {
            $values instanceof Collection => $values->all(),
            $values instanceof Model => [$values],
            default => (array) $values,
        };

        return collect($items)
            ->map(static function (mixed $value): array {
                if ($value instanceof Model) {
                    return [
                        'id' => $value->getKey(),
                        'name' => $value->getAttribute('name'),
                    ];
                }

                return ['id' => $value];
            })
            ->values()
            ->all();
    }
}
