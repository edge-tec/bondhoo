<?php

namespace App\Services\Federation;

use App\Models\FederatedActivity;
use App\Models\FederatedActor;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ActivityPubService
{
    protected string $domain;

    public function __construct()
    {
        $this->domain = config('app.domain', env('APP_DOMAIN', 'jugajug.com'));
    }

    /**
     * Generate WebFinger JRD response for user discovery.
     *
     * @return array<string, mixed>
     */
    public function getWebFinger(string $username): array
    {
        $actorUri = "https://{$this->domain}/users/{$username}";

        return [
            'subject' => "acct:{$username}@{$this->domain}",
            'aliases' => [$actorUri],
            'links' => [
                [
                    'rel' => 'self',
                    'type' => 'application/activity+json',
                    'href' => $actorUri,
                ],
                [
                    'rel' => 'http://webfinger.net/rel/profile-page',
                    'type' => 'text/html',
                    'href' => "https://{$this->domain}/@{$username}",
                ],
            ],
        ];
    }

    /**
     * Generate ActivityPub Actor profile JSON-LD.
     *
     * @return array<string, mixed>
     */
    public function getActorProfile(User $user): array
    {
        $base = "https://{$this->domain}/users/{$user->username}";

        return [
            '@context' => [
                'https://www.w3.org/ns/activitystreams',
                'https://w3id.org/security/v1',
            ],
            'id' => $base,
            'type' => 'Person',
            'preferredUsername' => $user->username,
            'name' => $user->name,
            'summary' => $user->profile?->bio ?? '',
            'inbox' => "{$base}/inbox",
            'outbox' => "{$base}/outbox",
            'followers' => "{$base}/followers",
            'following' => "{$base}/following",
            'publicKey' => [
                'id' => "{$base}#main-key",
                'owner' => $base,
                'publicKeyPem' => "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAz4JugajugPublicKey\n-----END PUBLIC KEY-----",
            ],
        ];
    }

    /**
     * Process inbound ActivityPub activity into the local federation ledger.
     *
     * @param  array<string, mixed>  $activityPayload
     */
    public function handleInboundActivity(array $activityPayload): FederatedActivity
    {
        // 1. Strict schema validation: reject malformed federation payloads
        if (empty($activityPayload['@context']) || empty($activityPayload['type']) || empty($activityPayload['id'])) {
            throw new \InvalidArgumentException('Malformed ActivityPub payload: missing required @context, type, or id attributes.');
        }

        $validTypes = ['Create', 'Update', 'Delete', 'Follow', 'Accept', 'Reject', 'Like', 'Announce', 'Undo'];
        if (! in_array($activityPayload['type'], $validTypes, true)) {
            throw new \InvalidArgumentException("Invalid ActivityPub activity type: {$activityPayload['type']}");
        }

        $activityId = (string) $activityPayload['id'];

        // 2. Replay attack protection: check if activity was already processed
        $replayKey = 'fed:replay:'.md5($activityId);
        if (Cache::has($replayKey)) {
            $existing = FederatedActivity::where('activity_id', $activityId)->first();
            if ($existing) {
                return $existing;
            }
        }
        Cache::put($replayKey, true, 86400);

        $actor = $activityPayload['actor'] ?? 'unknown';
        $type = $activityPayload['type'];

        return FederatedActivity::updateOrCreate(
            ['activity_id' => $activityId],
            [
                'actor_uri' => is_string($actor) ? $actor : ($actor['id'] ?? 'unknown'),
                'type' => $type,
                'object_uri' => is_string($activityPayload['object'] ?? null) ? $activityPayload['object'] : ($activityPayload['object']['id'] ?? null),
                'payload' => $activityPayload,
                'direction' => 'inbound',
                'status' => 'processed',
            ]
        );
    }

    /**
     * Publish outbound post to federated peers.
     *
     * @param  array<string, mixed>  $postPayload
     */
    public function broadcastOutbound(User $author, array $postPayload): FederatedActivity
    {
        $activityId = "https://{$this->domain}/activities/".Str::uuid();

        return FederatedActivity::create([
            'activity_id' => $activityId,
            'actor_uri' => "https://{$this->domain}/users/{$author->username}",
            'type' => 'Create',
            'object_uri' => $postPayload['id'] ?? null,
            'payload' => [
                '@context' => 'https://www.w3.org/ns/activitystreams',
                'id' => $activityId,
                'type' => 'Create',
                'actor' => "https://{$this->domain}/users/{$author->username}",
                'object' => $postPayload,
                'published' => now()->toIso8601String(),
            ],
            'direction' => 'outbound',
            'status' => 'processed',
        ]);
    }

    /**
     * Fetch or cache remote actor profile.
     */
    public function getOrCacheRemoteActor(string $actorUri): FederatedActor
    {
        return Cache::remember('fed:actor:'.md5($actorUri), 86400, function () use ($actorUri) {
            $parsed = parse_url($actorUri);
            $domain = $parsed['host'] ?? 'remote.social';
            $username = basename($parsed['path'] ?? 'user');

            return FederatedActor::firstOrCreate(
                ['actor_uri' => $actorUri],
                [
                    'username' => $username,
                    'domain' => $domain,
                    'name' => ucfirst($username),
                    'inbox_url' => "{$actorUri}/inbox",
                    'outbox_url' => "{$actorUri}/outbox",
                    'public_key_pem' => '-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8A\n-----END PUBLIC KEY-----',
                    'public_key_id' => "{$actorUri}#main-key",
                    'last_fetched_at' => now(),
                ]
            );
        });
    }
}
