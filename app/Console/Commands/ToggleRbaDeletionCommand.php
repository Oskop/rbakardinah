<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SystemSetting;
use App\Models\ActivityLog;

class ToggleRbaDeletionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rba:toggle-deletion {state? : State to set (enable/disable, on/off, 1/0)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Toggle or set the RBA Header deletion permission and button visibility in system settings.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $stateArg = $this->argument('state');
        $currentState = SystemSetting::isRbaDeletionAllowed();

        if ($stateArg !== null) {
            $stateLower = strtolower($stateArg);
            if (in_array($stateLower, ['enable', 'on', '1', 'true', 'aktif'])) {
                $newState = true;
            } elseif (in_array($stateLower, ['disable', 'off', '0', 'false', 'kunci'])) {
                $newState = false;
            } else {
                $this->error("Invalid state argument: '{$stateArg}'. Expected 'enable' or 'disable'.");
                return 1;
            }
        } else {
            // Invert current state if no state is specified
            $newState = !$currentState;
        }

        $setting = SystemSetting::set('allow_rba_deletion', $newState, 1);

        ActivityLog::create([
            'user_id' => 1,
            'user_name' => 'CLI Artisan',
            'user_role' => 'Administrator',
            'action' => 'updated',
            'model_type' => SystemSetting::class,
            'model_id' => $setting->id,
            'description' => $newState
                ? 'Mengaktifkan fitur dan tombol Hapus RBA Header via CLI Command'
                : 'Menonaktifkan dan mengunci fitur/tombol Hapus RBA Header via CLI Command',
            'old_values' => ['allow_rba_deletion' => $currentState],
            'new_values' => ['allow_rba_deletion' => $newState],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Artisan CLI (rba:toggle-deletion)',
        ]);

        if ($newState) {
            $this->info("✅ RBA Header deletion has been ENABLED (UNLOCKED).");
            $this->comment("The 'Hapus RBA' button is now visible in the web UI (/admin/headers) and deletion requests are allowed.");
        } else {
            $this->info("🔒 RBA Header deletion has been DISABLED (LOCKED).");
            $this->comment("The 'Hapus RBA' button is now hidden in the web UI (/admin/headers) and deletion requests are blocked.");
        }

        return 0;
    }
}

