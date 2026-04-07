@extends('layouts.app')

@section('title', 'My Quiz Results')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-history"></i> My Quiz History</h4>
            </div>
            <div class="card-body">
                @if($history->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover datatable">
                            <thead>
                                <tr>
                                    <th>Quiz Title</th>
                                    <th>Date</th>
                                    <th>Score</th>
                                    <th>Percentage</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($history as $item)
                                    <tr>
                                        <td><strong>{{ $item['quiz_title'] }}</strong></td>
                                        <td>{{ \Carbon\Carbon::parse($item['started_at'])->format('M d, Y') }}</td>
                                        <td>{{ $item['score'] }}</td>
                                        <td>
                                            <div class="progress" style="height: 25px;">
                                                <div class="progress-bar {{ $item['passed'] ? 'bg-success' : 'bg-warning' }}" 
                                                     role="progressbar" 
                                                     style="width: {{ $item['percentage'] }}%;"
                                                     aria-valuenow="{{ $item['percentage'] }}" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                    {{ $item['percentage'] }}%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($item['passed'])
                                                <span class="badge bg-success"><i class="fas fa-check"></i> Passed</span>
                                            @else
                                                <span class="badge bg-danger"><i class="fas fa-times"></i> Failed</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('user.quiz.result', ['quiz' => $item['quiz_id'], 'attempt' => $item['attempt_id']]) }}" 
                                               class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> View Details
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-chart-line fa-4x text-muted mb-3"></i>
                        <h5>No Quiz Attempts Yet</h5>
                        <p class="text-muted">Start taking quizzes to see your results here!</p>
                        <a href="{{ route('user.dashboard') }}" class="btn btn-primary">
                            <i class="fas fa-play"></i> Take a Quiz
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        $('.datatable').DataTable({
            pageLength: 10,
            responsive: true,
            order: [[1, 'desc']],
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries"
            }
        });
    });
</script>
@endpush
@endsection
