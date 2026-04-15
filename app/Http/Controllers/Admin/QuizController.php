<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\Category;
use App\Models\QuizParticipant;
use App\Models\QuizAttempt;
use App\Events\QuizStarted;
use App\Events\QuizEnded;
use App\Events\QuizParticipantsUpdated;
use App\Services\QuizParticipantsPayloadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class QuizController extends Controller
{
    public function __construct(
        private QuizParticipantsPayloadService $quizParticipantsPayloadService
    ) {
    }

    public function index()
    {
        $user = Auth::user();
        
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
            'category_id' => 'nullable|exists:categories,id',
            'duration_minutes' => 'required|integer|min:1|max:480',
            'passing_score' => 'required|integer|min:0|max:100',
            'is_random_questions' => 'boolean',
            'max_attempts' => 'required|integer|min:1|max:10',
            'scheduled_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:scheduled_at',
            'is_published' => 'boolean'
        ]);

        if ($request->category_id) {
            $category = Category::find($request->category_id);
            if (!Auth::user()->isMasterAdmin() && $category->created_by !== Auth::id()) {
                return back()->with('error', 'You do not have permission to use this category.');
            }
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

        $this->updateQuizTotals($quiz);

        return redirect()->route('admin.quizzes.edit', $quiz)
            ->with('success', 'Quiz created successfully! Now add questions.');
    }

    public function show(Quiz $quiz)
    {
        if (!Auth::user()->isMasterAdmin() && $quiz->created_by !== Auth::id()) {
            abort(403, 'You do not have permission to view this quiz.');
        }
        
        $quiz->load('category', 'creator', 'questions.options');
        
        $isWaitingToStart = $quiz->is_published && 
            (!$quiz->scheduled_at || $quiz->scheduled_at <= now()) && 
            $quiz->participants()->where('status', 'joined')->count() > 0;
        
        $stats = [
            'total_questions' => $quiz->questions->count(),
            'total_points' => $quiz->questions->sum('points'),
            'total_attempts' => $quiz->attempts()->count(),
            'average_score' => $quiz->attempts()->avg('score') ?? 0,
            'completion_rate' => $quiz->attempts()->count() > 0 
                ? ($quiz->attempts()->where('status', 'completed')->count() / max($quiz->attempts()->count(), 1)) * 100 
                : 0,
            'participants_waiting' => $quiz->participants()->where('status', 'joined')->count()
        ];

        return view('admin.quizzes.show', compact('quiz', 'stats', 'isWaitingToStart'));
    }

    public function edit(Quiz $quiz)
    {
        if (!Auth::user()->isMasterAdmin() && $quiz->created_by !== Auth::id()) {
            abort(403, 'You do not have permission to edit this quiz.');
        }
        
        $user = Auth::user();
        
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
        if (!Auth::user()->isMasterAdmin() && $quiz->created_by !== Auth::id()) {
            abort(403, 'You do not have permission to update this quiz.');
        }
        
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'duration_minutes' => 'required|integer|min:1|max:480',
            'passing_score' => 'required|integer|min:0|max:100',
            'is_random_questions' => 'boolean',
            'max_attempts' => 'required|integer|min:1|max:10',
            'scheduled_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:scheduled_at',
            'is_published' => 'boolean'
        ]);

        if ($request->category_id) {
            $category = Category::find($request->category_id);
            if (!Auth::user()->isMasterAdmin() && $category->created_by !== Auth::id()) {
                return back()->with('error', 'You do not have permission to use this category.');
            }
        }

        $baseSlug = Str::slug($request->title);
        $slug = $baseSlug;
        $counter = 1;
        
        while (Quiz::where('slug', $slug)->where('id', '!=', $quiz->id)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        $quiz->update([
            'title' => $request->title,
            'slug' => $slug,
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

        $quiz->refresh();
        $this->updateQuizTotals($quiz);

        return redirect()->route('admin.quizzes.index')
            ->with('success', 'Quiz updated successfully.');
    }

    public function destroy(Quiz $quiz)
    {
        if (!Auth::user()->isMasterAdmin() && $quiz->created_by !== Auth::id()) {
            abort(403, 'You do not have permission to delete this quiz.');
        }
        
        $quiz->delete();

        return redirect()->route('admin.quizzes.index')
            ->with('success', 'Quiz deleted successfully.');
    }

    public function duplicate(Quiz $quiz)
    {
        if (!Auth::user()->isMasterAdmin() && $quiz->created_by !== Auth::id()) {
            abort(403, 'You do not have permission to duplicate this quiz.');
        }
        
        $newTitle = $quiz->title . ' (1)';
        $newSlug = Str::slug($newTitle);
        
        $counter = 1;
        while (Quiz::where('slug', $newSlug)->exists()) {
            $newTitle = $quiz->title . ' ( ' . $counter . ')';
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

        $this->updateQuizTotals($newQuiz);

        return redirect()->route('admin.quizzes.edit', $newQuiz)
            ->with('success', 'Quiz duplicated successfully!');
    }
    
    public function togglePublish(Quiz $quiz)
    {
        if (!Auth::user()->isMasterAdmin() && $quiz->created_by !== Auth::id()) {
            abort(403, 'You do not have permission to modify this quiz.');
        }
        
        $quiz->update(['is_published' => !$quiz->is_published]);
        
        $status = $quiz->is_published ? 'published' : 'hidden';
        return back()->with('success', "Quiz has been {$status}.");
    }
    
    /**
     * Start quiz manually (admin)
     */
    public function startQuiz(Quiz $quiz)
    {
        if (!Auth::user()->isMasterAdmin() && $quiz->created_by !== Auth::id()) {
            if (request()->ajax()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403);
        }
        
        if ($quiz->questions()->count() === 0) {
            $message = 'Cannot start quiz without any questions. Please add questions first.';
            if (request()->ajax()) {
                return response()->json(['error' => $message], 422);
            }
            return redirect()->route('admin.quizzes.participants', $quiz)
                ->with('error', $message);
        }

        $joinedParticipants = QuizParticipant::where('quiz_id', $quiz->id)
            ->where('status', 'joined')
            ->count();

        if ($joinedParticipants < 1) {
            $message = 'Cannot start quiz until at least 1 participant joins the lobby.';
            if (request()->ajax()) {
                return response()->json(['error' => $message], 422);
            }
            return redirect()->route('admin.quizzes.participants', $quiz)
                ->with('error', $message);
        }
        
        $quiz->update([
            'is_published' => true,
            'scheduled_at' => now(),
            'ends_at' => now()->addMinutes($quiz->duration_minutes),
            'settings' => array_merge($quiz->settings ?? [], [
                'live_started_at' => now()->toIso8601String(),
            ]),
        ]);
        
        broadcast(new QuizStarted($quiz))->toOthers();
        broadcast(new QuizParticipantsUpdated($quiz, $this->quizParticipantsPayloadService->build($quiz)))->toOthers();
        
        $message = 'Quiz has been started! ' . $joinedParticipants . ' participants have been notified.';
        
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'participants_notified' => $joinedParticipants
            ]);
        }
        
        return redirect()->route('admin.quizzes.participants', $quiz)
            ->with('success', $message);
    }
    
    /**
     * Quit/End quiz manually (admin)
     */
    public function quitQuiz(Quiz $quiz)
    {
        if (!Auth::user()->isMasterAdmin() && $quiz->created_by !== Auth::id()) {
            if (request()->ajax()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            abort(403);
        }
        
        $quiz->update([
            'is_published' => false,
            'ends_at' => now()
        ]);

        broadcast(new QuizEnded($quiz))->toOthers();
        broadcast(new QuizParticipantsUpdated($quiz, $this->quizParticipantsPayloadService->build($quiz)))->toOthers();
        
        Log::info('Quiz ended manually by admin', [
            'quiz_id' => $quiz->id,
            'quiz_title' => $quiz->title,
            'ended_by' => Auth::user()->name
        ]);
        
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Quiz ended successfully!'
            ]);
        }
        
        return redirect()->route('admin.quizzes.participants', $quiz)
            ->with('success', 'Quiz has been ended. Participants can no longer take the quiz.');
    }
    
    /**
     * Get quiz status for AJAX polling
     */
    public function getQuizStatus(Quiz $quiz)
    {
        if (!Auth::user()->isMasterAdmin() && $quiz->created_by !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $isActive = $quiz->is_published && (!$quiz->ends_at || $quiz->ends_at > now());
        $isStarted = $quiz->is_published && $quiz->scheduled_at && $quiz->scheduled_at <= now();
        
        return response()->json([
            'is_active' => $isActive,
            'is_started' => $isStarted,
            'is_published' => $quiz->is_published,
            'scheduled_at' => $quiz->scheduled_at,
            'ends_at' => $quiz->ends_at,
            'duration_minutes' => $quiz->duration_minutes,
            'participants_count' => $quiz->participants()->where('status', 'joined')->count(),
            'has_questions' => $quiz->questions()->count() > 0,
            'questions_count' => $quiz->questions()->count()
        ]);
    }
    
    /**
     * Get quiz participants for admin (JSON) - ONLY ACTIVE LOBBY USERS
     */
    public function getParticipants(Quiz $quiz)
    {
        if (!Auth::user()->isMasterAdmin() && $quiz->created_by !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($this->quizParticipantsPayloadService->build($quiz));
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
    
    $payload = $this->quizParticipantsPayloadService->build($quiz);
    
    return view('admin.quizzes.participants', compact(
        'quiz', 
        'payload'
    ));
}
    
    /**
     * Remove a participant from the lobby
     */
    public function removeParticipant($participantId)
    {
        try {
            $participant = QuizParticipant::find($participantId);
            
            if (!$participant) {
                return response()->json(['success' => false, 'message' => 'Participant not found'], 404);
            }
            
            $quiz = $participant->quiz;
            $participant->delete();
            broadcast(new QuizParticipantsUpdated($quiz, $this->quizParticipantsPayloadService->build($quiz)))->toOthers();
            
            return response()->json(['success' => true, 'message' => 'Participant removed successfully']);
            
        } catch (\Exception $e) {
            Log::error('Error removing participant: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Update quiz totals (points and question count)
     */
    private function updateQuizTotals(Quiz $quiz)
    {
        $totalPoints = $quiz->questions()->sum('points');
        $totalQuestions = $quiz->questions()->count();
        
        $quiz->update([
            'total_points' => $totalPoints,
            'total_questions' => $totalQuestions
        ]);
    }

}
