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
        if (!Auth::user()->isMasterAdmin() && $quiz->created_by !== Auth::id()) {
            abort(403, 'You do not have permission to view questions for this quiz.');
        }
        
        $questions = $quiz->questions()->with('options')->orderBy('order')->get();
        
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
        if (!Auth::user()->isMasterAdmin() && $quiz->created_by !== Auth::id()) {
            abort(403, 'You do not have permission to add questions to this quiz.');
        }
        
        $request->validate([
            'question_text' => 'required|string|min:5',
            'question_type' => 'required|in:multiple_choice,true_false,single_choice',
            'points' => 'required|integer|min:1|max:100',
            'time_seconds' => 'required|integer|min:10|max:300',
            'explanation' => 'nullable|string',
            'show_answer' => 'nullable|boolean',
            'options' => 'required_if:question_type,multiple_choice,single_choice|array|min:2',
            'options.*.text' => 'required|string|min:1',
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
                'show_answer' => $request->boolean('show_answer', true),
                'created_by' => Auth::id(),
                'is_active' => true
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
                    $isCorrect = isset($optionData['is_correct']) && ($optionData['is_correct'] == 1 || $optionData['is_correct'] === 'on');
                    
                    Option::create([
                        'question_id' => $question->id,
                        'option_text' => $optionData['text'],
                        'is_correct' => $isCorrect,
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
            return back()->with('error', 'Failed to create question: ' . $e->getMessage())->withInput();
        }
    }

    public function edit(Quiz $quiz, Question $question)
    {
        if ($question->quiz_id !== $quiz->id) {
            abort(404);
        }
        
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
        
        if (!Auth::user()->isMasterAdmin() && $quiz->created_by !== Auth::id()) {
            abort(403, 'You do not have permission to update questions for this quiz.');
        }

        $request->validate([
            'question_text' => 'required|string|min:5',
            'points' => 'required|integer|min:1|max:100',
            'time_seconds' => 'required|integer|min:10|max:300',
            'explanation' => 'nullable|string',
            'show_answer' => 'nullable|boolean',
            'options' => 'required|array|min:2',
            'options.*.id' => 'nullable|exists:options,id',
            'options.*.text' => 'required|string|min:1',
            'options.*.is_correct' => 'nullable|boolean'
        ]);

        DB::beginTransaction();
        try {
            $question->update([
                'question_text' => $request->question_text,
                'points' => $request->points,
                'time_seconds' => $request->time_seconds,
                'explanation' => $request->explanation,
                'show_answer' => $request->boolean('show_answer', true),
            ]);

            $existingOptionIds = $question->options()->pluck('id')->toArray();
            $submittedOptionIds = [];

            foreach ($request->options as $index => $optionData) {
                $isCorrect = isset($optionData['is_correct']) && ($optionData['is_correct'] == 1 || $optionData['is_correct'] === 'on');
                
                if (isset($optionData['id'])) {
                    $option = Option::find($optionData['id']);
                    if ($option) {
                        $option->update([
                            'option_text' => $optionData['text'],
                            'is_correct' => $isCorrect,
                            'order' => $index + 1
                        ]);
                        $submittedOptionIds[] = $option->id;
                    }
                } else {
                    $option = Option::create([
                        'question_id' => $question->id,
                        'option_text' => $optionData['text'],
                        'is_correct' => $isCorrect,
                        'order' => $index + 1
                    ]);
                    $submittedOptionIds[] = $option->id;
                }
            }

            $optionsToDelete = array_diff($existingOptionIds, $submittedOptionIds);
            if (!empty($optionsToDelete)) {
                Option::whereIn('id', $optionsToDelete)->delete();
            }

            $quiz->updateTotals();

            DB::commit();

            return redirect()->route('admin.quizzes.questions.index', $quiz)
                ->with('success', 'Question updated successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update question: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(Quiz $quiz, Question $question)
    {
        if ($question->quiz_id !== $quiz->id) {
            abort(404, 'Question not found in this quiz.');
        }
        
        $user = Auth::user();
        if (!$user->isMasterAdmin() && $quiz->created_by !== $user->id) {
            abort(403, 'You do not have permission to delete questions for this quiz.');
        }

        DB::beginTransaction();
        try {
            $quiz->decrement('total_points', $question->points);
            $quiz->decrement('total_questions');
            $question->delete();

            $remainingQuestions = $quiz->questions()->orderBy('order')->get();
            foreach ($remainingQuestions as $index => $q) {
                $q->update(['order' => $index + 1]);
            }

            DB::commit();
            
            return redirect()->route('admin.quizzes.questions.index', $quiz)
                ->with('success', 'Question deleted successfully.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Question deletion error: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to delete question. ' . $e->getMessage());
        }
    }

    public function reorder(Request $request, Quiz $quiz)
    {
        if (!Auth::user()->isMasterAdmin() && $quiz->created_by !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Permission denied'], 403);
        }
        
        $request->validate([
            'questions' => 'required|array',
            'questions.*.id' => 'required|exists:questions,id',
            'questions.*.order' => 'required|integer|min:1'
        ]);
        
        \Log::info('Reorder questions', [
            'quiz_id' => $quiz->id,
            'questions' => $request->questions
        ]);
        
        DB::beginTransaction();
        try {
            foreach ($request->questions as $questionData) {
                $updated = Question::where('id', $questionData['id'])
                    ->where('quiz_id', $quiz->id)
                    ->update(['order' => $questionData['order']]);
                
                if (!$updated) {
                    throw new \Exception("Failed to update question ID: {$questionData['id']}");
                }
            }
            DB::commit();
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to reorder questions: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}