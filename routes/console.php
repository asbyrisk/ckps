<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('user:make-admin {email : Email akun yang akan dijadikan Admin}', function (string $email) {
    $user = User::where('email', $email)->first();

    if (! $user) {
        $this->error("Pengguna dengan email {$email} tidak ditemukan.");
        return self::FAILURE;
    }

    if ($user->isAdmin()) {
        $this->info("{$user->name} sudah menjadi Admin.");
        return self::SUCCESS;
    }

    $user->update(['role' => 'admin']);

    $this->info("{$user->name} sekarang memiliki role Admin.");
    return self::SUCCESS;
})->purpose('Ubah role pengguna menjadi Admin');
