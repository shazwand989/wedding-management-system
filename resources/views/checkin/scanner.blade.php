@extends('layouts.customer')

@section('title', 'Check-in Scanner')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">QR Code Check-in Scanner</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ route('customer.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('customer.events.index') }}">Events</a></li>
                    <li class="breadcrumb-item active">Check-in Scanner</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6 mx-auto">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Scan Guest QR Code</h3>
                    </div>
                    <div class="card-body text-center">
                        <div id="scannerContainer" style="display: none;">
                            <div id="reader" style="width: 100%;"></div>
                        </div>

                        <div id="manualEntry">
                            <p class="mb-3">Enter QR code manually or use camera to scan</p>

                            <div class="mb-3">
                                <input type="text" class="form-control" id="qrCodeInput"
                                       placeholder="Enter QR Code" autofocus>
                            </div>

                            <button type="button" class="btn btn-gold" onclick="checkInManual()">
                                <i class="fas fa-check"></i> Check In
                            </button>

                            <button type="button" class="btn btn-info" onclick="startScanner()">
                                <i class="fas fa-camera"></i> Use Camera Scanner
                            </button>
                        </div>

                        <div id="result" class="mt-4"></div>
                    </div>
                </div>

                <div id="guestInfo" class="card" style="display: none;">
                    <div class="card-body">
                        <h4 id="guestName"></h4>
                        <p><strong>Email:</strong> <span id="guestEmail"></span></p>
                        <p><strong>Number of Guests:</strong> <span id="numberOfGuests"></span></p>
                        <p><strong>Response:</strong> <span id="responseStatus"></span></p>
                        <p class="text-muted" id="checkInTime"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('styles')
<style>
    #reader {
        border: 2px solid #D4AF37;
        border-radius: 10px;
        overflow: hidden;
    }
    .success-check {
        font-size: 5rem;
        color: #28a745;
    }
    .error-icon {
        font-size: 5rem;
        color: #dc3545;
    }
</style>
@endpush

@push('scripts')
<!-- HTML5 QR Code Scanner -->
<script src="https://unpkg.com/html5-qrcode"></script>

<script>
let html5QrcodeScanner;

function startScanner() {
    $('#scannerContainer').show();
    $('#manualEntry').hide();

    html5QrcodeScanner = new Html5QrcodeScanner(
        "reader",
        {
            fps: 10,
            qrbox: 250,
            aspectRatio: 1.0
        },
        false
    );

    html5QrcodeScanner.render(onScanSuccess, onScanError);
}

function onScanSuccess(decodedText, decodedResult) {
    html5QrcodeScanner.clear();
    $('#scannerContainer').hide();
    $('#manualEntry').show();

    checkIn(decodedText);
}

function onScanError(errorMessage) {
    // Ignore scan errors (they happen frequently during scanning)
}

function checkInManual() {
    const qrCode = $('#qrCodeInput').val().trim();
    if (!qrCode) {
        showError('Please enter a QR code');
        return;
    }
    checkIn(qrCode);
}

function checkIn(qrCode) {
    $('#result').html('<div class="spinner-border text-gold" role="status"></div>');

    $.ajax({
        url: '{{ route("checkin.scan") }}',
        type: 'POST',
        data: {
            qr_code: qrCode,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                showSuccess(response.rsvp);
            } else {
                showError(response.message);
            }
            $('#qrCodeInput').val('');
        },
        error: function(xhr) {
            let message = 'Check-in failed';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            showError(message);
            $('#qrCodeInput').val('');
        }
    });
}

function showSuccess(rsvp) {
    $('#result').html(`
        <div class="success-check">
            <i class="fas fa-check-circle"></i>
        </div>
        <h3 class="text-success">Check-in Successful!</h3>
    `);

    $('#guestName').text(rsvp.guest_name);
    $('#guestEmail').text(rsvp.guest_email);
    $('#numberOfGuests').text(rsvp.number_of_guests);

    let statusBadge = '';
    if (rsvp.response_status === 'yes') {
        statusBadge = '<span class="badge text-bg-success">Attending</span>';
    } else if (rsvp.response_status === 'no') {
        statusBadge = '<span class="badge text-bg-danger">Not Attending</span>';
    } else {
        statusBadge = '<span class="badge text-bg-warning">Maybe</span>';
    }
    $('#responseStatus').html(statusBadge);

    $('#checkInTime').text('Checked in at ' + new Date().toLocaleTimeString());
    $('#guestInfo').fadeIn();

    // Play success sound (optional)
    // var audio = new Audio('/sounds/success.mp3');
    // audio.play();

    setTimeout(function() {
        $('#result').fadeOut();
        $('#guestInfo').fadeOut();
    }, 5000);
}

function showError(message) {
    $('#result').html(`
        <div class="error-icon">
            <i class="fas fa-times-circle"></i>
        </div>
        <h4 class="text-danger">Error</h4>
        <p>${message}</p>
    `);

    $('#guestInfo').hide();

    setTimeout(function() {
        $('#result').fadeOut();
    }, 4000);
}

// Auto-submit on Enter key
$('#qrCodeInput').on('keypress', function(e) {
    if (e.which === 13) {
        checkInManual();
    }
});
</script>
@endpush
