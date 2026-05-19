<?php

namespace App\Http\Resources\Client;

use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

/* @mixin Sale */

class ClientPurchasedServiceHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $service = $this->salable;
        $visits = collect($service->visits)->map(function ($visit) use ($service) {
            return [
                'id' => $visit->id,
                'name' => $service->service->name,
                'user' => $visit->user->name,
                'trainer' => $visit->trainer,
                'date' => format_datetime($visit->created_at),
                'is_penalty' => false,
                'description' => '',
                'club' => $visit->session->club,
                'created_at' => $visit->created_at
            ];
        });

        $penalties = collect($service->penalties)->map(function ($penalty) use ($service) {
            return [
                'id' => $penalty->id,
                'name' => $service->service->name,
                'user' => $penalty->user->name,
                'trainer' => $penalty->trainer,
                'date' => sprintf(
                    "%s %s",
                    format_date($penalty->penalty_date),
                    Carbon::parse($penalty->created_at)->format('H:i:s')
                ),
                'is_penalty' => true,
                'description' => $penalty->description,
                'created_at' => $penalty->date,
                'club' => $penalty->club,
            ];
        });

        $combinedVisits = $visits
            ->mergeRecursive($penalties)
            ->sortByDesc('created_at')
            ->values()
            ->all();

        $restores = $service->acceptedRestores->map(function ($restore) use ($service) {
            return [
                'id' => $restore->id,
                'name' => $service->service->name,
                'user' => $restore->user,
                'date' => format_datetime($restore->created_at),
                'restore_until' => format_date($restore->restore_until),
                'restore_price' => $restore->restore_price,
            ];
        });

        return [
            'id' => $service->id,
            'name' => $service->service->name,
            'purchase_date' => format_datetime($this->created_at),
            'activated_at' => format_datetime($service->activated_at),
            'visits' => $combinedVisits,
            'restores' => $restores,
            'price' => $this->transaction->amount * -1,
            'user' => $this->user,
            'sid' => $this->id,
        ];
    }
}
