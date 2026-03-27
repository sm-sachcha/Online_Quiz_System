<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\Category;
use App\Models\QuizParticipant;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class QuizController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // If Master Admin - see all quizzes
        // If General Admin - see only quizzes they created
        if ($user->isMasterAdmin()) {
            $quizzes = Quiz::with('category', 'creator')
                ->withCount('questions', 'attempts')
                ->latest()
                ->paginate(15);
        } else {
            $quizzes = Quiz::with('category', 'creator')
                ->withCount('questions', 'attempts')
                ->where('created_by', $user->id)
                ->latest()
                ->paginate(15);
        }
        
        return view('admin.quizzes.index', compact('quizzes'));
    }

    public function create()
    {
        $user = Auth::user();
        
        // If Master Admin - see all categories
        // If General Admin - see only categories they created
        if ($user->isMasterAdmin()) {
            $categories = Category::where('is_active', true)->get();
        } else {
            $categories = Category::where('is_active', true)
                ->where('created_by', $user->id)
                ->get();
        }
        
        return view('admin.quizzes.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'duration_minutes' => 'required|integer|min:1|max:480',
            'passing_score' => 'required|integer|min:0|max:100',
            'is_random_questions' => 'boolean',
            'max_attempts' => 'required|integer|min:1|max:10',
            'scheduled_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:scheduled_at',
            'is_published' => 'boolean'
        ]);

        // Check if user has permission to use this category
        $category = Category::find($request->category_id);
        if (!Auth::user()->isMasterAdmin() && $category->created_by !== Auth::id()) {
            return back()->with('error', 'You do not have permission to use this category.');
        }

        $quiz = Quiz::create([
            'title' => $request->title,
            'slug' => Str::slug($request->title),
            'description' => $request->description,
            'category_id' => $request->category_id,
            'duration_minutes' => $request->duration_minutes,
            'passing_score' => $request->passing_score,
            'is_random_questions' => $request->boolean('is_random_questions', false),
            'max_attempts' => $request->max_attempts,
            'scheduled_at' => $request->scheduled_at,
            'ends_at' => $request->ends_at,
            'is_published' => $request->boolean('is_published', false),
            'created_by' => Auth::id()
        ]);

        $quiz->updateTotals();

        return redirect()->route('admin.quizzes.edit', $quiz)
            ->with('success', 'Quiz created successfully! Now add questions.');
    }

    public function show(Quiz $quiz)
    {
        // Check if user owns this quiz or is Master Admin
        if (!Auth::user()->isMasterAdmin() && $quiz->created_by !== Auth::id()) {
            abort(403, 'You do not have permission to view this quiz.');
        }
        
        $quiz->load('category', 'creator', 'questions.options');
        
        $stats = [
            'total_questions' => $quiz->questions->count(),
            'total_points' => $quiz->questions->sum('points'),
            'total_attempts' => $quiz->attempts()->count(),
            'average_score' => $quiz->attempts()->avg('score') ?? 0,
            'completion_rate' => $quiz->attempts()->count() > 0 
                ? ($quiz->attempts()->where('status', 'completed')->count() / $quiz->attempts()->count()) * 100 
                : 0
        ];

        return view('admin.quizzes.show', compact('quiz', 'stats'));
    }

    public function edit(Quiz $quiz)
    {
        // Check if user owns this quiz or is Master Admin
        if (!Auth::user()->isMasterAdmin() && $quiz->created_by !== Auth::id()) {
            abort(403, 'You do not have permission to edit this quiz.');
        }
        
        $user = Auth::user();
        
        // If Master Admin - see all categories
        // If General Admin - see only categories they created
        if ($user->isMasterAdmin()) {
            $categories = Category::where('is_active', true)->get();
        } else {
            $categories = Category::where('is_active', true)
                ->where('created_by', $user->id)
                ->get();
        }
        
        return view('admin.quizzes.edit', compact('quiz', 'categories'));
    }

    public function update(Request $request, Quiz $quiz)
    {
        // Check if user owns this quiz or is Master Admin
        if (!Auth::user()->isMasterAdmin() && $quiz->created_by !== Auth::id()) {
            abort(403, 'You do not have permission to update this quiz.');
        }
        
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'duration_minutes' => 'required|integer|min:1|max:480',
            'passing_score' => 'required|integer|min:0|max:100',
            'is_random_questions' => 'boolean',
            'max_attempts' => 'required|integer|min:1|max:10',
            'scheduled_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:scheduled_at',
            'is_published' => 'boolean'
        ]);

        // Check if user has permission to use this category
        $category = Category::find($request->category_id);
        if (!Auth::user()->isMasterAdmin() && $category->created_by !== Auth::id()) {
            return back()->with('error', 'You do not have permission to use this category.');
        }

        $quiz->update([
            'title' => $request->title,
            'slug' => Str::slug($request->title),
            'description' => $request->description,
            'category_id' => $request->category_id,
            'duration_minutes' => $request->duration_minutes,
            'passing_score' => $request->passing_score,
            'is_random_questions' => $request->boolean('is_random_questions', false),
            'max_attempts' => $request->max_attempts,
            'scheduled_at' => $request->scheduled_at,
            'ends_at' => $request->ends_at,
            'is_published' => $request->boolean('is_published', false),
            'updated_by' => Auth::id()
        ]);

        $quiz->updateTotals();

        return redirect()->route('admin.quizzes.index')
            ->with('success', 'Quiz updated successfully.');
    }

    public function destroy(Quiz $quiz)
    {
        // Check if user owns this quiz or is Master Admin
        if (!Auth::user()->isMasterAdmin() && $quiz->created_by !== Auth::id()) {
            abort(403, 'You do not have permission to delete this quiz.');
        }
        
        $quiz->delete();

        return redirect()->route('admin.quizzes.index')
            ->with('success', 'Quiz deleted successfully.');
    }

    public function duplicate(Quiz $quiz)
    {
        // Check if user owns this quiz or is Master Admin
        if (!Auth::user()->isMasterAdmin() && $quiz->created_by !== Auth::id()) {
            abort(403, 'You do not have permission to duplicate this quiz.');
        }
        
        // Generate unique title and slug
        $newTitle = $quiz->title . ' (Copy)';
        $newSlug = Str::slug($newTitle);
        
        // Check if slug already exists and make it unique
        $counter = 1;
        while (Quiz::where('slug', $newSlug)->exists()) {
            $newTitle = $quiz->title . ' (Copy ' . $counter . ')';
            $newSlug = Str::slug($newTitle);
            $counter++;
        }
        
        $newQuiz = $quiz->replicate();
        $newQuiz->title = $newTitle;
        $newQuiz->slug = $newSlug;
        $newQuiz->created_by = Auth::id();
        $newQuiz->is_published = false;
        $newQuiz->save();

        foreach ($quiz->questions as $question) {
            $newQuestion = $question->replicate();
            $newQuestion->quiz_id = $newQuiz->id;
            $newQuestion->save();

            foreach ($question->options as $option) {
                $newOption = $option->replicate();
                $newOption->question_id = $newQuestion->id;
                $newOption->save();
            }
        }

        $newQuiz->updateTotals();

        return redirect()->route('admin.quizzes.edit', $newQuiz)
            ->with('success', 'Quiz duplicated successfully!');
    }
    
    public function togglePublish(Quiz $quiz)
    {
        // Check if user owns this quiz or is Master Admin
        if (!Auth::user()->isMasterAdmin() && $quiz->created_by !== Auth::id()) {
            abort(403, 'You do not have permission to modify this quiz.');
        }
        
        $quiz->update(['is_published' => !$quiz->is_published]);
        
        $status = $quiz->is_published ? 'published' : 'hidden';
        return back()->with('success', "Quiz has been {$status}.");
    }
    
