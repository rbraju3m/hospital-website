<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use App\Support\StaffRoles;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Staff accounts and the role each one holds.
 *
 * Three refusals, and all three are about not locking the panel: you cannot
 * delete yourself, you cannot delete the last account, and you cannot change
 * your own role. The last is the one that looks like a nuisance and is not —
 * an administrator who demotes themselves loses this screen in the same
 * request, and the way back is a database client.
 *
 * `admin:create` still exists for the first account and for the afternoon
 * somebody manages it anyway.
 */
class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', ['users' => User::orderBy('name')->paginate(25)]);
    }

    public function create(): View
    {
        return view('admin.users.form', ['user' => new User]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = User::create($request->safe()->only(['name', 'email', 'password', 'role']));

        return redirect()->route('admin.users.index')
            ->with('status', __('admin.users.created', ['name' => $user->name]));
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', ['user' => $user]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $user->fill($request->safe()->only(['name', 'email']));

        if ($request->filled('password')) {
            $user->password = $request->string('password')->value();
        }

        // Your own role is not on your own form, and is not taken from the
        // payload either: the form is not the only thing that can post here.
        if (! $user->is($request->user()) && $request->filled('role')) {
            $user->role = $request->string('role')->value();
        }

        $user->save();

        return back()->with('status', __('admin.users.updated', ['name' => $user->name]));
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->is(auth()->user())) {
            return back()->with('warning', __('admin.users.cannot_delete_self'));
        }

        // Locking everyone out of the panel would need database access to undo.
        if (User::count() <= 1) {
            return back()->with('warning', __('admin.users.cannot_delete_last'));
        }

        // So would deleting the only account that can reach this screen.
        if ($user->isAdministrator() && User::where('role', StaffRoles::ADMINISTRATOR)->count() <= 1) {
            return back()->with('warning', __('admin.users.cannot_delete_last_administrator'));
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', __('admin.users.deleted'));
    }
}
