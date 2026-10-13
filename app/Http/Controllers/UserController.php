<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index() 
    { 
        $users = \App\Models\User::with('roles')->paginate(15);
        return view('users.index', compact('users')); 
    }
    public function create() { return view('coming-soon', ['module' => 'UserController']); }
    public function store(Request $request) { return redirect()->back(); }
    public function show($id) { return view('coming-soon', ['module' => 'UserController']); }
    public function edit($id) { return view('coming-soon', ['module' => 'UserController']); }
    public function update(Request $request, $id)
    {
        $user = \App\Models\User::findOrFail($id);
        $validated = $request->validate([
            'phone'            => 'nullable|string|max:25',
            'telegram_chat_id' => 'nullable|string|max:50',
            'is_active'        => 'nullable|boolean',
        ]);
        
        $user->update([
            'phone'            => $validated['phone'] ?? null,
            'telegram_chat_id' => $validated['telegram_chat_id'] ?? null,
            'is_active'        => $request->has('is_active') ? (bool) $request->is_active : $user->is_active,
        ]);

        return redirect()->back()->with('success', "Kontak notifikasi untuk {$user->name} berhasil diperbarui!");
    }
    public function destroy($id) { return redirect()->back(); }
}
