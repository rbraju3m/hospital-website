<?php

namespace App\Http\Requests\Admin;

use App\Support\StaffRoles;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends AdminFormRequest
{
    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            // Optional when editing: an empty box means "leave the password alone".
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(8)],
            /* Required on a new account — there is no sensible default to pick
               on somebody's behalf — and optional when editing, because the
               field is not on the form at all when you are editing yourself.
               `Rule::in` rather than a free string: the column is a role name,
               and a typo would be an account that can reach nothing. */
            'role' => [$user ? 'nullable' : 'required', Rule::in(StaffRoles::all())],
        ];
    }

    public function attributes(): array
    {
        return (array) __('admin.fields');
    }
}
