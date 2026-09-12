<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('user:make-admin {email : Email address of the registered user}')]
#[Description('Grant shop administrator access to a registered user')]
class MakeUserAdmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->components->error("No user was found for [{$email}].");

            return self::FAILURE;
        }

        $user->forceFill(['is_admin' => true])->save();

        $this->components->info("[{$user->email}] can now manage the shop.");

        return self::SUCCESS;
    }
}
