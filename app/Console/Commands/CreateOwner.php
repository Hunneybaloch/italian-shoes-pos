<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateOwner extends Command
{
    protected $signature = 'app:create-owner';

    protected $description = 'Create the initial system Owner account';

    public function handle(): int
    {
        if (User::whereHas('roles', function ($query) {
            $query->where('slug', 'owner');
        })->exists()) {
            $this->error('An Owner account already exists.');
            return self::FAILURE;
        }

        $ownerRole = Role::where('slug', 'owner')
            ->where('is_active', true)
            ->first();

        if (!$ownerRole) {
            $this->error('Owner role not found. Please run the database seeder first.');
            return self::FAILURE;
        }

        $name = trim($this->ask('Enter Owner full name'));
        $username = trim($this->ask('Enter Owner username'));
        $email = strtolower(trim($this->ask('Enter Owner email')));
        $password = $this->secret('Enter Owner password');
        $passwordConfirmation = $this->secret('Confirm Owner password');

        $validator = Validator::make([
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:5', 'confirmed'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        DB::transaction(function () use ($name, $username, $email, $password, $ownerRole) {
            $owner = User::create([
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'password' => Hash::make($password),
                'branch_id' => null,
                'is_active' => true,
            ]);

            $owner->roles()->attach($ownerRole->id);
        });

        $this->info('Owner account created successfully.');
        $this->info('You can now log in using your username or email.');

        return self::SUCCESS;
    }
}
