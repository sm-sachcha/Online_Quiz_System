@extends('layouts.admin')

@section('title', 'Result Details - ' . ($attempt->quiz->title ?? 'Quiz Result'))

@section('content')
<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --success-gradient: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        --danger-gradient: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        --warning-gradient: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
        --info-gradient: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    }

    /* Score Circle */
    .score-circle {
        width: 160px;
        height: 160px;
        border-radius: 50%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        background: var(--primary-gradient);
        color: white;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        transition: transform 0.3s ease;
    }
    .score-circle:hover {
        transform: scale(1.05);
    }
    .score-number {
        font-size: 48px;
        font-weight: bold;
        line-height: 1;
    }
    .score-label {
        font-size: 14px;
        opacity: 0.9;
    }

    /* Cards */
    .result-card {
        border: none;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: 100%;
    }
    .result-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.12);
    }
    .card-header {
        border: none;
        padding: 16px 20px;
    }

    /* Avatar */
    .avatar-lg {
        width: 100px;
        height: 100px;
        background: var(--primary-gradient);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 42px;
        font-weight: bold;
        margin: 0 auto;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        transition: transform 0.3s ease;
    }
    .avatar-lg:hover {
        transform: scale(1.05);
    }
    .avatar-guest {
        background: var(--success-gradient);
    }

    /* Badges */
    .guest-badge {
        background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
        color: white;
        padding: 6px 14px;
        border-radius: 25px;
        font-size: 12px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .category-badge {
        background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
        color: white;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .category-badge-public {
        background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    }

    /* Stats Box */
    .stat-box {
        text-align: center;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 12px;
        transition: all 0.3s ease;
        border: 1px solid #e9ecef;
    }
    .stat-box:hover {
        background: white;
        border-color: #667eea;
        transform: translateY(-3px);
    }
    .stat-value {
        font-size: 28px;
        font-weight: bold;
        margin-bottom: 5px;
    }
    .stat-label {
        font-size: 13px;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Multiple Choice Answer Display */
    .multiple-choice-answers {
        list-style: none;
        padding-left: 0;
        margin-bottom: 0;
    }
    .multiple-choice-answers li {
        padding: 8px 12px;
        margin-bottom: 8px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .answer-correct-highlight {
        background-color: #d4edda;
        border-left: 3px solid #28a745;
    }
    .answer-incorrect-highlight {
        background-color: #f8d7da;
        border-left: 3px solid #dc3545;
    }
    .answer-selected {
        background-color: #e7f3ff;
        border-left: 3px solid #007bff;
    }
    .check-icon {
        width: 20px;
        text-align: center;
    }

    /* Answer Items */
    .answer-item {
        border-radius: 12px;
        margin-bottom: 16px;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    .answer-correct {
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        border-left: 4px solid #28a745;
    }
    .answer-incorrect {
        background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
        border-left: 4px solid #dc3545;
    }
    .answer-header {
        padding: 15px 20px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .answer-header:hover {
        background: rgba(0,0,0,0.02);
    }
    .answer-body {
        padding: 20px;
        border-top: 1px solid rgba(0,0,0,0.05);
        background: white;
    }

    /* Question Accordion */
    .accordion-item {
        border: none;
        margin-bottom: 12px;
        border-radius: 12px !important;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .accordion-button {
        background: white;
        padding: 16px 20px;
        font-weight: 500;
    }
    .accordion-button:not(.collapsed) {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        color: #495057;
    }
    .accordion-button:focus {
        box-shadow: none;
        border-color: rgba(0,0,0,0.125);
    }

    /* Progress Bars */
    .progress-custom {
        height: 8px;
        border-radius: 4px;
        background-color: #e9ecef;
    }
    .progress-bar-custom {
        border-radius: 4px;
        transition: width 0.6s ease;
    }

    /* Metrics Grid */
    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-top: 15px;
    }
    .metric-card {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 12px;
        text-align: center;
        transition: all 0.3s ease;
    }
    .metric-card:hover {
        background: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .metric-value {
        font-size: 20px;
        font-weight: bold;
    }
    .metric-label {
        font-size: 11px;
        color: #6c757d;
        text-transform: uppercase;
    }

    /* Timeline */
    .timeline {
        position: relative;
        padding-left: 30px;
    }
    .timeline-item {
        position: relative;
        padding-bottom: 20px;
    }
    .timeline-item:before {
        content: '';
        position: absolute;
        left: -22px;
        top: 0;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #667eea;
        border: 2px solid white;
        box-shadow: 0 0 0 2px #667eea;
    }
    .timeline-item:after {
        content: '';
        position: absolute;
        left: -17px;
        top: 12px;
        width: 2px;
        height: calc(100% - 12px);
        background: #dee2e6;
    }
    .timeline-item:last-child:after {
        display: none;
    }
    .timeline-date {
        font-size: 12px;
        color: #6c757d;
        margin-bottom: 4px;
    }
    .timeline-title {
        font-weight: 600;
        margin-bottom: 4px;
    }

    /* Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .animate-fadeInUp {
        animation: fadeInUp 0.5s ease forwards;
    }

    /* Option List Styling */
    .option-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .option-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        border-radius: 8px;
        transition: all 0.2s ease;
    }
    .option-correct {
        background-color: #d4edda;
        border: 1px solid #28a745;
    }
    .option-incorrect-selected {
        background-color: #f8d7da;
        border: 1px solid #dc3545;
    }
    .option-selected {
        background-color: #e7f3ff;
        border: 1px solid #007bff;
    }
    .option-icon {
        width: 28px;
        text-align: center;
    }
    .option-text {
        flex: 1;
    }
</style>

<div class="row mb-4 animate-fadeInUp">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="mb-1"><i class="fas fa-chart-line text-primary me-2"></i>Result Details</h2>
                <p class="text-muted mb-0">Detailed analysis of quiz attempt</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.results.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Results
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- User Information Card -->
    <div class="col-md-4 animate-fadeInUp" style="animation-delay: 0.1s">
        <div class="card result-card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i>Participant Information</h5>
            </div>
            <div class="card-body text-center">
                @php
                    $isGuest = is_null($attempt->user_id);
                    $participantName = $isGuest ? ($attempt->participant ? $attempt->participant->guest_name : 'Guest User') : ($attempt->user->name ?? 'Unknown');
                    $participantEmail = $isGuest ? 'Guest User' : ($attempt->user->email ?? 'N/A');
                    $avatarLetter = strtoupper(substr($participantName, 0, 1));
                    $avatarClass = $isGuest ? 'avatar-guest' : '';
                @endphp
                
                <div class="avatar-lg mb-3 {{ $avatarClass }}">
                    {{ $avatarLetter }}
                </div>
                
                <h4 class="mb-2">{{ $participantName }}</h4>
                
                @if($isGuest)
                    <span class="guest-badge mb-3">
                        <i class="fas fa-user-friends"></i> Guest Participant
                    </span>
                    <p class="text-muted small">
                        <i class="fas fa-info-circle"></i> This user took the quiz without logging in
                    </p>
                @else
                    <p class="text-muted mb-3">{{ $participantEmail }}</p>
                @endif
                
                <hr class="my-3">
                
                <div class="row g-3">
                    <div class="col-6">
                        <div class="metric-card">
                            <div class="metric-value text-primary">{{ $isGuest ? 'N/A' : ($attempt->user->profile->total_points ?? 0) }}</div>
                            <div class="metric-label">Total Points</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="metric-card">
                            <div class="metric-value text-info">{{ $isGuest ? 'N/A' : $attempt->user->quizAttempts()->count() }}</div>
                            <div class="metric-label">Quizzes Taken</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quiz Information Card -->
    <div class="col-md-4 animate-fadeInUp" style="animation-delay: 0.2s">
        <div class="card result-card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-question-circle me-2"></i>Quiz Information</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted text-uppercase d-block mb-1">Quiz Title</small>
                    <h5 class="mb-0">{{ $attempt->quiz->title }}</h5>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted text-uppercase d-block mb-1">Category</small>
                    @if($attempt->quiz->category)
                        <span class="category-badge" style="background: linear-gradient(135deg, {{ $attempt->quiz->category->color ?? '#6c757d' }} 0%, #5a6268 100%);">
                            <i class="{{ $attempt->quiz->category->icon ?? 'fas fa-tag' }}"></i>
                            {{ $attempt->quiz->category->name }}
                        </span>
                    @else
                        <span class="category-badge category-badge-public">
                            <i class="fas fa-globe"></i> Instant Quiz
                        </span>
                    @endif
                </div>
                
                <hr class="my-3">
                
                <div class="metrics-grid">
                    <div class="metric-card">
                        <div class="metric-value">{{ $attempt->quiz->duration_minutes }} min</div>
                        <div class="metric-label">Duration</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value">{{ $attempt->total_questions }}</div>
                        <div class="metric-label">Questions</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value">{{ $attempt->quiz->total_points }}</div>
                        <div class="metric-label">Total Points</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value">{{ $attempt->quiz->passing_score }}%</div>
                        <div class="metric-label">Passing Score</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Result Information Card -->
    <div class="col-md-4 animate-fadeInUp" style="animation-delay: 0.3s">
        <div class="card result-card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Result Information</h5>
            </div>
            <div class="card-body text-center">
                <div class="score-circle mb-4">
                    <div class="score-number">{{ $percentage }}%</div>
                    <div class="score-label">Score</div>
                </div>
                
                <h3 class="mb-3">{{ $attempt->score }} / {{ $attempt->quiz->total_points }} points</h3>
                
                <div class="progress-custom mb-4">
                    <div class="progress-bar-custom bg-success" style="width: {{ $percentage }}%; height: 8px;"></div>
                </div>
                
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <div class="metric-card">
                            <div class="metric-value">
                                @if(isset($userRank) && $userRank)
                                    @if($userRank == 1) 🥇
                                    @elseif($userRank == 2) 🥈
                                    @elseif($userRank == 3) 🥉
                                    @else #{{ $userRank }}
                                    @endif
                                @else N/A
                                @endif
                            </div>
                            <div class="metric-label">Rank</div>
                            @if(isset($totalParticipants))
                                <small class="text-muted">out of {{ $totalParticipants }}</small>
                            @endif
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="metric-card">
                            <div class="metric-value">
                                @if($attempt->result && $attempt->result->passed)
                                    <span class="text-success"><i class="fas fa-check-circle"></i> Passed</span>
                                @else
                                    <span class="text-danger"><i class="fas fa-times-circle"></i> Failed</span>
                                @endif
                            </div>
                            <div class="metric-label">Status</div>
                        </div>
                    </div>
                </div>
                
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-date">{{ $attempt->started_at->format('M d, Y') }}</div>
                        <div class="timeline-title">Started</div>
                        <div class="text-muted small">{{ $attempt->started_at->format('h:i A') }}</div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-date">{{ $attempt->ended_at->format('M d, Y') }}</div>
                        <div class="timeline-title">Completed</div>
                        <div class="text-muted small">{{ $attempt->ended_at->format('h:i A') }}</div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-date">Duration</div>
                        <div class="timeline-title">Time Taken</div>
                        <div class="text-muted small">{{ gmdate("i:s", $timeTaken) }} minutes</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Row -->
<div class="row g-4 mt-2">
    <div class="col-md-6 animate-fadeInUp" style="animation-delay: 0.4s">
        <div class="card result-card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Performance Summary</h5>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-6">
                        <div class="text-center">
                            <div class="stat-value text-success">{{ $attempt->correct_answers }}</div>
                            <div class="stat-label">Correct Answers</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <div class="stat-value text-danger">{{ $attempt->incorrect_answers }}</div>
                            <div class="stat-label">Incorrect Answers</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="text-center mt-2">
                            <div class="stat-value text-primary">{{ round(($attempt->correct_answers / max($attempt->total_questions, 1)) * 100, 1) }}%</div>
                            <div class="stat-label">Accuracy Rate</div>
                            <div class="progress-custom mt-2">
                                <div class="progress-bar-custom bg-primary" style="width: {{ ($attempt->correct_answers / max($attempt->total_questions, 1)) * 100 }}%; height: 8px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 animate-fadeInUp" style="animation-delay: 0.5s">
        <div class="card result-card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Time Analysis</h5>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-6">
                        <div class="text-center">
                            <div class="stat-value">{{ gmdate("i:s", $timeTaken) }}</div>
                            <div class="stat-label">Time Remaining</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center">
                            <div class="stat-value">
                                @php
                                    $avgTime = $attempt->answers->avg('time_taken_seconds') ?? 0;
                                @endphp
                                {{ floor($avgTime / 60) > 0 ? floor($avgTime / 60) . 'm ' : '' }}{{ $avgTime % 60 }}s
                            </div>
                            <div class="stat-label">Avg per Question</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="text-center mt-2">
                            <div class="stat-value text-warning">{{ floor($timeTaken / 60) }} minutes {{ $timeTaken % 60 }} seconds</div>
                            <div class="stat-label">Total Duration</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Question-wise Analysis -->
<div class="card result-card mt-4 animate-fadeInUp" style="animation-delay: 0.6s">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Question-wise Analysis</h5>
    </div>
    <div class="card-body p-0">
        @if($attempt->answers->count() > 0)
            <div class="accordion" id="questionAccordion">
                @foreach($attempt->answers as $index => $answer)
                    @php
                        $question = $answer->question;
                        $isCorrect = $answer->is_correct;
                        $userOption = $answer->option;
                        $correctOption = $question ? $question->options->where('is_correct', true)->first() : null;
                        $showAnswer = $question && $question->show_answer;
                        $questionType = $question ? $question->question_type : 'single_choice';
                        
                        // Decode multiple choice answers
                        $selectedOptions = [];
                        $selectedOptionIds = [];
                        if ($answer->answer_text && ($questionType == 'multiple_choice')) {
                            $selectedOptionIds = json_decode($answer->answer_text, true);
                            if (is_array($selectedOptionIds)) {
                                $selectedOptions = $question->options->whereIn('id', $selectedOptionIds);
                            }
                        }
                        
                        // Get all options for display
                        $allOptions = $question ? $question->options : collect();
                        $correctOptionIds = $allOptions->where('is_correct', true)->pluck('id')->toArray();
                        
                        // Determine if all correct options were selected
                        $allCorrectSelected = $questionType == 'multiple_choice' && $selectedOptionIds && 
                            empty(array_diff($correctOptionIds, $selectedOptionIds)) && 
                            empty(array_diff($selectedOptionIds, $correctOptionIds));
                    @endphp
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button {{ $index > 0 ? 'collapsed' : '' }}" type="button" 
                                    data-bs-toggle="collapse" data-bs-target="#collapse{{ $index }}">
                                <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="badge {{ $isCorrect ? 'bg-success' : 'bg-danger' }}" style="width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                            @if($isCorrect) <i class="fas fa-check"></i> @else <i class="fas fa-times"></i> @endif
                                        </span>
                                        <div>
                                            <strong>Question {{ $index + 1 }}</strong>
                                            <div class="text-muted small">{{ Str::limit($question ? $question->question_text : 'Question not found', 100) }}</div>
                                            @if($questionType == 'multiple_choice')
                                                <span class="badge bg-info mt-1">Multiple Select</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge {{ $isCorrect ? 'bg-success' : 'bg-danger' }} px-3 py-2">
                                            {{ $answer->points_earned }}/{{ $question ? $question->points : 0 }} pts
                                        </span>
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="collapse{{ $index }}" class="accordion-collapse collapse {{ $index == 0 ? 'show' : '' }}" data-bs-parent="#questionAccordion">
                            <div class="accordion-body">
                                @if($question)
                                    <div class="row g-4">
                                        <div class="col-md-12">
                                            <div class="mb-3">
                                                <label class="fw-semibold mb-2">Question</label>
                                                <div class="p-3 bg-light rounded">{{ $question->question_text }}</div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="fw-semibold mb-2">
                                                    @if($questionType == 'multiple_choice')
                                                        Your Selected Answers
                                                    @else
                                                        Your Answer
                                                    @endif
                                                </label>
                                                
                                                @if($questionType == 'multiple_choice')
                                                    <div class="option-list">
                                                        @foreach($allOptions as $option)
                                                            @php
                                                                $isSelected = in_array($option->id, $selectedOptionIds);
                                                                $isCorrectOption = $option->is_correct;
                                                                $optionClass = '';
                                                                if ($isCorrectOption && $isSelected) {
                                                                    $optionClass = 'option-correct';
                                                                } elseif ($isCorrectOption) {
                                                                    $optionClass = 'option-correct';
                                                                } elseif ($isSelected && !$isCorrectOption) {
                                                                    $optionClass = 'option-incorrect-selected';
                                                                }
                                                            @endphp
                                                            <div class="option-item {{ $optionClass }}">
                                                                <div class="option-icon">
                                                                    @if($isCorrectOption)
                                                                        <i class="fas fa-check-circle text-success"></i>
                                                                    @elseif($isSelected)
                                                                        <i class="fas fa-times-circle text-danger"></i>
                                                                    @else
                                                                        <i class="far fa-circle text-muted"></i>
                                                                    @endif
                                                                </div>
                                                                <div class="option-text">
                                                                    {{ $option->option_text }}
                                                                </div>
                                                                @if($isSelected)
                                                                    <span class="badge bg-primary">Selected</span>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    
                                                    @if(!$allCorrectSelected && $correctOptionIds && $showAnswer)
                                                        <div class="mt-3 p-3 bg-success bg-opacity-10 rounded">
                                                            <i class="fas fa-info-circle text-success me-2"></i>
                                                            <strong>Correct answers:</strong> 
                                                            @foreach($allOptions->where('is_correct', true) as $correctOpt)
                                                                <span class="badge bg-success me-1">{{ $correctOpt->option_text }}</span>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                @else
                                                    <div class="p-3 rounded {{ $isCorrect ? 'bg-success bg-opacity-10 border border-success' : 'bg-danger bg-opacity-10 border border-danger' }}">
                                                        <i class="fas {{ $isCorrect ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' }} me-2"></i>
                                                        @if($userOption)
                                                            {{ $userOption->option_text }}
                                                        @elseif($answer->answer_text)
                                                            {{ $answer->answer_text }}
                                                        @else
                                                            <span class="text-warning">Not answered</span>
                                                        @endif
                                                    </div>
                                                    
                                                    @if(!$isCorrect && $correctOption && $showAnswer)
                                                        <div class="mt-3 p-3 bg-success bg-opacity-10 rounded">
                                                            <i class="fas fa-check-circle text-success me-2"></i>
                                                            <strong>Correct Answer:</strong> {{ $correctOption->option_text }}
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>
                                            
                                            <div class="row g-3">
                                                @if($answer->time_taken_seconds)
                                                    <div class="col-md-6">
                                                        <div class="p-3 bg-light rounded text-center">
                                                            <i class="fas fa-clock text-primary me-2"></i>
                                                            <strong>{{ $answer->time_taken_seconds }} seconds</strong>
                                                            <div class="small text-muted">Time Taken</div>
                                                        </div>
                                                    </div>
                                                @endif
                                                <div class="col-md-6">
                                                    <div class="p-3 bg-light rounded text-center">
                                                        <i class="fas fa-star text-warning me-2"></i>
                                                        <strong>{{ $answer->points_earned }} points</strong>
                                                        <div class="small text-muted">Points Earned</div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            @if($question->explanation && $showAnswer)
                                                <div class="alert alert-info mt-3 mb-0">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    <strong>Explanation:</strong> {{ $question->explanation }}
                                                </div>
                                            @endif
                                            
                                            @if(!$showAnswer)
                                                <div class="alert alert-secondary mt-3 mb-0">
                                                    <i class="fas fa-eye-slash me-2"></i>
                                                    <strong>Note:</strong> The correct answer is hidden as per quiz settings.
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <div class="text-center py-4">
                                        <i class="fas fa-exclamation-triangle text-warning fa-2x mb-2"></i>
                                        <p class="text-muted">Question data not found</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                <h5>No Answers Found</h5>
                <p class="text-muted">This attempt has no recorded answers.</p>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    // Animate progress bars on load
    document.addEventListener('DOMContentLoaded', function() {
        const progressBars = document.querySelectorAll('.progress-bar-custom');
        progressBars.forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0';
            setTimeout(() => {
                bar.style.width = width;
            }, 100);
        });
    });
</script>
@endpush
@endsection