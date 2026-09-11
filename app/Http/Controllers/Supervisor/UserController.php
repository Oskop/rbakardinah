<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SubUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $unitId = Auth::user()->unit_id;
        $users = User::where('unit_id', $unitId)->with('subUnit')->get();
        $subUnits = SubUnit::where('unit_id', $unitId)->where('is_active', true)->orderBy('name')->get();
        return view('supervisor.users.index', compact('users', 'subUnits'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $subUnits = SubUnit::where('unit_id', Auth::user()->unit_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('supervisor.users.create', compact('subUnits'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $unitId = Auth::user()->unit_id;

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'sub_unit_id' => [
                'nullable',
                'exists:sub_units,id',
                function ($attribute, $value, $fail) use ($unitId) {
                    if ($value && !SubUnit::where('id', $value)->where('unit_id', $unitId)->exists()) {
                        $fail('Sub-unit yang dipilih tidak berada di bawah naungan unit kerja Anda.');
                    }
                },
            ],
            'jabatan' => ['nullable', 'string', 'max:255'],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'Operator', // Supervisor can only create Operators
            'unit_id' => $unitId,
            'sub_unit_id' => $request->sub_unit_id,
            'jabatan' => $request->jabatan,
            'is_active' => true,
        ]);

        return redirect()->route('supervisor.users.index')->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        if ($user->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        $subUnits = SubUnit::where('unit_id', Auth::user()->unit_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('supervisor.users.edit', compact('user', 'subUnits'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        if ($user->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        $unitId = Auth::user()->unit_id;

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'is_active' => ['required', 'boolean'],
            'sub_unit_id' => [
                'nullable',
                'exists:sub_units,id',
                function ($attribute, $value, $fail) use ($unitId) {
                    if ($value && !SubUnit::where('id', $value)->where('unit_id', $unitId)->exists()) {
                        $fail('Sub-unit yang dipilih tidak berada di bawah naungan unit kerja Anda.');
                    }
                },
            ],
            'jabatan' => ['nullable', 'string', 'max:255'],
        ]);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'is_active' => $request->is_active,
            'sub_unit_id' => $request->sub_unit_id,
            'jabatan' => $request->jabatan,
        ];

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);

        return redirect()->route('supervisor.users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage. (Repurposed for toggle)
     */
    public function destroy(User $user)
    {
        if ($user->unit_id !== Auth::user()->unit_id) {
            abort(403);
        }

        if ($user->id === auth()->id()) {
            return redirect()->route('supervisor.users.index')->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('supervisor.users.index')->with('success', "User berhasil $status.");
    }
}
