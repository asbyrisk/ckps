<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::query()->latest()->paginate(15);

        return view('users.index', compact('users'));
    }

    public function update(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'Role akun yang sedang digunakan tidak dapat diubah dari halaman ini.');
        }

        $validated = $request->validate([
            'role' => ['required', Rule::in(['admin', 'user'])],
        ]);

        $oldRole = $user->role;

        if ($oldRole === 'admin' && $validated['role'] === 'user' && User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'Minimal harus ada satu akun Admin. Role Admin terakhir tidak dapat diturunkan.');
        }

        $user->update(['role' => $validated['role']]);

        $audit->log(
            'user_role_updated',
            "Role pengguna {$user->name} diubah dari {$oldRole} menjadi {$user->role}.",
            $user,
            null,
            ['old_role' => $oldRole, 'new_role' => $user->role]
        );

        return back()->with('success', 'Role pengguna berhasil diperbarui.');
    }
}
