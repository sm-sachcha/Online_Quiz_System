<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class QuizController extends Controller
{
    public function index()
    {
        $quizzes = Quiz::with('category', 'creator')
            ->withCount('questions', 'attempts')
            ->latest()
            ->paginate(15);
        
        return view('admin.quizzes.index', compact('quizzes'));
    }

    public function create()
    {
        $categories = Category::where('is_active', true)->get();
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
        $categories = Category::where('is_active', true)->get();
        return view('admin.quizzes.edit', compact('quiz', 'categories'));
    }

    public function update(Request $request, Quiz $quiz)
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
        $quiz->delete();

        return redirect()->route('admin.quizzes.index')
            ->with('success', 'Quiz deleted successfully.');
    }

    public function duplicate(Quiz $quiz)
    {
        $newQuiz = $quiz->replicate();
        $newQuiz->title = $quiz->title . ' (Copy)';
        $newQuiz->slug = Str::slug($newQuiz->title);
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
            ->with('success', 'Quiz duplicated successfully.');
    }
    
    public function togglePublish(Quiz $quiz)
    {
        $quiz->update(['is_published' => !$quiz->is_published]);
        
        $status = $quiz->is_published ? 'published' : 'hidden';
        return back()->with('success', "Quiz has been {$status}.");
    }
    
    public function updateTotals(Quiz $quiz)
    {
        $quiz->updateTotals();
        
        return response()->json([
            'success' => true,
            'total_questions' => $quiz->total_questions,
            'total_points' => $quiz->total_points
        ]);
    }
}