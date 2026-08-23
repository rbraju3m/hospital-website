<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Staff accounts. There is one role — everyone signed in sees the whole panel —
 * so this is deliberately a short list rather than a permissions screen.
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
        $user = User::create($request->safe()->only(['name', 'email', 'password']));

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

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', __('admin.users.deleted'));
    }
}
