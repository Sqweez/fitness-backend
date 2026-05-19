<?php

namespace App\Console\Commands\Imports;

use App\Http\Services\PassService;
use App\Models\User;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileCannotBeAdded;

class ImportUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Импортируем пользователей из старой системы';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     * @throws FileCannotBeAdded
     */
    public function handle(): int {
        $users = $this->getUsersList();
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        \DB::table('users')->truncate();
        \DB::table('role_user')->truncate();
        \DB::table('passes')->truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        collect($users)->each(function ($user) {
            $this->line($user->name);

            try {
                $birth_date = $user->birthday !== '0000-00-00' ? Carbon::parse($user->birthday): now();
            } catch (InvalidFormatException $exception) {
                \Log::error($exception->getMessage());
                $birth_date = now();
            }

            $_user = User::create([
                'id' => $user->id,
                'name' => $user->name,
                'description' => $user->opisanie,
                'birth_date' => $birth_date,
                'club_id' => str_replace('club', '', $user->clubid),
                'phone' => '+7' . unmask_phone($user->phone),
                // 123456
                'password' => null,
                'is_active' => $user->status == 0
            ]);

            if ($user->cardid && $user->status == 0) {
                $pass = PassService::createPass($user->cardid);
                $_user->pass()->save($pass);
            }

            $roles = [];
            if ($user->prava == '5') {
                $roles[] = 1;
            }
            if ($user->prava == '1') {
                $roles[] = 2;
            }
            if ($user->prava == '2') {
                $roles[] = 6;
            }
            if ($user->prava == '7' || $user->prava == '10') {
                $roles[] = 4;
            }
            if ($user->prava == '9') {
                $roles[] = 5;
            }
            if ($user->prava == '3') {
                $roles[] = 3;
            }

            $_user->roles()->sync($roles);

            if ($user->photo && $user->status == 0) {
                $this->line($user->photo);
                $_user
                    ->addMediaFromUrl(sprintf("http://top-star.kz/photos/%s", $user->photo))
                    ->toMediaCollection(\App\Models\User::MEDIA_AVATAR);;
            }

        });

        return 1;
    }

    private function getUsersList() {
        $client = new Client();
        $response = $client->get('http://top-star.kz/export/export_users.php');
        return json_decode($response->getBody());
    }
}
