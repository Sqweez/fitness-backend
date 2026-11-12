<?php

use App\Models\Client;
use App\Models\Club;
use App\Models\Session;
use App\Models\User;
use App\Http\Services\TrainerService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (DB::connection()->getDatabaseName() !== 'fitness_docker_preflight') {
    throw new RuntimeException('This script only runs against fitness_docker_preflight.');
}

function requestJson(string $method, string $path, array $body = [], ?string $token = null): array {
    $headers = "Accept: application/json\r\nContent-Type: application/json\r\n";
    if ($token !== null) {
        $headers .= "Authorization: Bearer {$token}\r\n";
    }
    $context = stream_context_create(['http' => [
        'method' => $method,
        'header' => $headers,
        'content' => $body ? json_encode($body) : '',
        'ignore_errors' => true,
        'timeout' => 10,
    ]]);
    $response = file_get_contents('http://nginx:8080' . $path, false, $context);
    if (strpos($http_response_header[0], '200') === false) {
        throw new RuntimeException($path . ': ' . $http_response_header[0]);
    }
    return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
}

$statePath = storage_path('app/docker-smoke.json');
if (($argv[1] ?? '') === 'create') {
    $context = stream_context_create(['http' => ['header' => "Host: docker.example.test\r\nX-Forwarded-Proto: https\r\n"]]);
    $docs = file_get_contents('http://nginx:8080/api/mobile/v1/docs', false, $context);
    if (strpos($docs, 'https://docker.example.test/api/mobile/v1/docs/openapi.yaml') === false) {
        throw new RuntimeException('Forwarded HTTPS verification failed.');
    }
    $limited = false;
    for ($i = 0; $i < 65; $i++) {
        $context = stream_context_create(['http' => ['header' => "X-Forwarded-For: 198.51.100.17\r\n", 'ignore_errors' => true]]);
        file_get_contents('http://nginx:8080/api/mobile/v1/docs/openapi.yaml', false, $context);
        $limited = $limited || strpos($http_response_header[0], '429') !== false;
    }
    $context = stream_context_create(['http' => ['header' => "X-Forwarded-For: 198.51.100.18\r\n", 'ignore_errors' => true]]);
    file_get_contents('http://nginx:8080/api/mobile/v1/docs/openapi.yaml', false, $context);
    if (!$limited || strpos($http_response_header[0], '200') === false) {
        throw new RuntimeException('Separate client IP rate limits verification failed.');
    }

    $club = Club::create(['name' => 'Docker test club']);
    $user = User::create(['name' => 'Docker test operator']);
    $phone = '+7701' . random_int(1000000, 9999999);
    $client = Client::create([
        'name' => 'Docker Проверка', 'phone' => $phone, 'gender' => 'M',
        'birth_date' => '1990-01-01', 'club_id' => $club->id,
    ]);
    Session::create([
        'client_id' => $client->id, 'start_user_id' => $user->id, 'club_id' => $club->id,
        'created_at' => '2026-11-12 18:20:00',
    ]);

    requestJson('POST', '/api/mobile/v1/auth/code', ['phone' => $phone]);
    $auth = requestJson('POST', '/api/mobile/v1/auth/verify', ['phone' => $phone, 'code' => '111111']);

    $imagePath = storage_path('app/docker-smoke.jpg');
    $image = imagecreatetruecolor(20, 20);
    imagejpeg($image, $imagePath);
    imagedestroy($image);
    $trainer = app(TrainerService::class)->createTrainer([
        'first_name' => 'Docker', 'last_name' => 'Test', 'specialization' => 'Test',
        'photo' => new UploadedFile($imagePath, 'docker-smoke.jpg', 'image/jpeg', null, true),
    ]);
    file_put_contents($statePath, json_encode(['token' => $auth['token'], 'trainer_id' => (string) $trainer->id]));
    chmod($statePath, 0600);
}

$state = json_decode(file_get_contents($statePath), true, 512, JSON_THROW_ON_ERROR);
$profile = requestJson('GET', '/api/mobile/v1/me', [], $state['token']);
if ($profile['data']['last_name'] !== 'Docker') {
    throw new RuntimeException('Profile verification failed.');
}
$visits = requestJson('GET', '/api/mobile/v1/me/visits?limit=1', [], $state['token']);
if ($visits['data']['items'][0]['at'] !== '2026-11-12T18:20:00+05:00') {
    throw new RuntimeException('Visit timezone verification failed.');
}
requestJson('GET', '/api/mobile/v1/me/membership', [], $state['token']);
requestJson('GET', '/api/mobile/v1/me/membership/history', [], $state['token']);
requestJson('GET', '/api/mobile/v1/me/trinket', [], $state['token']);
$trainers = requestJson('GET', '/api/mobile/v1/trainers', [], $state['token']);
$trainer = array_values(array_filter($trainers['data'], function (array $item) use ($state) {
    return $item['id'] === $state['trainer_id'];
}))[0];
$photo = file_get_contents('http://nginx:8080' . parse_url($trainer['photo_url'], PHP_URL_PATH));
$dimensions = getimagesizefromstring($photo);
if ($dimensions[0] !== 600 || $dimensions[1] !== 600) {
    throw new RuntimeException('Public photo conversion verification failed.');
}
echo "HTTP authentication, profile, visits, memberships, trinket, photo and UTC+5: OK\n";
