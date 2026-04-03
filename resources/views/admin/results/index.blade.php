@extends('layouts.admin')

@section('title', 'Quiz Results')

@section('content')
<style>
    /* ── Avatars ── */
    .avatar-sm {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 14px;
        flex-shrink: 0;
    }
    .avatar-guest {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    }

    /* ── Badges ── */
    .guest-badge {
        background-color: #17a2b8;
        color: white;
        font-size: 10px;
        padding: 2px 7px;
        border-radius: 10px;
        margin-left: 4px;
        vertical-align: middle;
    }
    .badge-passed {
        background-color: #28a745;
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        white-space: nowrap;
    }
    .badge-failed {
        background-color: #dc3545;
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        white-space: nowrap;
    }
    .badge-in-progress {
        background-color: #ffc107;
        color: #856404;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        white-space: nowrap;
    }
    .attempt-pill {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        background-color: #e9ecef;
        color: #495057;
        font-size: 12px;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 20px;
        white-space: nowrap;
    }

    /* ── Stats cards ── */
    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        transition: transform 0.25s;
    }
    .stats-card:hover { transform: translateY(-4px); }
    .stats-card h3    { font-size: 2rem; font-weight: 700; margin-bottom: 4px; }
    .stats-card p     { margin: 0; opacity: .85; font-size: 14px; }

    /* ── Filter section ── */
    .filter-section {
        background-color: #f8f9fa;
        padding: 16px;
        border-radius: 10px;
        margin-bottom: 24px;
        border: 1px solid #e9ecef;
    }

    /* ── Buttons ── */
    .export-btn, .filter-btn, .reset-btn {
        border: none;
        padding: 8px 20px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .export-btn  { background-color: #28a745; color: white; }
    .export-btn:hover  { background-color: #218838; transform: scale(1.02); color: white; }
    .filter-btn  { background-color: #007bff; color: white; }
    .filter-btn:hover  { background-color: #0056b3; color: white; }
    .reset-btn   { background-color: #6c757d; color: white; }
    .reset-btn:hover   { background-color: #5a6268; color: white; }

    /* ── Table ── */
    #resultsTable thead th {
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #6c757d;
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
        white-space: nowrap;
    }
    #resultsTable tbody tr {
        transition: background 0.15s, transform 0.15s;
    }
    #resultsTable tbody tr:hover {
        background-color: #f0f4ff;
        transform: translateX(2px);
    }

    /* ── Progress bar ── */
    .progress { border-radius: 20px; background-color: #e9ecef; }
    .progress-bar { border-radius: 20px; font-size: 11px; font-weight: 600; }

    /* ── Pagination wrapper ── */
    .pg-wrapper {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: .75rem;
        padding: 14px 20px;
        border-top: 1px solid #dee2e6;
    }
    .pg-info-text {
        font-size: 13px;
        color: #6c757d;
    }
    .pg-info-text strong { color: #212529; font-weight: 600; }

    /* ── Custom pagination nav ── */
    .pg-nav { display: flex; align-items: center; gap: 4px; }
    .pg-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 34px;
        height: 34px;
        padding: 0 6px;
        border: 1px solid #dee2e6;
        border-radius: 7px;
        background: #fff;
        color: #212529;
        font-size: 13px;
        text-decoration: none;
        cursor: pointer;
        transition: background 0.15s, border-color 0.15s, color 0.15s;
        line-height: 1;
    }
    .pg-btn:hover { background: #f0f4ff; border-color: #b3c6ff; color: #212529; }
    .pg-btn.pg-active {
        background: #212529;
        color: #fff;
        border-color: #212529;
        font-weight: 600;
        pointer-events: none;
    }
    .pg-btn.pg-disabled { opacity: .35; pointer-events: none; }
    .pg-ellipsis {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 28px;
        height: 34px;
        font-size: 13px;
        color: #adb5bd;
        user-select: none;
    }
</style>

{{-- ══════════════════════════════════════
     PAGE HEADER
══════════════════════════════════════ --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2 class="mb-1"><i class="fas fa-chart-line me-2"></i>Quiz Results</h2>
                <p class="text-muted mb-0" style="font-size:14px;">
                    Best result per participant — showing {{ $attempts->total() }} unique {{ Str::plural('entry', $attempts->total()) }}
                </p>
            </div>
            <button class="export-btn" id="exportBtn">
                <i class="fas fa-download"></i> Export CSV
            </button>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════
     STATS CARDS
══════════════════════════════════════ --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stats-card">
            <h3>{{ $attempts->total() }}</h3>
            <p>Unique Participants</p>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg,#28a745 0%,#20c997 100%);">
            <h3>{{ $attempts->getCollection()->where('result.passed', true)->count() }}</h3>
            <p>Passed (this page)</p>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg,#dc3545 0%,#c82333 100%);">
            <h3>{{ $attempts->getCollection()->where('result.passed', false)->count() }}</h3>
            <p>Failed (this page)</p>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stats-card" style="background: linear-gradient(135deg,#17a2b8 0%,#138496 100%);">
            <h3>{{ $attempts->getCollection()->avg('score') ? number_format($attempts->getCollection()->avg('score'), 1) : '—' }}</h3>
            <p>Avg Score (this page)</p>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════
     FILTERS
══════════════════════════════════════ --}}
<div class="filter-section">
    <form method="GET" id="filterForm" class="row g-3">
        <div class="col-md-3">
            <label for="quiz_id" class="form-label fw-semibold" style="font-size:13px;">Quiz</label>
            <select name="quiz_id" id="quiz_id" class="form-select form-select-sm">
                <option value="">All Quizzes</option>
                @foreach($quizzes as $quiz)
                    <option value="{{ $quiz->id }}" {{ request('quiz_id') == $quiz->id ? 'selected' : '' }}>
                        {{ $quiz->title }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="user_id" class="form-label fw-semibold" style="font-size:13px;">Participant</label>
            <select name="user_id" id="user_id" class="form-select form-select-sm">
                <option value="">All Participants</option>
                <option value="guest" {{ request('user_id') === 'guest' ? 'selected' : '' }}>Instant / Guest</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                        {{ $u->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label for="status" class="form-label fw-semibold" style="font-size:13px;">Status</label>
            <select name="status" id="status" class="form-select form-select-sm">
                <option value="">All</option>
                <option value="passed"      {{ request('status') === 'passed'      ? 'selected' : '' }}>Passed</option>
                <option value="failed"      {{ request('status') === 'failed'      ? 'selected' : '' }}>Failed</option>
                <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
            </select>
        </div>
        <div class="col-md-2">
            <label for="date_from" class="form-label fw-semibold" style="font-size:13px;">From</label>
            <input type="date" name="date_from" id="date_from" class="form-control form-control-sm"
                   value="{{ request('date_from') }}">
        </div>
        <div class="col-md-2">
            <label for="date_to" class="form-label fw-semibold" style="font-size:13px;">To</label>
            <input type="date" name="date_to" id="date_to" class="form-control form-control-sm"
                   value="{{ request('date_to') }}">
        </div>
        <div class="col-12 d-flex gap-2 flex-wrap">
            <button type="submit" class="filter-btn">
                <i class="fas fa-filter"></i> Apply Filters
            </button>
            <a href="{{ route('admin.results.index') }}" class="reset-btn">
                <i class="fas fa-redo"></i> Reset
            </a>
        </div>
    </form>
</div>

{{-- ══════════════════════════════════════
     RESULTS TABLE
══════════════════════════════════════ --}}
<div class="card shadow-sm border-0">
    <div class="card-header bg-primary text-white d-flex align-items-center gap-2">
        <i class="fas fa-list"></i>
        <h5 class="mb-0">Quiz Attempts — Best Results</h5>
    </div>

    <div class="card-body p-0">
        @if($attempts->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="resultsTable">
                    <thead class="table-light">
                        <tr>
                            <th width="45">#</th>
                            <th>Participant</th>
                            <th style="width:90px;" class="text-center">Attempts</th>
                            <th>Quiz</th>
                            <th>Score</th>
                            <th style="width:130px;">Progress</th>
                            <th>Correct / Wrong</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th width="80" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($attempts as $index => $attempt)
                            @php
                                $isGuest     = is_null($attempt->user_id);
                                $userName    = $isGuest
                                    ? ($attempt->participant->guest_name ?? 'Guest User')
                                    : ($attempt->user->name ?? 'Unknown User');
                                $userEmail   = $isGuest ? 'Guest' : ($attempt->user->email ?? 'N/A');
                                $avatarLetter = strtoupper(substr($userName, 0, 1) ?: '?');
                                $avatarClass  = $isGuest ? 'avatar-guest' : '';

                                $percentage = $attempt->quiz->total_points > 0
                                    ? round(($attempt->score / $attempt->quiz->total_points) * 100, 1)
                                    : 0;

                                $passingScore = $attempt->quiz->passing_score ?? 50;
                                $barClass     = $percentage >= $passingScore ? 'bg-success' : 'bg-danger';

                                // Status badge
                                if ($attempt->status === 'completed') {
                                    if ($attempt->result && $attempt->result->passed) {
                                        $statusClass = 'badge-passed';
                                        $statusText  = 'Passed';
                                    } else {
                                        $statusClass = 'badge-failed';
                                        $statusText  = 'Failed';
                                    }
                                } elseif ($attempt->status === 'in_progress') {
                                    $statusClass = 'badge-in-progress';
                                    $statusText  = 'In Progress';
                                } else {
                                    $statusClass = 'badge-failed';
                                    $statusText  = ucfirst($attempt->status);
                                }

                                $attemptCount = $attempt->attempt_count ?? 1;
                            @endphp
                            <tr>
                                {{-- # --}}
                                <td class="text-center text-muted" style="font-size:13px;">
                                    {{ $attempts->firstItem() + $index }}
                                </td>

                                {{-- Participant --}}
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-sm {{ $avatarClass }}">{{ $avatarLetter }}</div>
                                        <div>
                                            <div class="fw-semibold" style="font-size:14px; line-height:1.3;">
                                                {{ $userName }}
                                                @if($isGuest)
                                                    <span class="guest-badge">Guest</span>
                                                @endif
                                            </div>
                                            <div class="text-muted" style="font-size:12px;">{{ $userEmail }}</div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Attempts --}}
                                <td class="text-center">
                                    <span class="attempt-pill">
                                        <i class="fas fa-redo-alt" style="font-size:10px;"></i>
                                        {{ $attemptCount }}
                                    </span>
                                    <div class="text-muted mt-1" style="font-size:11px;">
                                        {{ $attemptCount == 1 ? 'attempt' : 'attempts' }}
                                    </div>
                                </td>

                                {{-- Quiz --}}
                                <td>
                                    <div class="fw-semibold" style="font-size:14px;">
                                        {{ Str::limit($attempt->quiz->title, 38) }}
                                    </div>
                                    <div class="text-muted" style="font-size:12px;">
                                        {{ $attempt->quiz->category->name ?? 'Uncategorized' }}
                                    </div>
                                </td>

                                {{-- Score --}}
                                <td>
                                    <span class="fw-bold" style="font-size:15px;">{{ $attempt->score }}</span>
                                    <span class="text-muted" style="font-size:12px;">
                                        / {{ $attempt->quiz->total_points }} pts
                                    </span>
                                </td>

                                {{-- Progress bar --}}
                                <td>
                                    <div class="progress" style="height:22px; width:110px;">
                                        <div class="progress-bar {{ $barClass }}"
                                             role="progressbar"
                                             style="width:{{ $percentage }}%"
                                             aria-valuenow="{{ $percentage }}"
                                             aria-valuemin="0"
                                             aria-valuemax="100">
                                            {{ $percentage }}%
                                        </div>
                                    </div>
                                </td>

                                {{-- Correct / Wrong / Total --}}
                                <td style="white-space:nowrap;">
                                    <span class="text-success fw-semibold">{{ $attempt->correct_answers }}</span>
                                    <span class="text-muted mx-1">/</span>
                                    <span class="text-danger fw-semibold">{{ $attempt->incorrect_answers }}</span>
                                    <div class="text-muted" style="font-size:11px;">
                                        of {{ $attempt->total_questions }} questions
                                    </div>
                                </td>

                                {{-- Status --}}
                                <td>
                                    <span class="{{ $statusClass }}">{{ $statusText }}</span>
                                </td>

                                {{-- Date --}}
                                <td style="white-space:nowrap;">
                                    <div style="font-size:13px;">
                                        <i class="far fa-calendar-alt text-muted me-1"></i>
                                        {{ $attempt->created_at->format('M d, Y') }}
                                    </div>
                                    <div class="text-muted" style="font-size:12px;">
                                        {{ $attempt->created_at->format('h:i A') }}
                                    </div>
                                </td>

                                {{-- Action --}}
                                <td class="text-center">
                                    <a href="{{ route('admin.results.show', $attempt) }}"
                                       class="btn btn-sm btn-info"
                                       title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- ── Pagination ── --}}
            @if($attempts->hasPages())
            <div class="pg-wrapper">
                {{-- Result count text --}}
                <p class="pg-info-text mb-0">
                    Showing
                    <strong>{{ $attempts->firstItem() }}</strong>–<strong>{{ $attempts->lastItem() }}</strong>
                    of <strong>{{ $attempts->total() }}</strong> results
                </p>

                {{-- Pagination nav --}}
                <nav class="pg-nav" aria-label="Pagination">
                    {{-- Previous --}}
                    @if($attempts->onFirstPage())
                        <span class="pg-btn pg-disabled" aria-disabled="true">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                        </span>
                    @else
                        <a href="{{ $attempts->previousPageUrl() }}" class="pg-btn" aria-label="Previous">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                        </a>
                    @endif

                    {{-- Page numbers --}}
                    @foreach($attempts->getUrlRange(1, $attempts->lastPage()) as $page => $url)
                        @if($page == $attempts->currentPage())
                            <span class="pg-btn pg-active" aria-current="page">{{ $page }}</span>
                        @elseif(
                            $page == 1 ||
                            $page == $attempts->lastPage() ||
                            abs($page - $attempts->currentPage()) <= 1
                        )
                            <a href="{{ $url }}" class="pg-btn">{{ $page }}</a>
                        @elseif(abs($page - $attempts->currentPage()) == 2)
                            <span class="pg-ellipsis">…</span>
                        @endif
                    @endforeach

                    {{-- Next --}}
                    @if($attempts->hasMorePages())
                        <a href="{{ $attempts->nextPageUrl() }}" class="pg-btn" aria-label="Next">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                        </a>
                    @else
                        <span class="pg-btn pg-disabled" aria-disabled="true">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                        </span>
                    @endif
                </nav>
            </div>
            @endif

        @else
            <div class="text-center py-5">
                <i class="fas fa-chart-line fa-4x text-muted mb-3 d-block"></i>
                <h5>No Results Found</h5>
                <p class="text-muted">No completed quiz attempts match your filters.</p>
                <a href="{{ route('admin.results.index') }}" class="btn btn-primary">
                    <i class="fas fa-redo me-1"></i> Clear Filters
                </a>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function () {

    // Export with current query string
    $('#exportBtn').on('click', function () {
        const params = new URLSearchParams(window.location.search);
        params.set('export', 'csv');
        window.location.href = '{{ route("admin.results.export") }}?' + params.toString();
    });

    // Auto-submit dropdowns
    $('#quiz_id, #user_id, #status').on('change', function () {
        $('#filterForm').submit();
    });

    // Date range validation + auto-submit
    $('#date_from, #date_to').on('change', function () {
        const from = $('#date_from').val();
        const to   = $('#date_to').val();
        if (from && to && from > to) {
            alert('From date cannot be after To date.');
            $(this).val('');
            return;
        }
        if (from || to) $('#filterForm').submit();
    });

});
</script>
@endpush
@endsection