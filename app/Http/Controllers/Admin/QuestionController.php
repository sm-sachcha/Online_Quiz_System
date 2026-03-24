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
        $questions = $quiz->questions()->with('options')->orderBy('order')->get();
        return view('admin.questions.index', compact('quiz', 'questions'));
    }

    public function create(Quiz $quiz)
    {
        return view('admin.questions.create', compact('quiz'));
    }

    public function store(Request $request, Quiz $quiz)
    {
        $request->validate([
            'question_text' => 'required|string|min:5',
            'question_type' => 'required|in:multiple_choice,true_false,single_choice',
            'points' => 'required|integer|min:1|max:100',
            'time_seconds' => 'required|integer|min:10|max:300',
            'explanation' => 'nullable|string',
            'options' => 'required_if:question_type,multiple_choice,single_choice|array|min:2|max:6',
            'options.*.text' => 'required_if:question_type,multiple_choice,single_choice|string|min:1',
            'options.*.is_correct' => 'sometimes|boolean',
            'true_false_correct' => 'required_if:question_type,true_false|in:true,false'
        ]);

        DB::beginTransaction();
        try {
            // Get the next order number
            $order = ($quiz->questions()->max('order') ?? 0) + 1;

            // Create the question
            $question = Question::create([
                'quiz_id' => $quiz->id,
                'question_text' => $request->question_text,
                'question_type' => $request->question_type,
                'points' => $request->points,
                'time_seconds' => $request->time_seconds,
                'order' => $order,
                'explanation' => $request->explanation,
                'created_by' => Auth::id(),
                'is_active' => true
            ]);

            // Create options based on question type
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
                        'is_correct' => isset($optionData['is_correct']),
                        'order' => $index + 1
                    ]);
                }
            }

            // Update quiz totals
            $quiz->updateTotals();

            DB::commit();

            return redirect()->route('admin.quizzes.questions.index', $quiz)
                ->with('success', 'Question created successfully! Points: ' . $request->points . ', Time: ' . $request->time_seconds . ' seconds');

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

        $question->load('options');
        return view('admin.questions.edit', compact('quiz', 'question'));
    }

    public function update(Request $request, Quiz $quiz, Question $question)
    {
        if ($question->quiz_id !== $quiz->id) {
            abort(404);
        }

        $request->validate([
            'question_text' => 'required|string|min:5',
            'points' => 'required|integer|min:1|max:100',
            'time_seconds' => 'required|integer|min:10|max:300',
            'explanation' => 'nullable|string',
            'options' => 'required|array|min:2',
            'options.*.id' => 'nullable|exists:options,id',
            'options.*.text' => 'required|string|min:1',
            'options.*.is_correct' => 'sometimes|boolean'
        ]);

        DB::beginTransaction();
        try {
            // Update the question
            $question->update([
                'question_text' => $request->question_text,
                'points' => $request->points,
                'time_seconds' => $request->time_seconds,
                'explanation' => $request->explanation
            ]);

            $existingOptionIds = $question->options()->pluck('id')->toArray();
            $submittedOptionIds = [];

            // Update or create options
            foreach ($request->options as $index => $optionData) {
                if (isset($optionData['id'])) {
                    $option = Option::find($optionData['id']);
                    if ($option) {
                        $option->update([
                            'option_text' => $optionData['text'],
                            'is_correct' => isset($optionData['is_correct']),
                            'order' => $index + 1
                        ]);
                        $submittedOptionIds[] = $option->id;
                    }
                } else {
                    $option = Option::create([
                        'question_id' => $question->id,
                        'option_text' => $optionData['text'],
                        'is_correct' => isset($optionData['is_correct']),
                        'order' => $index + 1
                    ]);
                    $submittedOptionIds[] = $option->id;
                }
            }

            // Delete options that were removed
            $optionsToDelete = array_diff($existingOptionIds, $submittedOptionIds);
            if (!empty($optionsToDelete)) {
                Option::whereIn('id', $optionsToDelete)->delete();
            }

            // Update quiz totals
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
            abort(404);
        }

        DB::beginTransaction();
        try {
            // Delete the question (options will cascade delete)
            $question->delete();

            // Reorder remaining questions
            $remainingQuestions = $quiz->questions()->orderBy('order')->get();
            foreach ($remainingQuestions as $index => $q) {
                $q->update(['order' => $index + 1]);
            }

            // Update quiz totals
            $quiz->updateTotals();

            DB::commit();

            return redirect()->route('admin.quizzes.questions.index', $quiz)
                ->with('success', 'Question deleted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete question: ' . $e->getMessage());
        }
    }

    public function reorder(Request $request, Quiz $quiz)
    {
        $request->validate([
            'questions' => 'required|array',
            'questions.*.id' => 'required|exists:questions,id',
            'questions.*.order' => 'required|integer|min:1'
        ]);

        foreach ($request->questions as $questionData) {
            Question::where('id', $questionData['id'])
                ->where('quiz_id', $quiz->id)
                ->update(['order' => $questionData['order']]);
        }

        return response()->json(['success' => true]);
    }
}