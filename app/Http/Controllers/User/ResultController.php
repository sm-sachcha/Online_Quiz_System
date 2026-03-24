<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use App\Services\ResultService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResultController extends Controller
{
    protected $resultService;

    public function __construct(ResultService $resultService)
    {
        $this->resultService = $resultService;
    }

    public function show($quizId, QuizAttempt $attempt)
    {
        if ($attempt->user_id !== Auth::id()) {
            abort(403);
        }

        $result = $attempt->result;
        
        if (!$result) {
            $result = $this->resultService->calculateResult($attempt);
        }

        return view('user.quiz.result', compact('attempt', 'result'));
    }

    public function history()
    {
        $history = $this->resultService->getUserQuizHistory(Auth::id());
        
        return view('user.results.index', compact('history'));
    }

    public function certificate(QuizAttempt $attempt)
    {
        if ($attempt->user_id !== Auth::id()) {
            abort(403);
        }

        $result = $attempt->result;
        
        if (!$result || !$result->passed) {
            abort(404);
        }

        // Return PDF view (you'll need to install barryvdh/laravel-dompdf)
        return view('user.results.certificate', compact('attempt', 'result'));
    }
}