<?php

namespace App\Http\Services;

use App\Models\Trainer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

class TrainerService
{
    public function getTrainers(bool $onlyActive): Collection {
        return Trainer::query()->with('media')
            ->when($onlyActive, function (Builder $query) {
                $query->where('is_active', true);
            })
            ->orderBy('sort_order')->orderBy('id')->get();
    }

    public function getTrainer(Trainer $trainer): Trainer {
        return $trainer->load('media');
    }

    public function createTrainer(array $data): Trainer {
        return DB::transaction(function () use ($data) {
            $trainer = Trainer::create(Arr::except($data, ['photo']));
            $this->savePhoto($trainer, $data);
            return $trainer->refresh()->load('media');
        });
    }

    public function updateTrainer(Trainer $trainer, array $data): Trainer {
        return DB::transaction(function () use ($trainer, $data) {
            $trainer->update(Arr::except($data, ['photo']));
            $this->savePhoto($trainer, $data);
            return $trainer->refresh()->load('media');
        });
    }

    public function deleteTrainer(Trainer $trainer): void {
        $trainer->delete();
    }

    private function savePhoto(Trainer $trainer, array $data): void {
        if (!array_key_exists('photo', $data)) {
            return;
        }
        if ($data['photo'] === null) {
            $trainer->clearMediaCollection(Trainer::MEDIA_PHOTO);
            return;
        }

        $existingIds = $trainer->getMedia(Trainer::MEDIA_PHOTO)->pluck('id')->all();
        try {
            $trainer->addMedia($data['photo'])->toMediaCollection(Trainer::MEDIA_PHOTO);
        } catch (Throwable $exception) {
            $trainer->unsetRelation('media');
            foreach ($trainer->getMedia(Trainer::MEDIA_PHOTO)->whereNotIn('id', $existingIds) as $media) {
                $media->delete();
            }
            throw $exception;
        }
    }
}
