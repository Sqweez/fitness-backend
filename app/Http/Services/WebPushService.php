<?php

namespace App\Http\Services;

use App\Models\User;
use App\Models\WebPushSubscription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Browser push notifications for staff in the admin panel (separate from mobile client pushes).
 */
class WebPushService
{
    private const TTL_SECONDS = 3600;

    public function subscribe(User $user, string $endpoint, string $publicKey, string $authToken, string $contentEncoding): void {
        // The same browser endpoint moves to whoever is signed in there now
        WebPushSubscription::query()->updateOrCreate(['endpoint_hash' => WebPushSubscription::hashEndpoint($endpoint)], [
            'user_id' => $user->id,
            'endpoint' => $endpoint,
            'public_key' => $publicKey,
            'auth_token' => $authToken,
            'content_encoding' => $contentEncoding,
        ]);
    }

    public function unsubscribe(User $user, string $endpoint): void {
        WebPushSubscription::query()
            ->where('user_id', $user->id)
            ->where('endpoint_hash', WebPushSubscription::hashEndpoint($endpoint))
            ->delete();
    }

    /**
     * @param Collection<int, int> $userIds
     */
    public function sendToUsers(Collection $userIds, array $payload): void {
        $subscriptions = WebPushSubscription::query()->whereIn('user_id', $userIds)->get();
        if ($subscriptions->isEmpty()) {
            return;
        }

        $webPush = new WebPush(['VAPID' => [
            'subject' => config('webpush.subject'),
            'publicKey' => config('webpush.public_key'),
            'privateKey' => config('webpush.private_key'),
        ]], ['TTL' => self::TTL_SECONDS, 'urgency' => 'high']);

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        foreach ($subscriptions as $subscription) {
            $webPush->queueNotification(Subscription::create([
                'endpoint' => $subscription->endpoint,
                'publicKey' => $subscription->public_key,
                'authToken' => $subscription->auth_token,
                'contentEncoding' => $subscription->content_encoding,
            ]), $body);
        }

        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                continue;
            }
            // 404/410: the browser dropped the subscription, it will never work again
            if ($report->isSubscriptionExpired()) {
                WebPushSubscription::query()->where('endpoint_hash', WebPushSubscription::hashEndpoint($report->getEndpoint()))->delete();
                continue;
            }
            // Other failures are per-recipient; logged so the remaining recipients still get the push
            Log::warning('Web push failed', ['endpoint' => $report->getEndpoint(), 'reason' => $report->getReason()]);
        }
    }
}
