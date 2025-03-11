<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;


class UserController extends Controller
{


    public function index(Request $request)
    {
        $query = User::query();

        // Filter by role if provided
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by search term
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', Rule::in([
                User::ROLE_SUPER_ADMIN,
                User::ROLE_ADMIN,
                User::ROLE_USER
            ])],
            'status' => 'required|boolean',
        ]);

        // Hash the password
        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('admin.users.index')
                         ->with('success', 'User created successfully!');
    }
    /**
 * Display the specified user.
 */
public function show(User $user)
{
    // Get user's event registrations
    $registrations = $user->registrations()
        ->with('event')
        ->latest()
        ->get();

    return view('admin.users.show', compact('user', 'registrations'));
}

/**
 * Show the form for editing the specified user.
 */
public function edit(User $user)
{
    return view('admin.users.edit', compact('user'));
}

/**
 * Update the specified user in storage.
 */
public function update(Request $request, User $user)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => [
            'required',
            'string',
            'email',
            'max:255',
            Rule::unique('users')->ignore($user->id),
        ],
        'role' => [
            'required',
            Rule::in([
                User::ROLE_SUPER_ADMIN,
                User::ROLE_ADMIN,
                User::ROLE_USER
            ])
        ],
        'status' => 'required|boolean',
    ]);

    // Only update password if provided
    if ($request->filled('password')) {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $validated['password'] = Hash::make($request->password);
    }

    $user->update($validated);

    return redirect()->route('admin.users.index')
        ->with('success', 'User updated successfully!');
}

/**
 * Remove the specified user from storage.
 */
public function destroy(User $user)
{
    // Prevent self-deletion
    if (auth()->id() === $user->id) {
        return redirect()->route('admin.users.index')
            ->with('error', 'You cannot delete your own account!');
    }

    // Check if user has any registrations before deletion
    if ($user->registrations()->count() > 0) {
        // Option: Delete associated registrations
        $user->registrations()->delete();
    }

    $user->delete();

    return redirect()->route('admin.users.index')
        ->with('success', 'User deleted successfully!');
}

/**
 * Toggles the status of a user (active/inactive)
 */
public function toggleStatus(User $user)
{
    // Prevent deactivating your own account
    if (auth()->id() === $user->id) {
        return redirect()->route('admin.users.index')
            ->with('error', 'You cannot change the status of your own account!');
    }

    $user->status = !$user->status;
    $user->save();

    $statusText = $user->status ? 'activated' : 'deactivated';

    return redirect()->route('admin.users.index')
        ->with('success', "User {$statusText} successfully!");
}

/**
 * Reset the user's password to a random string
 */
public function resetPassword(User $user)
{
    // Generate a random password
    $password = \Str::random(10);

    // Update the user's password
    $user->password = Hash::make($password);
    $user->save();

    // For a school system, you might want to send this password to the user
    // via email, but for now we'll just flash it to the session
    return redirect()->route('admin.users.show', $user)
        ->with('success', "Password reset successfully. New password: {$password}");
}

/**
 * Export users list as CSV
 */
public function export(Request $request)
{
    $fileName = 'users_' . date('Y-m-d') . '.csv';

    $users = User::query();

    // Apply any filters
    if ($request->has('role')) {
        $users->where('role', $request->role);
    }

    if ($request->has('status')) {
        $users->where('status', $request->status);
    }

    $users = $users->get();

    $headers = [
        "Content-type" => "text/csv",
        "Content-Disposition" => "attachment; filename=$fileName",
        "Pragma" => "no-cache",
        "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
        "Expires" => "0"
    ];

    $columns = ['ID', 'Name', 'Email', 'Role', 'Status', 'Created At'];

    $callback = function() use($users, $columns) {
        $file = fopen('php://output', 'w');
        fputcsv($file, $columns);

        foreach ($users as $user) {
            $row['ID'] = $user->id;
            $row['Name'] = $user->name;
            $row['Email'] = $user->email;
            $row['Role'] = $user->role;
            $row['Status'] = $user->status ? 'Active' : 'Inactive';
            $row['Created At'] = $user->created_at->format('Y-m-d H:i:s');

            fputcsv($file, array_values($row));
        }

        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

/**
 * Bulk action for multiple users
 */
public function bulkAction(Request $request)
{
    $validated = $request->validate([
        'user_ids' => 'required|array',
        'user_ids.*' => 'exists:users,id',
        'action' => 'required|in:activate,deactivate,delete',
    ]);

    // Don't allow actions on own account
    if (in_array(auth()->id(), $validated['user_ids'])) {
        return redirect()->route('admin.users.index')
            ->with('error', 'You cannot perform bulk actions on your own account!');
    }

    $count = count($validated['user_ids']);

    switch ($validated['action']) {
        case 'activate':
            User::whereIn('id', $validated['user_ids'])->update(['status' => true]);
            $message = "{$count} users activated successfully!";
            break;

        case 'deactivate':
            User::whereIn('id', $validated['user_ids'])->update(['status' => false]);
            $message = "{$count} users deactivated successfully!";
            break;

        case 'delete':
            // This might need more complex logic if you need to handle registrations
            User::destroy($validated['user_ids']);
            $message = "{$count} users deleted successfully!";
            break;
    }

    return redirect()->route('admin.users.index')->with('success', $message);
}
}
}
