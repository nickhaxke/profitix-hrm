<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->get();

        return view('users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,hr,manager',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'], // Hash is handled by Model cast
            'role' => $validated['role'],
            'is_active' => true,
        ]);

        AuditService::log('Created user: '.$user->email);

        return redirect()->route('users.index')->with('success', 'User created.');
    }

    public function update(Request $request, User $user)
    {
        if ($user->role === 'superadmin' || $user->email === 'superadmin@profitix.com') {
            return redirect()->route('users.index')->with('error', 'Super Admin cannot be edited from the frontend.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'role' => 'required|in:admin,hr,manager',
            'is_active' => 'boolean',
        ]);

        $data = $request->only('name', 'email', 'role');
        $data['is_active'] = $request->has('is_active');

        if ($request->filled('password')) {
            $data['password'] = $request->password; // Handled by cast
        }

        $user->update($data);

        AuditService::log('Updated user: '.$user->email);

        return redirect()->route('users.index')->with('success', 'User updated.');
    }

    public function destroy(User $user)
    {
        if ($user->role === 'superadmin' || $user->email === 'superadmin@profitix.com') {
            return redirect()->route('users.index')->with('error', 'Super Admin cannot be deleted.');
        }
        if (auth()->id() === $user->id) {
            return redirect()->route('users.index')->with('error', 'You cannot delete your own account.');
        }
        $user->delete();
        AuditService::log('Deleted user: '.$user->email);

        return redirect()->route('users.index')->with('success', 'User deleted.');
    }
}
