<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RSVP Confirmed - {{ $rsvp->event->title }}</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Source Sans Pro', sans-serif;
            background: linear-gradient(135deg, #F4E4C1 0%, #D4AF37 100%);
            min-height: 100vh;
            padding: 20px 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .success-card {
            max-width: 600px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 40px;
            text-align: center;
        }
        .success-icon {
            font-size: 5rem;
            color: #28a745;
            margin-bottom: 20px;
        }
        .qr-code {
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .btn-gold {
            background: linear-gradient(135deg, #D4AF37 0%, #B8941D 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 1.1rem;
            border-radius: 5px;
        }
        .btn-gold:hover {
            background: linear-gradient(135deg, #B8941D 0%, #A07C1A 100%);
            color: white;
        }
    </style>
</head>
<body>
    <div class="success-card">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>

        <h1 class="mb-3">RSVP Confirmed!</h1>

        @if($rsvp->response_status === 'yes')
            <p class="lead">Thank you, {{ $rsvp->guest_name }}! We're excited to see you at the event.</p>

            <div class="qr-code">
                <h5>Your Check-in QR Code</h5>
                <p class="text-muted">Save this QR code for quick check-in at the venue</p>
                {!! $rsvp->generateQrCode() !!}
                <small class="text-muted d-block mt-2">QR Code: {{ $rsvp->qr_code }}</small>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Number of Guests:</strong> {{ $rsvp->number_of_guests }}
            </div>
        @elseif($rsvp->response_status === 'no')
            <p class="lead">Thank you for letting us know, {{ $rsvp->guest_name }}. We'll miss you!</p>
        @else
            <p class="lead">Thank you for your response, {{ $rsvp->guest_name }}. We hope you can make it!</p>
        @endif

        @if($rsvp->message)
        <div class="alert alert-light mt-3">
            <strong>Your Message:</strong>
            <p class="mb-0">{{ $rsvp->message }}</p>
        </div>
        @endif

        <div class="mt-4">
            <p><strong>Event Details:</strong></p>
            <p>{{ $rsvp->event->title }}<br>
            {{ $rsvp->event->event_date->format('l, F j, Y') }} at {{ $rsvp->event->event_time }}<br>
            {{ $rsvp->event->venue_name }}</p>
        </div>

        <a href="{{ route('invitation.show', $rsvp->event->invitation_code) }}" class="btn btn-gold mt-3">
            <i class="fas fa-arrow-left"></i> Back to Invitation
        </a>

        @if($rsvp->response_status === 'yes')
        <button onclick="window.print()" class="btn btn-secondary mt-3">
            <i class="fas fa-print"></i> Print QR Code
        </button>
        @endif
    </div>
</body>
</html>
