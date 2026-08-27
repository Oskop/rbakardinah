<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SimrsSsoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper to create a signed mock JWT with claims.
     */
    private function createMockJwt(array $claims): string
    {
        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'EdDSA']));
        $payload = base64_encode(json_encode($claims));
        $signature = base64_encode('mock-signature');

        return "{$header}.{$payload}.{$signature}";
    }

    public function test_user_can_login_via_simrs_sso_with_mocked_oidc_server()
    {
        Config::set('simrs_oidc.enabled', true);

        $idToken = $this->createMockJwt([
            'sub' => 'simrs-user-101',
            'nip' => '198501012010011001',
            'name' => 'dr. Budi Santoso, Sp.A',
            'email' => 'budi.santoso@kardinah.tegal.go.id',
        ]);

        Http::fake([
            config('simrs_oidc.token_endpoint') => Http::response([
                'access_token' => 'mock_access_token_12345',
                'token_type' => 'Bearer',
                'expires_in' => 900,
                'refresh_token' => 'mock_refresh_token_67890',
                'id_token' => $idToken,
                'scope' => 'openid profile email simrs:pegawai',
            ], 200),
        ]);

        $response = $this->post(route('login.sso'), [
            'username_simrs' => '198501012010011001',
            'password_simrs' => 'rahasia123',
        ]);

        $this->assertAuthenticated();
        $user = auth()->user();
        $this->assertEquals('dr. Budi Santoso, Sp.A', $user->name);
        $this->assertEquals('198501012010011001', $user->nip);
        $this->assertEquals('simrs-user-101', $user->simrs_sub);
        $this->assertEquals('Operator', $user->role);
        $this->assertEquals('simrs_oidc', $user->auth_provider);

        $this->assertEquals('mock_access_token_12345', session('simrs_access_token'));
        $this->assertEquals('mock_refresh_token_67890', session('simrs_refresh_token'));

        $response->assertRedirect(route('operator.dashboard'));
    }

    public function test_new_sso_user_is_provisioned_with_default_operator_role()
    {
        Config::set('simrs_oidc.enabled', true);
        Config::set('simrs_oidc.default_role', 'Operator');

        $idToken = $this->createMockJwt([
            'sub' => 'simrs-user-202',
            'nip' => '199002022015022002',
            'name' => 'Siti Aminah, S.Kep',
            'email' => 'siti.aminah@kardinah.tegal.go.id',
        ]);

        Http::fake([
            config('simrs_oidc.token_endpoint') => Http::response([
                'access_token' => 'mock_access_token_202',
                'token_type' => 'Bearer',
                'expires_in' => 900,
                'refresh_token' => 'mock_refresh_token_202',
                'id_token' => $idToken,
                'scope' => 'openid profile email simrs:pegawai',
            ], 200),
        ]);

        $this->assertEquals(0, User::where('nip', '199002022015022002')->count());

        $this->post(route('login.sso'), [
            'username_simrs' => '199002022015022002',
            'password_simrs' => 'password202',
        ]);

        $this->assertDatabaseHas('users', [
            'nip' => '199002022015022002',
            'name' => 'Siti Aminah, S.Kep',
            'role' => 'Operator',
            'auth_provider' => 'simrs_oidc',
        ]);
    }

    public function test_existing_supervisor_or_admin_retains_role_when_logging_in_via_sso()
    {
        $unit = \App\Models\Unit::create(['name' => 'Pelayanan Medis', 'code' => 'YANMED']);

        // Pre-existing Supervisor in SIPAKAR
        $supervisor = User::factory()->create([
            'name' => 'Kabid Pelayanan Medis',
            'email' => 'kabid.pelayanan@hospital.com',
            'nip' => '197505052000031001',
            'role' => 'Supervisor',
            'unit_id' => $unit->id,
        ]);

        $idToken = $this->createMockJwt([
            'sub' => 'simrs-user-kabid',
            'nip' => '197505052000031001',
            'name' => 'dr. Kabid Pelayanan Medis, MMR',
            'email' => 'kabid.pelayanan@hospital.com',
        ]);

        Http::fake([
            config('simrs_oidc.token_endpoint') => Http::response([
                'access_token' => 'mock_access_token_supervisor',
                'token_type' => 'Bearer',
                'expires_in' => 900,
                'refresh_token' => 'mock_refresh_token_supervisor',
                'id_token' => $idToken,
                'scope' => 'openid profile email simrs:pegawai',
            ], 200),
        ]);

        $response = $this->post(route('login.sso'), [
            'username_simrs' => '197505052000031001',
            'password_simrs' => 'supersecret',
        ]);

        $this->assertAuthenticatedAs($supervisor);
        $freshUser = $supervisor->fresh();

        // Role and unit must be preserved
        $this->assertEquals('Supervisor', $freshUser->role);
        $this->assertEquals(1, $freshUser->unit_id);
        $this->assertEquals('simrs-user-kabid', $freshUser->simrs_sub);
        $this->assertEquals('simrs_oidc', $freshUser->auth_provider);

        $response->assertRedirect(route('supervisor.dashboard'));
    }

    public function test_sso_login_fails_gracefully_when_simrs_returns_invalid_grant()
    {
        Config::set('simrs_oidc.enabled', true);

        Http::fake([
            config('simrs_oidc.token_endpoint') => Http::response([
                'error' => 'invalid_grant',
                'error_description' => 'Invalid username or password',
            ], 400),
        ]);

        $response = $this->post(route('login.sso'), [
            'username_simrs' => 'wrong_nip',
            'password_simrs' => 'wrong_pass',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['username_simrs']);
    }

    public function test_sso_login_fails_gracefully_when_simrs_server_is_down_or_timeouts()
    {
        Config::set('simrs_oidc.enabled', true);

        Http::fake([
            config('simrs_oidc.token_endpoint') => function () {
                throw new ConnectionException('Connection timed out after 10000ms');
            },
        ]);

        $response = $this->post(route('login.sso'), [
            'username_simrs' => '198501012010011001',
            'password_simrs' => 'rahasia123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['username_simrs']);
    }

    public function test_local_login_remains_fully_functional_when_sso_is_enabled()
    {
        Config::set('simrs_oidc.enabled', true);

        $admin = User::factory()->create([
            'email' => 'admin.sipakar@hospital.com',
            'password' => Hash::make('password123'),
            'role' => 'Administrator',
            'auth_provider' => 'local',
        ]);

        $response = $this->post(route('login'), [
            'email' => 'admin.sipakar@hospital.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_login_screen_renders_with_vite_assets_and_tabs_properly()
    {
        Config::set('simrs_oidc.enabled', true);

        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertSee('Pegawai SIMRS (SSO)');
        $response->assertSee('Akun Lokal SIPAKAR');
        $response->assertSee('x-cloak');
    }
}
