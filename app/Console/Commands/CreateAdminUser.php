<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\StaffRoles;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create
                            {--name= : The staff member\'s name}
                            {--email= : The address they sign in with}
                            {--password= : Their password (prompted for when omitted)}
                            {--role=administrator : administrator, front_desk or editor}';

    protected $description = 'Create a staff account for the admin panel';

    public function handle(): int
    {
        $name = $this->option('name') ?: text('Name', required: true);
        $email = $this->option('email') ?: text('Email', required: true);
        $plain = $this->option('password') ?: password('Password', required: true);

        // Defaults to administrator: this command exists to create the first
        // account, and an installation whose only account cannot reach the
        // staff screen has nowhere to create the second one from.
        $role = (string) $this->option('role');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $plain, 'role' => $role],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
                'role' => ['required', Rule::in(StaffRoles::all())],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        // `password` is cast to hashed on the model, so it is hashed on save.
        $user = User::create(['name' => $name, 'email' => $email, 'password' => $plain, 'role' => $role]);

        $this->components->info("Staff account created for {$user->email} ({$user->roleLabel()}).");

        return self::SUCCESS;
    }
}
