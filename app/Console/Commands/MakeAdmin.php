<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'user:make-admin {email : Email akun yang akan dijadikan Admin}';

    protected $description = 'Mengubah role pengguna menjadi Admin.';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('Pengguna dengan email tersebut tidak ditemukan.');
            return self::FAILURE;
        }

        $user->update(['role' => 'admin']);
        $this->info("{$user->name} sekarang menjadi Admin.");

        return self::SUCCESS;
    }
}
