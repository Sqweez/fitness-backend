<?php

namespace App\Rules;

use App\Models\Client;
use Illuminate\Contracts\Validation\Rule;

class IsEnoughFunds implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */

    private $client;

    public function __construct($clientId)
    {
        $this->client = Client::find($clientId);
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return ($this->client->balance - $value) >= 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Недостаточно средств!';
    }
}
