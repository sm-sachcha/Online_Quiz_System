<?php

namespace App\Http\Controllers\MasterAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AdminManagementController extends Controller
{
    /**
     * Display a listing of admins (both admin and master admin)
     */
    public function index(Request $request)
    {
        $query = User::whereIn('role', ['admin', 'master_admin']);

        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        $admins = $query->withCount(['createdQuizzes', 'createdCategories', 'createdQuestions'])
            ->latest()
            ->paginate(15);

        return view('master-admin.admins.index', compact('admins'));
    }

    /**
     * Show the form for creating a new admin
     */
    public function create()
    {
        return view('master-admin.admins.create');
    }

    /**
     * Store a newly created admin in storage
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,master_admin',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => $request->boolean('is_active', true)
        ]);

        // Create user profile
        UserProfile::create([
            'user_id' => $user->id,
            'phone' => $request->phone
        ]);

        $roleName = $request->role == 'master_admin' ? 'Master Admin' : 'Admin';
        
        return redirect()->route('master-admin.admins.index')
            ->with('success', $roleName . ' created successfully!');
    }

    /**
     * Display the specified admin
     */
    public function show(User $admin)
    {
        // Check if user is an admin or master admin
        if (!in_array($admin->role, ['admin', 'master_admin'])) {
            abort(404, 'User not found');
        }

        $admin->load('profile');
        
        $stats = [
            'quizzes_created' => $admin->createdQuizzes()->count(),
            'categories_created' => $admin->createdCategories()->count(),
            'questions_created' => $admin->createdQuestions()->count(),
            'total_activities' => $admin->activities()->count(),
            'last_activity' => $admin->activities()->latest()->first()
        ];

        $recentActivities = $admin->activities()
            ->latest()
            ->take(20)
            ->get();

        $createdQuizzes = $admin->createdQuizzes()
            ->with('category')
            ->latest()
            ->take(10)
            ->get();

        return view('master-admin.admins.show', compact('admin', 'stats', 'recentActivities', 'createdQuizzes'));
    }

    /**
     * Show the form for editing the specified admin
     */
    public function edit(User $admin)
    {
        // Check if user is an admin or master admin
        if (!in_array($admin->role, ['admin', 'master_admin'])) {
            abort(404, 'User not found');
        }

        // Prevent editing own account through this route
        if ($admin->id === auth()->id()) {
            return redirect()->route('profile.show')
                ->with('info', 'Please edit your own profile from the profile section.');
        }

        $admin->load('profile');
        return view('master-admin.admins.edit', compact('admin'));
    }

    /**
     * Update the specified admin in storage
     */
    public function update(Request $request, User $admin)
    {
        // Check if user is an admin or master admin
        if (!in_array($admin->role, ['admin', 'master_admin'])) {
            abort(404, 'User not found');
        }

        // Prevent updating own account through this route
        if ($admin->id === auth()->id()) {
            return redirect()->route('profile.show')
                ->with('info', 'Please edit your own profile from the profile section.');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($admin->id)
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|in:admin,master_admin',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'is_active' => $request->boolean('is_active', true)
        ];

        // Only update password if provided
        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $admin->update($userData);

        // Update or create profile
        $admin->profile()->updateOrCreate(
            ['user_id' => $admin->id],
            ['phone' => $request->phone]
        );

        $roleName = $request->role == 'master_admin' ? 'Master Admin' : 'Admin';
        
        return redirect()->route('master-admin.admins.index')
            ->with('success', $roleName . ' updated successfully!');
    }

    /**
     * Remove the specified admin from storage
     */
    public function destroy(User $admin)
    {
        // Check if user is an admin or master admin
        if (!in_array($admin->role, ['admin', 'master_admin'])) {
            abort(404, 'User not found');
        }

        // Prevent deleting own account
        if ($admin->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Prevent deleting the last master admin
        if ($admin->role === 'master_admin') {
            $masterAdminCount = User::where('role', 'master_admin')->count();
            if ($masterAdminCount <= 1) {
                return back()->with('error', 'Cannot delete the last Master Admin. Please promote another admin first.');
            }
        }

        // Check if admin has created content
        if ($admin->createdQuizzes()->exists() || 
            $admin->createdCategories()->exists() || 
            $admin->createdQuestions()->exists()) {
            
            // Option to reassign or prevent deletion
            return back()->with('error', 'Cannot delete admin who has created content. Please reassign their content first.');
        }

        $adminName = $admin->name;
        $admin->delete();

        return redirect()->route('master-admin.admins.index')
            ->with('success', 'Admin "' . $adminName . '" deleted successfully.');
    }

    /**
     * Toggle admin status (activate/deactivate)
     */
    public function toggleStatus(User $admin)
    {
        // Check if user is an admin or master admin
        if (!in_array($admin->role, ['admin', 'master_admin'])) {
            return response()->json(['error' => 'Invalid user'], 404);
        }

        // Prevent toggling own status
        if ($admin->id === auth()->id()) {
            return back()->with('error', 'You cannot change your own status.');
        }

        // Prevent deactivating the last master admin
        if ($admin->role === 'master_admin' && $admin->is_active) {
            $activeMasterAdmins = User::where('role', 'master_admin')
                ->where('is_active', true)
                ->count();
            
            if ($activeMasterAdmins <= 1) {
                return back()->with('error', 'Cannot deactivate the last active Master Admin.');
            }
        }

        $admin->update([
            'is_active' => !$admin->is_active
        ]);

        $status = $admin->is_active ? 'activated' : 'deactivated';
        return back()->with('success', 'Admin has been ' . $status . ' successfully.');
    }

    /**
     * Resend welcome email to admin
     */
    public function resendWelcome(User $admin)
    {
        if (!in_array($admin->role, ['admin', 'master_admin'])) {
            return back()->with('error', 'Invalid user.');
        }

        // Logic to send welcome email
        // Mail::to($admin->email)->send(new AdminWelcomeMail($admin));
        
        return back()->with('success', 'Welcome email sent to ' . $admin->name);
    }

    /**
     * Create a new admin from a user (promote user to admin)
     */
    public function promoteToAdmin(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:admin,master_admin'
        ]);

        $user = User::findOrFail($request->user_id);
        
        // Check if user is already admin
        if ($user->isAdmin()) {
            return back()->with('error', 'User is already an administrator.');
        }

        $user->update([
            'role' => $request->role
        ]);

        $roleName = $request->role == 'master_admin' ? 'Master Admin' : 'Admin';
        
        return redirect()->route('master-admin.admins.index')
            ->with('success', $user->name . ' has been promoted to ' . $roleName . '!');
    }

    /**
     * Demote admin to regular user
     */
    public function demoteToUser(User $admin)
    {
        if (!in_array($admin->role, ['admin', 'master_admin'])) {
            return back()->with('error', 'Invalid user.');
        }

        // Prevent demoting self
        if ($admin->id === auth()->id()) {
            return back()->with('error', 'You cannot demote your own account.');
        }

        // Prevent demoting the last master admin
        if ($admin->role === 'master_admin') {
            $masterAdminCount = User::where('role', 'master_admin')->count();
            if ($masterAdminCount <= 1) {
                return back()->with('error', 'Cannot demote the last Master Admin.');
            }
        }

        $admin->update(['role' => 'user']);
        
        return redirect()->route('master-admin.admins.index')
            ->with('success', $admin->name . ' has been demoted to regular user.');
    }

    /**
     * Get list of users to promote to admin
     */
    public function getPromotableUsers()
    {
        $users = User::where('role', 'user')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
        
        return response()->json($users);
    }
}