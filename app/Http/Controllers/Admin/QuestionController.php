<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\Option;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuestionController extends Controller
{
    public function index(Quiz $quiz)
    {
        // Check if user owns this quiz or is Master Admin
        if (!Auth::user()->isMasterAdmin() && $quiz->created_by !== Auth::id()) {
            abort(403, 'You do not have permission to view questions for this quiz.');
        }
        
        $questions = $quiz->questions()->with('options')->orderBy('order')->get();
        
        // Debug - add this to check
        \Log::info('Questions index', [
            'quiz_id' => $quiz->id,
            'quiz_title' => $quiz->title,
            'questions_count' => $questions->count()
        ]);
        
        return view('admin.questions.index', compact('quiz', 'questions'));
    }

    public function create(Quiz $quiz)
    {
        // Check if user owns this quiz or is Master Admin
        if (!Auth::user()->isMasterAdmin() && $quiz->created_by !== Auth::id()) {
            abort(403, 'You do not have permission to add questions to this quiz.');
        }
        
        return view('admin.questions.create', compact('quiz'));
    }

    public function store(Request $request, Quiz $quiz)
    {
        // Debug
        \Log::info('Store question request', $request->all());
        
        // Check if user owns this quiz or is Master Admin
        if (!Auth::user()->isMasterAdmin() && $quiz->created_by !== Auth::id()) {
            abort(403, 'You do not have permission to add questions to this quiz.');
        }
        
        $request->validate([
            'question_text' => 'required|string|min:5',
            'question_type' => 'required|in:multiple_choice,true_false,single_choice',
            'points' => 'required|integer|min:1|max:100',
            'time_seconds' => 'required|integer|min:10|max:300',
            'explanation' => 'nullable|string',
            'options' => 'required_if:question_type,multiple_choice,single_choice|array|min:2',
            'options.*.text' => 'required|string',
            'options.*.is_correct' => 'nullable|boolean',
            'true_false_correct' => 'required_if:question_type,true_false|in:true,false'
        ]);

        DB::beginTransaction();
        try {
            $order = ($quiz->questions()->max('order') ?? 0) + 1;

            $question = Question::create([
                'quiz_id' => $quiz->id,
                'question_text' => $request->question_text,
                'question_type' => $request->question_type,
                'points' => $request->points,
                'time_seconds' => $request->time_seconds,
                'order' => $order,
                'explanation' => $request->explanation,
                'created_by' => Auth::id()
            ]);

            if ($request->question_type === 'true_false') {
                Option::create([
                    'question_id' => $question->id,
                    'option_text' => 'True',
                    'is_correct' => $request->true_false_correct === 'true',
                    'order' => 1
                ]);
                Option::create([
                    'question_id' => $question->id,
                    'option_text' => 'False',
                    'is_correct' => $request->true_false_correct === 'false',
                    'order' => 2
                ]);
            } else {
                foreach ($request->options as $index => $optionData) {
                    Option::create([
                        'question_id' => $question->id,
                        'option_text' => $optionData['text'],
                        'is_correct' => isset($optionData['is_correct']) && $optionData['is_correct'] == 1,
                        'order' => $index + 1
                    ]);
                }
            }

            $quiz->updateTotals();

            DB::commit();

            return redirect()->route('admin.quizzes.questions.index', $quiz)
                ->with('success', 'Question created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create question: ' . $e->getMessage());
            return back()->with('error', 'Failed to create question: ' . $e->getMessage())->withInput();
        }
    }

    public function edit(Quiz $quiz, Question $question)
    {
        if ($question->quiz_id !== $quiz->id) {
            abort(404);
        }
        
        // Check if user owns this quiz or is Master Admin
        if (!Auth::user()->isMasterAdmin() && $quiz->created_by !== Auth::id()) {
            abort(403, 'You do not have permission to edit questions for this quiz.');
        }

        $question->load('options');
        return view('admin.questions.edit', compact('quiz', 'question'));
    }

    public function update(Request $request, Quiz $quiz, Question $question)
    {
        if ($question->quiz_id !== $quiz->id) {
            abort(404);
        }
        
        // Check if user owns this quiz or is Master Admin
        if (!Auth::user()->isMasterAdmin() && $quiz->created_by !== Auth::id()) {
            abort(403, 'You do not have permission to update questions for this quiz.');
        }
        
        // ... rest of the update method remains the same ...
    }

    public function destroy(Quiz $quiz, Question $question)
    {
        if ($question->quiz_id !== $quiz->id) {
            abort(404);
        }
        
        // Check if user owns this quiz or is Master Admin
        if (!Auth::user()->isMasterAdmin() && $quiz->created_by !== Auth::id()) {
            abort(403, 'You do not have permission to delete questions for this quiz.');
        }
        
        // ... rest of the destroy method remains the same ...
    }

    public function reorder(Request $request, Quiz $quiz)
    {
        // Check if user owns this quiz or is Master Admin
        if (!Auth::user()->isMasterAdmin() && $quiz->created_by !== Auth::id()) {
            abort(403, 'You do not have permission to reorder questions for this quiz.');
        }
        
        // ... rest of the reorder method remains the same ...
    }
}