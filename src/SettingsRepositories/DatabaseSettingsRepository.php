<?php

namespace Spatie\LaravelSettings\SettingsRepositories;

use DB;
use Spatie\LaravelSettings\Models\SettingsProperty;

class DatabaseSettingsRepository implements SettingsRepository
{
    /** @var \Spatie\LaravelSettings\Models\SettingsProperty|string */
    private string $propertyModel;

    public function __construct(array $config)
    {
        $this->propertyModel = $config['model'] ?? SettingsProperty::class;
    }

    public function getPropertiesInGroup(string $group): array
    {
        /** @var \Spatie\LaravelSettings\Models\SettingsProperty $temp */
        $temp = new $this->propertyModel;

        return DB::connection($temp->getConnectionName())
            ->table($temp->getTable())
            ->where('group', $group)
            ->get(['name', 'payload'])
            ->mapWithKeys(function ($object) {
                return [$object->name => json_decode($object->payload, true)];
            })
            ->toArray();
    }

    public function updateOrCreatePropertiesInGroup(string $group, array $properties): void
    {
        foreach ($properties as $name => $value) {
            $this->propertyModel::updateOrCreate([
                'group' => $group,
                'name' => $name,
                'locked' => false,
            ], [
                'payload' => json_encode($value),
            ]);
        }
    }

    public function checkIfPropertyExists(string $group, string $name): bool
    {
        return $this->propertyModel::query()
            ->where('group', $group)
            ->where('name', $name)
            ->exists();
    }

    public function getPropertyPayload(string $group, string $name)
    {
        $setting = $this->propertyModel::query()
            ->where('group', $group)
            ->where('name', $name)
            ->first('payload')
            ->toArray();

        return json_decode($setting['payload']);
    }

    public function createProperty(string $group, string $name, $payload): void
    {
        $this->propertyModel::create([
            'group' => $group,
            'name' => $name,
            'payload' => json_encode($payload),
            'locked' => false,
        ]);
    }

    public function updatePropertyPayload(string $group, string $name, $value): void
    {
        $this->propertyModel::query()
            ->where('group', $group)
            ->where('name', $name)
            ->update([
                'payload' => json_encode($value),
            ]);
    }

    public function deleteProperty(string $group, string $name): void
    {
        $this->propertyModel::query()
            ->where('group', $group)
            ->where('name', $name)
            ->delete();
    }

    public function lockProperties(string $group, array $properties)
    {
        $this->propertyModel::query()
            ->where('group', $group)
            ->whereIn('name', $properties)
            ->update(['locked' => true]);
    }

    public function unlockProperties(string $group, array $properties)
    {
        $this->propertyModel::query()
            ->where('group', $group)
            ->whereIn('name', $properties)
            ->update(['locked' => false]);
    }

    public function getLockedProperties(string $group): array
    {
        return $this->propertyModel::query()
            ->where('group', $group)
            ->where('locked', true)
            ->pluck('name')
            ->toArray();
    }
}