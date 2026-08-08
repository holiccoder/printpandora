<?php

namespace App\Filament\Resources\MediaResource\Pages;

use App\Filament\Resources\MediaResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;

class CreateMedia extends CreateRecord
{
    protected static string $resource = MediaResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $modelClass = $data['model_type'];
        $modelId = $data['model_id'];

        /** @var HasMedia $model */
        $model = $modelClass::findOrFail($modelId);

        // Get absolute file path from public disk
        $filePath = Storage::disk('public')->path($data['file']);

        // Use Spatie Media Library adder to add the file and create the record
        $media = $model->addMedia($filePath)
            ->usingName($data['name'] ?? pathinfo($filePath, PATHINFO_FILENAME))
            ->withCustomProperties($data['custom_properties'] ?? [])
            ->toMediaCollection($data['collection_name']);

        if (isset($data['order_column'])) {
            $media->order_column = (int) $data['order_column'];
            $media->save();
        }

        return $media;
    }
}
