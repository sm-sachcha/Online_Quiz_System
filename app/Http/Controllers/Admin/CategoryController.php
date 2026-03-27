<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        if ($user->isMasterAdmin()) {
            $categories = Category::with('creator')
                ->withCount('quizzes')
                ->latest()
                ->paginate(15);
        } else {
            $categories = Category::with('creator')
                ->withCount('quizzes')
                ->where('created_by', $user->id)
                ->latest()
                ->paginate(15);
        }
        
        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'is_active' => 'boolean'
        ]);

        Category::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'icon' => $request->icon,
            'color' => $request->color,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => Auth::id()
        ]);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function edit(Category $category)
    {
        if (!Auth::user()->isMasterAdmin() && $category->created_by !== Auth::id()) {
            abort(403, 'You do not have permission to edit this category.');
        }
        
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        if (!Auth::user()->isMasterAdmin() && $category->created_by !== Auth::id()) {
            abort(403, 'You do not have permission to update this category.');
        }
        
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'is_active' => 'boolean'
        ]);

        $category->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'icon' => $request->icon,
            'color' => $request->color,
            'is_active' => $request->boolean('is_active', true)
        ]);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        if (!Auth::user()->isMasterAdmin() && $category->created_by !== Auth::id()) {
            abort(403, 'You do not have permission to delete this category.');
        }
        
        if ($category->quizzes()->exists()) {
            return back()->with('error', 'Cannot delete category with associated quizzes.');
        }

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    /**
     * Show assign users page for category
     */
    public function assignUsers(Category $category)
    {
        if (!Auth::user()->isMasterAdmin() && $category->created_by !== Auth::id()) {
            abort(403, 'You do not have permission to assign users to this category.');
        }
        
        $users = User::where('role', 'user')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        $assignedUsers = $category->assignedUsers()->pluck('user_id');
        
        return view('admin.categories.assign-users', compact('category', 'users', 'assignedUsers'));
    }

    /**
     * Assign or remove user from category
     */
    public function assignUser(Request $request, Category $category)
    {
        if (!Auth::user()->isMasterAdmin() && $category->created_by !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Permission denied'], 403);
        }
        
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'action' => 'required|in:assign,remove'
        ]);
        
        $user = User::find($request->user_id);
        
        try {
            if ($request->action == 'assign') {
                // Check if already assigned
                if ($category->assignedUsers()->where('user_id', $user->id)->exists()) {
                    return response()->json([
                        'success' => false, 
                        'message' => 'User already assigned to this category'
                    ], 400);
                }
                
                // Assign the user - NO status field, just attach
                $category->assignedUsers()->attach($user->id);
                
                return response()->json([
                    'success' => true, 
                    'message' => 'User assigned successfully',
                    'user_name' => $user->name
                ]);
                
            } else if ($request->action == 'remove') {
                // Remove the user
                $category->assignedUsers()->detach($user->id);
                
                return response()->json([
                    'success' => true, 
                    'message' => 'User removed successfully',
                    'user_name' => $user->name
                ]);
            }
            
            return response()->json(['success' => false, 'message' => 'Invalid action'], 400);
            
        } catch (\Exception $e) {
            \Log::error('Category assignment error: ' . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Failed to update assignment: ' . $e->getMessage()
            ], 500);
        }
    }
}