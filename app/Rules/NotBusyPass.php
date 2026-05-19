<?php

namespace App\Rules;

use App\Models\Pass;
use App\Models\User;
use Illuminate\Contracts\Validation\Rule;
use function PHPUnit\Framework\at;

class NotBusyPass implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */

    private $id;
    private $instance;

    public function __construct($id = null, $instance = User::class)
    {
        $this->id = $id;
        $this->instance = $instance;
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
        $pass = Pass::whereCode($value)->first();
        if (!isset($pass) || !isset($pass->passable)) {
            return true;
        }
        if ($pass->passable_id === $this->id && $pass->passable_type === $this->instance) {
            return true;
        }
        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Данная карта уже используется';
    }
}
