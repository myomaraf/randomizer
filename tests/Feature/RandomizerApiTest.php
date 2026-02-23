<?php

namespace Tests\Feature;

use Tests\TestCase;

class RandomizerApiTest extends TestCase
{
    private const ALLOWED_ORIGIN = 'https://myomaraf.com';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('omaraf.allowed_origins', [
            'https://myomaraf.com',
            'https://www.myomaraf.com',
        ]);
        config()->set('omaraf.max_uuids', 20000);
        config()->set('omaraf.rate_limit_per_minute', 60);
    }

    public function test_success_case_returns_a_selected_uuid_and_audit_meta(): void
    {
        $uuids = [
            '550e8400-e29b-41d4-a716-446655440000',
            '550e8400-e29b-41d4-a716-446655440001',
            '550e8400-e29b-41d4-a716-446655440002',
        ];

        $response = $this
            ->withHeaders(['Origin' => self::ALLOWED_ORIGIN])
            ->postJson('/api/randomize', ['uuids' => $uuids]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'selected_uuid',
                'meta' => [
                    'count',
                    'algorithm',
                    'audit' => [
                        'uuids_sha256',
                        'count',
                        'digest_sha256',
                        'index',
                        'timestamp_utc',
                        'timestamp_bucket_utc',
                        'server_nonce_sha256',
                        'server_nonce_hex',
                        'algorithm_version',
                    ],
                ],
            ]);

        $selected = $response->json('selected_uuid');
        $canonical = array_map(static fn (string $uuid) => strtolower(trim($uuid)), $uuids);

        $this->assertContains($selected, $canonical);
        $this->assertSame(3, $response->json('meta.count'));
    }

    public function test_invalid_uuid_returns_422(): void
    {
        $response = $this
            ->withHeaders(['Origin' => self::ALLOWED_ORIGIN])
            ->postJson('/api/randomize', [
                'uuids' => [
                    '550e8400-e29b-41d4-a716-446655440000',
                    'not-a-uuid',
                ],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['uuids.1']);
    }

    public function test_missing_uuids_returns_422(): void
    {
        $response = $this
            ->withHeaders(['Origin' => self::ALLOWED_ORIGIN])
            ->postJson('/api/randomize', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['uuids']);
    }

    public function test_origin_blocked_returns_403(): void
    {
        $response = $this
            ->withHeaders(['Origin' => 'https://evil.example'])
            ->postJson('/api/randomize', [
                'uuids' => ['550e8400-e29b-41d4-a716-446655440000'],
            ]);

        $response->assertForbidden();
        $response->assertJsonPath('message', 'Request origin is not allowed.');
    }

    public function test_large_array_within_limit_succeeds(): void
    {
        config()->set('omaraf.max_uuids', 6000);

        $uuids = [];

        for ($i = 0; $i < 5000; $i++) {
            $suffix = str_pad(dechex($i), 12, '0', STR_PAD_LEFT);
            $uuids[] = "00000000-0000-4000-8000-{$suffix}";
        }

        $response = $this
            ->withHeaders(['Origin' => self::ALLOWED_ORIGIN])
            ->postJson('/api/randomize', ['uuids' => $uuids]);

        $response->assertOk();
        $response->assertJsonPath('meta.count', 5000);
    }
}
