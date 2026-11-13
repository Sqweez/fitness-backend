<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ClubProfile extends Model implements HasMedia
{
    use InteractsWithMedia;

    const MEDIA_COVER = 'club_cover';

    protected $guarded = ['id'];

    protected $casts = ['schedule' => 'array'];

    public function club(): BelongsTo {
        return $this->belongsTo(Club::class);
    }

    public function registerMediaCollections(): void {
        $this->addMediaCollection(self::MEDIA_COVER)->singleFile();
    }

    public function getCoverUrlAttribute(): ?string {
        $cover = $this->getFirstMedia(self::MEDIA_COVER);
        return $cover ? $cover->getUrl() : null;
    }
}
