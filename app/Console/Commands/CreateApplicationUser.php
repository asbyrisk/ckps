<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateApplicationUser extends Command
{
    protected $signature = 'app:create-user
                            {--name= : Nama pengguna}
                            {--email= : Email pengguna}
                            {--password= : Kata sandi pengguna}';

    protected $description = 'Membuat akun pengguna aplikasi secara aman';

    public function handle(): int
    {
        $name = trim((string) ($this->option('name') ?: $this->ask('Nama pengguna')));
        $email = trim((string) ($this->option('email') ?: $this->ask('Email')));
        $password = (string) ($this->option('password') ?: $this->secret('Kata sandi'));

        $validator = Validator::make(
            compact('name', 'email', 'password'),
            [
                'name' => ['required', 'string', 'max:100'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', Password::min(8)->mixedCase()->numbers()->symbols()],
            ],
            [
                'password.min' => 'Kata sandi minimal 8 karakter.',
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->info('Akun berhasil dibuat.');
        $this->line('Email: '.$email);
        $this->warn('Simpan kata sandi secara aman dan jangan masukkan ke source code.');

        return self::SUCCESS;
    }
}
