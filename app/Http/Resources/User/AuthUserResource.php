<?php

namespace App\Http\Resources\User;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */

class AuthUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'position' => $this->string_role,
            'club' => $this->club,
            'clubs' => $this->clubs,
            'roles' => $this->roles,
            'photo' => $this->getFirstMediaUrl(User::MEDIA_AVATAR),
            'is_boss' => $this->is_boss,
            'is_seller' => $this->is_seller,
            'is_bartender' => $this->is_bartender,
            'can_sale_service' => $this->can_sale_service,
            'can_change_club' => $this->canChangeClub(),
            'must_select_club' => $this->mustSelectAClub(),
            'permissions' => [
                'can_top_up_account' => $this->canTopUpAccount(),
                'can_write_off_services' => $this->canWriteOffServices(),
                'can_see_purchased_services' => $this->canSeePurchasedServices(),
                'can_sale_products' => $this->canSaleProducts(),
                'can_sale_bar' => $this->canSaleBar(),
                'can_open_session' => $this->canOpenSession(),
                'can_create_clients' => $this->canCreateClients(),
            ]
        ];
    }
}
