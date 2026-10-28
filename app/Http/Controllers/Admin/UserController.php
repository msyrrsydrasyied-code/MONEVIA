<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::withCount('transaksi')->latest();
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%");
            });
        }
        $users = $query->paginate(12)->withQueryString();
        return view('admin.users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,user',
        ]);

        User::create([
            'name' => $request->name,
            'email' => strtolower(trim($request->email)),
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return back()->with('sukses', 'Pengguna baru berhasil dibuat.');
    }

    public function updateRole(Request $request, $id)
    {
        $request->validate(['role' => 'required|in:admin,user']);
        $user = User::findOrFail($id);
        if ($user->id === auth()->id()) {
            return back()->withErrors(['role' => 'Tidak bisa mengubah peran akun yang sedang dipakai.']);
        }
        $user->update(['role' => $request->role]);
        return back()->with('sukses', 'Peran ' . $user->name . ' diperbarui.');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'Tidak bisa menghapus akun sendiri.']);
        }
        $user->delete();
        return back()->with('sukses', 'Pengguna dihapus.');
    }
}
