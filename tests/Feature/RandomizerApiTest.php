<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Raffle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RandomizerApiTest extends TestCase
{
    use RefreshDatabase;

    private const RAW_API_KEY = 'test-secret-api-key';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('omaraf.max_uuids', 20000);
        config()->set('omaraf.rate_limit', 60);
        config()->set('omaraf.algorithm_version', '1');

        ApiKey::query()->create([
            'name' => 'Testing',
            'key_hash' => hash('sha256', self::RAW_API_KEY),
            'is_active' => true,
        ]);
    }

    public function test_randomize_without_api_key_returns_401(): void
    {
        $response = $this->postJson('/api/randomize', $this->payload('raffle-missing-key'));

        $response->assertStatus(401);
        $response->assertJsonPath('message', 'API key missing');
    }

    public function test_randomize_with_invalid_api_key_returns_401(): void
    {
        $response = $this->withHeaders([
            'X-API-KEY' => 'invalid',
        ])->postJson('/api/randomize', $this->payload('raffle-invalid-key'));

        $response->assertStatus(401);
        $response->assertJsonPath('message', 'Invalid API key');
    }

    public function test_randomize_with_valid_api_key_returns_200_and_persists_data(): void
    {
        $response = $this
            ->withHeaders(['X-API-KEY' => self::RAW_API_KEY])
            ->postJson('/api/randomize', $this->payload('raffle-success'));

        $response
            ->assertOk()
            ->assertJsonStructure([
                'raffle_id',
                'selected_uuid',
                'meta' => [
                    'count',
                    'algorithm',
                    'audit' => [
                        'raffle_id',
                        'uuids_sha256',
                        'nonce_hex',
                        'digest_sha256',
                        'index_selected',
                        'count',
                        'timestamp_utc',
                        'algorithm_version',
                    ],
                ],
            ]);

        $this->assertSame('raffle-success', $response->json('raffle_id'));
        $this->assertSame(3, $response->json('meta.count'));

        $this->assertDatabaseHas('raffles', [
            'raffle_id' => 'raffle-success',
            'selected_uuid' => $response->json('selected_uuid'),
        ]);

        $raffle = Raffle::query()->where('raffle_id', 'raffle-success')->firstOrFail();
        $this->assertDatabaseCount('raffle_tickets', 3);
        $this->assertDatabaseHas('raffle_tickets', [
            'raffle_id' => $raffle->id,
            'position' => 0,
        ]);
    }

    public function test_duplicate_raffle_id_returns_409(): void
    {
        $response = $this
            ->withHeaders(['X-API-KEY' => self::RAW_API_KEY])
            ->postJson('/api/randomize', [
                'raffle_id' => 'raffle-duplicate',
                'ticket_uuids' => [
                    '550e8400-e29b-41d4-a716-446655440000',
                    '550e8400-e29b-41d4-a716-446655440001',
                ],
            ]);

        $response->assertOk();

        $duplicateResponse = $this
            ->withHeaders(['X-API-KEY' => self::RAW_API_KEY])
            ->postJson('/api/randomize', [
                'raffle_id' => 'raffle-duplicate',
                'ticket_uuids' => [
                    '550e8400-e29b-41d4-a716-446655440010',
                    '550e8400-e29b-41d4-a716-446655440011',
                ],
            ]);

        $duplicateResponse->assertStatus(409);
        $duplicateResponse->assertJsonPath('message', 'raffle_id already exists');
    }

    public function test_invalid_ticket_uuid_returns_422(): void
    {
        $response = $this
            ->withHeaders(['X-API-KEY' => self::RAW_API_KEY])
            ->postJson('/api/randomize', [
                'raffle_id' => 'raffle-invalid-uuid',
                'ticket_uuids' => [
                    '550e8400-e29b-41d4-a716-446655440000',
                    'not-a-uuid',
                ],
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ticket_uuids.1']);
    }

    public function test_missing_ticket_uuids_returns_422(): void
    {
        $response = $this
            ->withHeaders(['X-API-KEY' => self::RAW_API_KEY])
            ->postJson('/api/randomize', [
                'raffle_id' => 'raffle-missing-ticket-uuids',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ticket_uuids']);
    }

    public function test_get_raffle_page_shows_winner_and_audit(): void
    {
        $createResponse = $this
            ->withHeaders(['X-API-KEY' => self::RAW_API_KEY])
            ->postJson('/api/randomize', $this->payload('raffle-verify-page'));

        $createResponse->assertOk();

        $selectedUuid = $createResponse->json('selected_uuid');
        $digest = $createResponse->json('meta.audit.digest_sha256');

        $page = $this->get('/raffles/raffle-verify-page');

        $page->assertOk();
        $page->assertSee('Raffle raffle-verify-page');
        $page->assertSee((string) $selectedUuid);
        $page->assertSee((string) $digest);
    }

    public function test_rafflee_query_auto_redirects_and_shows_raffle(): void
    {
        $this
            ->withHeaders(['X-API-KEY' => self::RAW_API_KEY])
            ->postJson('/api/randomize', $this->payload('raffle-query-open'))
            ->assertOk();

        $page = $this->followingRedirects()->get('/raffles?rafflee=raffle-query-open');

        $page->assertOk();
        $page->assertSee('Raffle raffle-query-open');
    }

    public function test_missing_raffle_uses_toast_error_instead_of_404(): void
    {
        $page = $this->followingRedirects()->get('/raffles/raffle-does-not-exist');

        $page->assertOk();
        $page->assertSee("Raffle ID 'raffle-does-not-exist' does not exist.");
    }

    public function test_large_array_within_limit_succeeds(): void
    {
        config()->set('omaraf.max_uuids', 6000);

        $ticketUuids = [];

        for ($i = 0; $i < 5000; $i++) {
            $suffix = str_pad(dechex($i), 12, '0', STR_PAD_LEFT);
            $ticketUuids[] = "00000000-0000-4000-8000-{$suffix}";
        }

        $response = $this
            ->withHeaders(['X-API-KEY' => self::RAW_API_KEY])
            ->postJson('/api/randomize', [
                'raffle_id' => 'raffle-large',
                'ticket_uuids' => $ticketUuids,
            ]);

        $response->assertOk();
        $response->assertJsonPath('meta.count', 5000);
    }

    public function test_raffle_tickets_data_endpoint_returns_datatables_payload(): void
    {
        $this
            ->withHeaders(['X-API-KEY' => self::RAW_API_KEY])
            ->postJson('/api/randomize', $this->payload('raffle-tickets-data'))
            ->assertOk();

        $response = $this->get('/raffles/raffle-tickets-data/tickets/data?draw=1&start=0&length=2');

        $response->assertOk();
        $response->assertJsonPath('draw', 1);
        $response->assertJsonPath('recordsTotal', 3);
        $response->assertJsonPath('recordsFiltered', 3);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.position', 0);
    }

    public function test_raffle_tickets_export_csv_downloads_ticket_rows(): void
    {
        $this
            ->withHeaders(['X-API-KEY' => self::RAW_API_KEY])
            ->postJson('/api/randomize', $this->payload('raffle-export-csv'))
            ->assertOk();

        $response = $this->get('/raffles/raffle-export-csv/tickets/export.csv');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        $this->assertStringContainsString("position,uuid\n", $csv);
        $this->assertStringContainsString("0,550e8400-e29b-41d4-a716-446655440000\n", $csv);
        $this->assertStringContainsString("1,550e8400-e29b-41d4-a716-446655440001\n", $csv);
        $this->assertStringContainsString("2,550e8400-e29b-41d4-a716-446655440002\n", $csv);
    }

    public function test_raffle_tickets_export_excel_downloads_ticket_rows(): void
    {
        $this
            ->withHeaders(['X-API-KEY' => self::RAW_API_KEY])
            ->postJson('/api/randomize', $this->payload('raffle-export-excel'))
            ->assertOk();

        $response = $this->get('/raffles/raffle-export-excel/tickets/export.xls');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.ms-excel; charset=UTF-8');

        $excel = $response->streamedContent();

        $this->assertStringContainsString('<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"', $excel);
        $this->assertStringContainsString('<Data ss:Type="Number">0</Data>', $excel);
        $this->assertStringContainsString('<Data ss:Type="String">550e8400-e29b-41d4-a716-446655440000</Data>', $excel);
    }

    private function payload(string $raffleId): array
    {
        return [
            'raffle_id' => $raffleId,
            'ticket_uuids' => [
                '550e8400-e29b-41d4-a716-446655440000',
                '550e8400-e29b-41d4-a716-446655440001',
                '550e8400-e29b-41d4-a716-446655440002',
            ],
        ];
    }
}
