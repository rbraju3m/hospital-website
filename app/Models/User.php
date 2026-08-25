<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\StaffRoles;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * May this account reach a section of the panel?
     *
     * The single implementation. The `reach` Gate, the route middleware, the
     * menu, the palette and the search endpoint all come through here, so
     * there is one answer to the question rather than five that agree today.
     */
    public function canReach(string $section): bool
    {
        return StaffRoles::grants($this->role, $section);
    }

    public function isAdministrator(): bool
    {
        return $this->role === StaffRoles::ADMINISTRATOR;
    }

    public function roleLabel(): string
    {
        return StaffRoles::label($this->role);
    }
}
