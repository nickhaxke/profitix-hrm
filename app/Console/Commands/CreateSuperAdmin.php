<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:superadmin {email?} {password?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a super admin user for license management';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email') ?? $this->ask('Enter super admin email', 'superadmin@profitix.com');
        $password = $this->argument('password') ?? $this->secret('Enter super admin password (default: superadmin123)');

        if (empty($password)) {
            $password = 'superadmin123';
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            $user->update([
                'password' => Hash::make($password),
                'role' => 'superadmin',
                'is_active' => true,
            ]);
            $this->info("User {$email} updated to superadmin successfully.");
        } else {
            User::create([
                'name' => 'Super Admin',
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'superadmin',
                'is_active' => true,
            ]);
            $this->info("Super admin {$email} created successfully.");
        }

        return Command::SUCCESS;
    }
}
