<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'user:make-admin {email}';

    protected $description = 'Convierte en administrador a la cuenta con el correo dado';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error("No existe un usuario con el correo {$this->argument('email')}.");
            return self::FAILURE;
        }

        $user->update(['is_admin' => true]);
        $this->info("✓ {$user->name} ({$user->email}) ahora es administrador.");

        return self::SUCCESS;
    }
}
