<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeSuperAdmin extends Command
{
    protected $signature = 'make:superadmin {email? : L\'adresse email du compte à promouvoir}';
    protected $description = 'Promeut ou révoque le rôle Super Administrateur de la plateforme';

    public function handle(): int
    {
        $email = $this->argument('email') ?? $this->ask('Adresse email du compte utilisateur');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("Aucun utilisateur trouvé avec l'email : {$email}");
            return self::FAILURE;
        }

        $user->is_platform_admin = true;
        $user->save();

        $this->info("✓ L'utilisateur {$user->name} ({$user->email}) est désormais Super Administrateur de la plateforme !");
        return self::SUCCESS;
    }
}
