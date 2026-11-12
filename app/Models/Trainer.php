<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Trainer extends Model implements HasMedia
{
    use InteractsWithMedia;

    const MEDIA_PHOTO = 'trainer_photo';

    protected $guarded = ['id'];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function registerMediaCollections(): void {
        $this->addMediaCollection(self::MEDIA_PHOTO)->singleFile();
    }

    public function registerMediaConversions(Media $media = null): void {
        $this->addMediaConversion('mobile')
            ->performOnCollections(self::MEDIA_PHOTO)
            ->fit(Fit::Crop, 600, 600)
            ->nonQueued();
    }

    public function getPhotoUrlAttribute(): ?string {
        $photo = $this->getFirstMedia(self::MEDIA_PHOTO);
        return $photo ? $photo->getUrl('mobile') : null;
    }
}