/**
 * Show quiz participants for admin
 */
public function participants(Quiz $quiz)
{
    // Check permission
    if (!Auth::user()->isMasterAdmin() && $quiz->created_by !== Auth::id()) {
        abort(403, 'You do not have permission to view participants for this quiz.');
    }
    
    // Get ALL participants from the database (not just from the relationship)
    // Using the QuizParticipant model directly to ensure we get all records
    $participants = \App\Models\QuizParticipant::with('user')
        ->where('quiz_id', $quiz->id)
        ->orderByRaw("FIELD(status, 'taking_quiz', 'joined', 'completed', 'left')")
        ->orderBy('updated_at', 'desc')
        ->get();
    
    // Log for debugging
    \Log::info('Admin participants view', [
        'quiz_id' => $quiz->id,
        'total_participants' => $participants->count(),
        'statuses' => $participants->pluck('status')->toArray()
    ]);
    
    // Get users currently taking the quiz (in_progress attempts)
    $inProgressUsers = QuizAttempt::where('quiz_id', $quiz->id)
        ->where('status', 'in_progress')
        ->pluck('user_id')
        ->toArray();
    
    // Get completed participants count
    $completedParticipants = QuizAttempt::where('quiz_id', $quiz->id)
        ->where('status', 'completed')
        ->distinct('user_id')
        ->count('user_id');
    
    // Calculate active participants (joined + taking_quiz)
    $activeParticipants = $participants->whereIn('status', ['joined', 'taking_quiz'])->count();
    
    // Calculate lobby users
    $lobbyUsers = $participants->where('status', 'joined')
        ->whereNotIn('user_id', $inProgressUsers)
        ->count();
    
    return view('admin.quizzes.participants', compact(
        'quiz', 
        'participants', 
        'activeParticipants', 
        'inProgressUsers',
        'completedParticipants',
        'lobbyUsers'
    ));
}
}