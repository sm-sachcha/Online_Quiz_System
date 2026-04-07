<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use App\Services\AntiCheatService;
use Illuminate\Http\Request;

class AntiCheatController extends Controller
{
    protected $antiCheatService;

    public function __construct(AntiCheatService $antiCheatService)
    {
        $this->antiCheatService = $antiCheatService;
    }

    public function reportTabSwitch(Request $request)
    {
        $request->validate([
            'attempt_id' => 'required|exists:quiz_attempts,id',
            'action' => 'required|in:blur,focus'
        ]);

        $attempt = QuizAttempt::findOrFail($request->attempt_id);

        if ($attempt->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $this->antiCheatService->logCheatingEvent(
            $attempt,
            'tab_switch',
            ['action' => $request->action, 'timestamp' => now()]
        );

        return response()->json(['success' => true]);
    }

    public function reportSuspiciousActivity(Request $request)
    {
        $request->validate([
            'attempt_id' => 'required|exists:quiz_attempts,id',
            'type' => 'required|string',
            'details' => 'required|array'
        ]);

        $attempt = QuizAttempt::findOrFail($request->attempt_id);

        if ($attempt->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $this->antiCheatService->logCheatingEvent(
            $attempt,
            $request->type,
            $request->details
        );

        return response()->json(['success' => true]);
    }

    public function getCheatingStatus(Request $request, QuizAttempt $attempt)
    {
        if ($attempt->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $logs = $attempt->cheating_logs ?? [];
        
        return response()->json([
            'has_cheating_logs' => count($logs) > 0,
            'log_count' => count($logs),
            'is_disqualified' => $attempt->status === 'disqualified'
        ]);
    }
}