<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create
                            {--name= : The staff member\'s name}
                            {--email= : The address they sign in with}
                            {--password= : Their password (prompted for when omitted)}';

    protected $description = 'Create a staff account for the admin panel';

    public function handle(): int
    {
        $name = $this->option('name') ?: text('Name', required: true);
        $email = $this->option('email') ?: text('Email', required: true);
        $plain = $this->option('password') ?: password('Password', required: true);

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $plain],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        // `password` is cast to hashed on the model, so it is hashed on save.
        $user = User::create(['name' => $name, 'email' => $email, 'password' => $plain]);

        $this->components->info("Staff account created for {$user->email}.");

        return self::SUCCESS;
    }
}
