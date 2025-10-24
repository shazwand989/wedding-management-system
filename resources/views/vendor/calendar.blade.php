@extends('layouts.vendor')
@section('title', 'Calendar')
@section('page-title', 'Availability Calendar')
@section('breadcrumb')
    <li class="breadcrumb-item active">Calendar</li>
@endsection
@section('content')
<div class="card">
    <div class="card-header">
        <h5>My Calendar</h5>
        <button class="btn btn-sm btn-gold" data-bs-toggle="modal" data-bs-target="#addEventModal"><i class="fas fa-plus"></i> Block Date</button>
    </div>
    <div class="card-body">
        <div id="calendar"></div>
    </div>
</div>

<!-- Block Date Modal -->
<div class="modal fade" id="addEventModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('vendor.calendar.block') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5>Block Unavailable Date</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Reason</label>
                        <input type="text" name="reason" class="form-control" placeholder="e.g., Fully Booked, On Leave">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-gold">Block Dates</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: [
            @foreach($bookings as $booking)
            {
                title: '{{ $booking->customer->name }} - {{ $booking->package->name }}',
                start: '{{ $booking->event_date }}',
                backgroundColor: '{{ $booking->status == "confirmed" ? "#28a745" : "#ffc107" }}',
                borderColor: '{{ $booking->status == "confirmed" ? "#28a745" : "#ffc107" }}',
                url: '{{ route("vendor.bookings.show", $booking->id) }}'
            },
            @endforeach
            @foreach($blockedDates as $blocked)
            {
                title: 'Unavailable: {{ $blocked->reason }}',
                start: '{{ $blocked->start_date }}',
                end: '{{ $blocked->end_date }}',
                backgroundColor: '#dc3545',
                borderColor: '#dc3545',
                display: 'background'
            },
            @endforeach
        ],
        eventClick: function(info) {
            if(info.event.url) {
                window.open(info.event.url, '_self');
                info.jsEvent.preventDefault();
            }
        }
    });
    calendar.render();
});
</script>
@endpush
@endsection
