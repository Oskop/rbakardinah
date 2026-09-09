<?php

namespace Tests\Feature\Admin;

use App\Models\ApiClient;
use App\Models\ApiAccessLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ApiAccessLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $supervisor;
    protected User $operator;
    protected ApiClient $client;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'Administrator']);
        $this->supervisor = User::factory()->create(['role' => 'Supervisor']);
        $this->operator = User::factory()->create(['role' => 'Operator']);

        $clientData = ApiClient::createWithToken('Aplikasi Rekanan Test');
        $this->client = $clientData['client'];
        $this->token = $clientData['plain_token'];
    }

    public function test_api_request_is_automatically_logged_on_success(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/pagu?year=2026');

        $response->assertStatus(200);

        $this->assertDatabaseHas('api_access_logs', [
            'api_client_id' => $this->client->id,
            'client_name' => 'Aplikasi Rekanan Test',
            'method' => 'GET',
            'endpoint' => '/api/v1/pagu',
            'status_code' => 200,
        ]);

        $log = ApiAccessLog::latest('id')->first();
        $this->assertNotNull($log);
        $this->assertEquals(['year' => '2026'], $log->query_params);
        $this->assertGreaterThanOrEqual(0, $log->response_time_ms);
        $this->assertEquals('127.0.0.1', $log->ip_address);
    }

    public function test_unauthorized_api_request_is_logged_with_401(): void
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->getJson('/api/v1/pagu');

        $response->assertStatus(401);

        $this->assertDatabaseHas('api_access_logs', [
            'api_client_id' => null,
            'status_code' => 401,
            'method' => 'GET',
            'endpoint' => '/api/v1/pagu',
        ]);

        $log = ApiAccessLog::latest('id')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('API Key tidak ditemukan', (string) $log->error_message);
    }

    public function test_forbidden_api_request_with_revoked_token_is_logged(): void
    {
        $this->client->update(['is_active' => false]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/pagu');

        $response->assertStatus(403);

        $this->assertDatabaseHas('api_access_logs', [
            'api_client_id' => $this->client->id,
            'status_code' => 403,
            'client_name' => 'Aplikasi Rekanan Test',
        ]);
    }

    public function test_admin_can_view_api_logs_dashboard_and_metrics(): void
    {
        ApiAccessLog::create([
            'api_client_id' => $this->client->id,
            'client_name' => $this->client->name,
            'endpoint' => '/api/v1/pagu',
            'method' => 'GET',
            'status_code' => 200,
            'response_time_ms' => 45.2,
            'ip_address' => '192.168.1.10',
            'user_agent' => 'PostmanRuntime/7.32',
        ]);

        ApiAccessLog::create([
            'api_client_id' => null,
            'client_name' => null,
            'endpoint' => '/api/v1/pagu',
            'method' => 'GET',
            'status_code' => 401,
            'response_time_ms' => 12.0,
            'ip_address' => '10.0.0.5',
            'error_message' => 'API Key tidak valid',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.api-logs.index'));

        $response->assertStatus(200);
        $response->assertSee('Monitoring &amp; Log Akses REST API', false);
        $response->assertSee('Aplikasi Rekanan Test');
        $response->assertSee('192.168.1.10');
        $response->assertSee('401');
    }

    public function test_admin_can_filter_api_logs_by_search_and_status(): void
    {
        $clientAlpha = ApiClient::createWithToken('Klien Alpha')['client'];
        $clientBeta = ApiClient::createWithToken('Klien Beta')['client'];

        ApiAccessLog::create([
            'api_client_id' => $clientAlpha->id,
            'client_name' => 'Klien Alpha',
            'endpoint' => '/api/v1/pagu',
            'method' => 'GET',
            'status_code' => 200,
            'response_time_ms' => 30.0,
            'ip_address' => '1.1.1.1',
        ]);

        ApiAccessLog::create([
            'api_client_id' => $clientBeta->id,
            'client_name' => 'Klien Beta',
            'endpoint' => '/api/v1/pagu',
            'method' => 'GET',
            'status_code' => 500,
            'response_time_ms' => 120.0,
            'ip_address' => '2.2.2.2',
        ]);

        // Filter search
        $searchResponse = $this->actingAs($this->admin)->get(route('admin.api-logs.index', ['search' => 'Alpha']));
        $searchResponse->assertStatus(200);
        $logs = $searchResponse->viewData('logs');
        $this->assertCount(1, $logs);
        $this->assertEquals('Klien Alpha', $logs->first()->client_name);

        // Filter status code
        $statusResponse = $this->actingAs($this->admin)->get(route('admin.api-logs.index', ['status' => 'server_error']));
        $statusResponse->assertStatus(200);
        $statusLogs = $statusResponse->viewData('logs');
        $this->assertCount(1, $statusLogs);
        $this->assertEquals(500, $statusLogs->first()->status_code);
    }

    public function test_admin_can_view_single_api_log_json_for_modal(): void
    {
        $log = ApiAccessLog::create([
            'api_client_id' => $this->client->id,
            'client_name' => $this->client->name,
            'endpoint' => '/api/v1/pagu',
            'method' => 'GET',
            'status_code' => 200,
            'response_time_ms' => 55.4,
            'query_params' => ['year' => '2026', 'status' => 'Approved'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'GuzzleHttp/7.0',
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('admin.api-logs.show', $log->id));

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $log->id,
            'client_name' => $this->client->name,
            'status_code' => 200,
            'response_time_ms' => 55.4,
            'query_params' => [
                'year' => '2026',
                'status' => 'Approved',
            ],
            'ip_address' => '127.0.0.1',
        ]);
    }

    public function test_non_admin_cannot_access_api_logs(): void
    {
        $supervisorResponse = $this->actingAs($this->supervisor)->get(route('admin.api-logs.index'));
        $supervisorResponse->assertStatus(403);

        $operatorResponse = $this->actingAs($this->operator)->get(route('admin.api-logs.index'));
        $operatorResponse->assertStatus(403);
    }

    public function test_admin_can_prune_old_api_logs_via_web(): void
    {
        $oldLog = ApiAccessLog::create([
            'api_client_id' => $this->client->id,
            'client_name' => $this->client->name,
            'endpoint' => '/api/v1/pagu',
            'method' => 'GET',
            'status_code' => 200,
            'response_time_ms' => 40.0,
            'ip_address' => '127.0.0.1',
            'created_at' => Carbon::now()->subDays(40),
        ]);

        $recentLog = ApiAccessLog::create([
            'api_client_id' => $this->client->id,
            'client_name' => $this->client->name,
            'endpoint' => '/api/v1/pagu',
            'method' => 'GET',
            'status_code' => 200,
            'response_time_ms' => 40.0,
            'ip_address' => '127.0.0.1',
            'created_at' => Carbon::now()->subDays(5),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.api-logs.prune'), [
            'days' => 30,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('api_access_logs', ['id' => $oldLog->id]);
        $this->assertDatabaseHas('api_access_logs', ['id' => $recentLog->id]);
    }

    public function test_prune_api_logs_artisan_command(): void
    {
        $oldLog = ApiAccessLog::create([
            'api_client_id' => $this->client->id,
            'client_name' => $this->client->name,
            'endpoint' => '/api/v1/pagu',
            'method' => 'GET',
            'status_code' => 200,
            'response_time_ms' => 40.0,
            'ip_address' => '127.0.0.1',
            'created_at' => Carbon::now()->subDays(20),
        ]);

        $recentLog = ApiAccessLog::create([
            'api_client_id' => $this->client->id,
            'client_name' => $this->client->name,
            'endpoint' => '/api/v1/pagu',
            'method' => 'GET',
            'status_code' => 200,
            'response_time_ms' => 40.0,
            'ip_address' => '127.0.0.1',
            'created_at' => Carbon::now()->subDays(3),
        ]);

        $this->artisan('api:logs-prune', ['--days' => 15])
            ->expectsOutputToContain('Berhasil membersihkan 1 rekaman log akses API')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('api_access_logs', ['id' => $oldLog->id]);
        $this->assertDatabaseHas('api_access_logs', ['id' => $recentLog->id]);
    }
}
