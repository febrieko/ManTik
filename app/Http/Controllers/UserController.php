<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::with('roles');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->role($request->role);
        }

        $users = $query->latest()->paginate(15)->withQueryString();
        $roles = Role::whereIn('name', ['super-admin', 'admin', 'teknisi'])->get();

        return view('users.index', compact('users', 'roles'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $roles = [
            'super-admin' => [
                'name' => 'Super Admin',
                'description' => 'Akses penuh ke seluruh sistem, pengaturan, backup, dan manajemen pengguna.',
                'badge' => 'badge-danger',
                'color' => '#ff4444',
            ],
            'admin' => [
                'name' => 'Administrator',
                'description' => 'Akses manajemen router, PPPoE, Hotspot, billing, laporan, dan pelanggan.',
                'badge' => 'badge-info',
                'color' => '#00d4ff',
            ],
            'teknisi' => [
                'name' => 'Teknisi',
                'description' => 'Akses operasional router, monitoring lalu lintas, status perangkat, dan troubleshooting.',
                'badge' => 'badge-warning',
                'color' => '#ffb300',
            ],
        ];

        return view('users.create', compact('roles'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'              => ['required', 'string', 'min:6', 'confirmed'],
            'role'                  => ['required', Rule::in(['super-admin', 'admin', 'teknisi'])],
            'phone'                 => ['nullable', 'string', 'max:25'],
            'telegram_chat_id'      => ['nullable', 'string', 'max:50'],
            'is_active'             => ['nullable', 'boolean'],
        ], [
            'name.required'         => 'Nama pengguna wajib diisi.',
            'email.required'        => 'Email wajib diisi.',
            'email.email'           => 'Format email tidak valid.',
            'email.unique'          => 'Email sudah digunakan oleh pengguna lain.',
            'password.required'     => 'Password wajib diisi.',
            'password.min'          => 'Password minimal harus 6 karakter.',
            'password.confirmed'    => 'Konfirmasi password tidak cocok.',
            'role.required'         => 'Pilihan hak akses / role wajib dipilih.',
            'role.in'               => 'Role yang dipilih tidak valid.',
        ]);

        $user = User::create([
            'name'             => $validated['name'],
            'email'            => $validated['email'],
            'password'         => Hash::make($validated['password']),
            'phone'            => $validated['phone'] ?? null,
            'telegram_chat_id' => $validated['telegram_chat_id'] ?? null,
            'is_active'        => $request->boolean('is_active', true),
        ]);

        $user->assignRole($validated['role']);

        $roleLabel = match ($validated['role']) {
            'super-admin' => 'Super Admin',
            'admin'       => 'Administrator',
            'teknisi'     => 'Teknisi',
            default       => $validated['role'],
        };

        return redirect()->route('users.index')->with('success', "Pengguna '{$user->name}' dengan hak akses '{$roleLabel}' berhasil ditambahkan!");
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit($id)
    {
        $user = User::with('roles')->findOrFail($id);
        $roles = [
            'super-admin' => [
                'name' => 'Super Admin',
                'description' => 'Akses penuh ke seluruh sistem, pengaturan, dan manajemen pengguna.',
            ],
            'admin' => [
                'name' => 'Administrator',
                'description' => 'Akses manajemen router, PPPoE, Hotspot, billing, laporan, dan pelanggan.',
            ],
            'teknisi' => [
                'name' => 'Teknisi',
                'description' => 'Akses operasional router, monitoring, dan troubleshooting.',
            ],
        ];

        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role'             => ['required', Rule::in(['super-admin', 'admin', 'teknisi'])],
            'phone'            => ['nullable', 'string', 'max:25'],
            'telegram_chat_id' => ['nullable', 'string', 'max:50'],
            'is_active'        => ['nullable', 'boolean'],
        ], [
            'name.required'    => 'Nama pengguna wajib diisi.',
            'email.required'   => 'Email wajib diisi.',
            'email.unique'     => 'Email sudah digunakan oleh pengguna lain.',
            'role.required'    => 'Hak akses wajib dipilih.',
        ]);

        $user->update([
            'name'             => $validated['name'],
            'email'            => $validated['email'],
            'phone'            => $validated['phone'] ?? null,
            'telegram_chat_id' => $validated['telegram_chat_id'] ?? null,
            'is_active'        => $request->has('is_active') ? (bool) $request->is_active : false,
        ]);

        $user->syncRoles([$validated['role']]);

        return redirect()->route('users.index')->with('success', "Data pengguna '{$user->name}' berhasil diperbarui!");
    }

    /**
     * Update password for a specific user (by Admin / Super Admin).
     */
    public function updatePassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'password.required'  => 'Password baru wajib diisi.',
            'password.min'       => 'Password minimal harus 6 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->back()->with('success', "Password untuk pengguna '{$user->name}' berhasil diperbarui!");
    }

    /**
     * Show form to change currently authenticated user's password.
     */
    public function showChangePasswordForm()
    {
        return view('profile.change-password');
    }

    /**
     * Update currently authenticated user's password.
     */
    public function changeOwnPassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'string', 'min:6', 'confirmed', 'different:current_password'],
        ], [
            'current_password.required'         => 'Password saat ini wajib diisi.',
            'current_password.current_password' => 'Password saat ini salah.',
            'password.required'                 => 'Password baru wajib diisi.',
            'password.min'                      => 'Password baru minimal harus 6 karakter.',
            'password.confirmed'                => 'Konfirmasi password baru tidak cocok.',
            'password.different'                => 'Password baru harus berbeda dengan password lama.',
        ]);

        $user = $request->user();
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->back()->with('success', 'Password Anda berhasil diperbarui!');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if ($user->hasRole('super-admin')) {
            $superAdminCount = User::role('super-admin')->count();
            if ($superAdminCount <= 1) {
                return redirect()->back()->with('error', 'Tidak dapat menghapus satu-satunya akun Super Admin di sistem.');
            }
        }

        $userName = $user->name;
        $user->delete();

        return redirect()->route('users.index')->with('success', "Pengguna '{$userName}' berhasil dihapus.");
    }
}
