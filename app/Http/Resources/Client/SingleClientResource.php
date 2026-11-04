<?php

namespace App\Http\Resources\Client;

use App\Models\Client;
use App\Models\ClientBookmark;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
* @mixin Client
 */

class SingleClientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'gender' => $this->gender,
            'gender_display' => $this->gender === 'F' ? 'Женский' : 'Мужской',
            'club' => $this->club,
            'pass' => $this->pass,
            'registrar' => $this->registrar->name,
            'phone' => mask_phone($this->phone),
            'unmasked_phone' => $this->phone,
            'balance' => $this->balance,
            'photo' => $this->getFirstMediaUrl(Client::MEDIA_AVATAR),
            'birth_date_formatted' => $this->birth_date_formatted,
            'age' => $this->age,
            'age_full' => $this->ageSuffix($this->age),
            'age_type' => $this->age_type,
            'birth_date' => $this->birth_date,
            'is_birthday_today' => $this->is_birthday,
            'description' => $this->description,
            'programs' => ClientPurchasedServices::collection($this->lastPrograms),
            'trinket' => $this->trinket->code ?? null,
            'cabinet_number' => $this->trinket->cabinet_number ?? null,
            'active_session' => $this->active_session,
            'is_in_club' => $this->is_in_club,
            'trinket_can_given' => $this->trinket_can_given,
            'session_can_be_finished' => $this->session_can_be_finished,
            'has_unlimited_discount' => $this->has_unlimited_discount,
            'total_solarium' => $this->cached_solarium_total, //$this->activeSolariumMinutes->sum('salable.remaining_minutes'),
            'in_bookmark' => ClientBookmark::query()
                ->where('client_id', $this->id)
                ->where('user_id', auth()->id())
                ->exists(),
            'mobile_programs_with_details' => $this->getProgramsWithDetails(ClientPurchasedServices::collection($this->lastPrograms)->toArray($request)),
        ];
    }

    public function ageSuffix($age) {
        $lastDigit = $age % 10;
        $lastTwoDigits = $age % 100;

        if ($lastTwoDigits >= 11 && $lastTwoDigits <= 14) {
            return $age . ' лет';
        } elseif ($lastDigit === 1) {
            return $age . ' год';
        } elseif ($lastDigit >= 2 && $lastDigit <= 4) {
            return $age . ' года';
        } else {
            return $age . ' лет';
        }
    }

    public function getProgramsWithDetails($programs): Collection {
        return collect($programs)
            ->map(function ($program) {
                $details = [
                    sprintf('Тренер: %s', $program['last_trainer']['name']),
                    sprintf('Действительно до: %s', $program['active_until']),
                    sprintf('Сделано посещений: %s', $program['visits_count']),
                ];

                if ($program['type']['id'] === __hardcoded(3)) {
                    $details[] = sprintf('Осталось посещений: %s', $program['remaining_visits']);
                }

                return [
                    'id' => $program['id'],
                    'title' => $program['name'],
                    'details' => $details,
                ];
            });
    }
}
