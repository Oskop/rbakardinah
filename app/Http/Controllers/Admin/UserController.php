<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Unit;
use App\Models\SubUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::with(['unit', 'subUnit'])->get();
        $units = Unit::with('subUnits')->orderBy('name')->get();
        return view('admin.users.index', compact('users', 'units'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $units = Unit::with('subUnits')->orderBy('name')->get();
        $subUnits = SubUnit::where('is_active', true)->orderBy('name')->get();
        $roles = ['Administrator', 'Supervisor', 'Operator'];
        return view('admin.users.create', compact('units', 'subUnits', 'roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'string', 'in:Administrator,Supervisor,Operator'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'sub_unit_id' => ['nullable', 'exists:sub_units,id'],
            'jabatan' => ['nullable', 'string', 'max:255'],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'unit_id' => $request->unit_id,
            'sub_unit_id' => $request->sub_unit_id,
            'jabatan' => $request->jabatan,
            'is_active' => true,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $units = Unit::with('subUnits')->orderBy('name')->get();
        $subUnits = SubUnit::where('is_active', true)->orderBy('name')->get();
        $roles = ['Administrator', 'Supervisor', 'Operator'];
        return view('admin.users.edit', compact('user', 'units', 'subUnits', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'string', 'in:Administrator,Supervisor,Operator'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'sub_unit_id' => ['nullable', 'exists:sub_units,id'],
            'jabatan' => ['nullable', 'string', 'max:255'],
        ]);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'unit_id' => $request->unit_id,
            'sub_unit_id' => $request->sub_unit_id,
            'jabatan' => $request->jabatan,
            'is_active' => $request->is_active,
        ];

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('admin.users.index')->with('success', "User berhasil $status.");
    }
}
