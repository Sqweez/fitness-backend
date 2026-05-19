<?php

namespace App\Http\Resources\User;

use App\Models\Client;
use App\Models\User;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin User
*/
class SingleUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array {

        $clubs = $this->clubs;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'roles' => $this->roles->pluck('id'),
            'club' => $clubs,
            'position' => $this->string_role,
            'login' => $this->login,
            'photo' => $this->getFirstMediaUrl(User::MEDIA_AVATAR),
            'phone' => mask_phone($this->phone),
            'unmasked_phone' => $this->phone,
            'birth_date' => $this->birth_date_formatted,
            'pass' => $this->pass->code ?? '',
            'description' => $this->description,
            'clubs_id' => $clubs->pluck('id'),
            'is_active' => $this->is_active,
            'mobile_position' => $this->string_role,
            'is_trainer' => $this->is_trainer,
        ];
    }
}
