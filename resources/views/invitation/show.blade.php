<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $event->title }} - You're Invited!</title>

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
        }
        .invitation-card {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .invitation-header {
            background: linear-gradient(135deg, #D4AF37 0%, #B8941D 100%);
            color: white;
            padding: 60px 40px;
            text-align: center;
        }
        .invitation-header h1 {
            font-size: 3rem;
            font-weight: 300;
            margin-bottom: 10px;
        }
        .invitation-body {
            padding: 40px;
        }
        .event-detail {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .event-detail i {
            font-size: 2rem;
            color: #D4AF37;
            margin-right: 20px;
            width: 40px;
            text-align: center;
        }
        .event-detail .detail-content h5 {
            margin: 0 0 5px 0;
            color: #333;
        }
        .event-detail .detail-content p {
            margin: 0;
            color: #666;
        }
        .rsvp-form {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 10px;
            margin-top: 30px;
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
        .countdown {
            text-align: center;
            margin: 30px 0;
            padding: 30px;
            background: linear-gradient(135deg, #F4E4C1 0%, #E8D5A8 100%);
            border-radius: 10px;
        }
        .countdown-item {
            display: inline-block;
            margin: 0 15px;
            text-align: center;
        }
        .countdown-item .number {
            font-size: 3rem;
            font-weight: bold;
            color: #B8941D;
            display: block;
        }
        .countdown-item .label {
            color: #666;
            text-transform: uppercase;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="invitation-card">
        <div class="invitation-header">
            <h1>You're Invited!</h1>
            <p style="font-size: 1.5rem; margin: 0;">{{ $event->title }}</p>
        </div>

        <div class="invitation-body">
            @if($event->description)
            <div class="text-center mb-4">
                <p class="lead">{{ $event->description }}</p>
            </div>
            @endif

            <!-- Countdown -->
            <div class="countdown">
                <h3 class="mb-3">Countdown to the Big Day</h3>
                <div id="countdown"></div>
            </div>

            <!-- Event Details -->
            <div class="event-detail">
                <i class="fas fa-calendar-alt"></i>
                <div class="detail-content">
                    <h5>Date & Time</h5>
                    <p>{{ $event->event_date->format('l, F j, Y') }} at {{ $event->event_time }}</p>
                </div>
            </div>

            <div class="event-detail">
                <i class="fas fa-map-marker-alt"></i>
                <div class="detail-content">
                    <h5>Venue</h5>
                    <p><strong>{{ $event->venue_name }}</strong></p>
                    <p>{{ $event->venue_address }}</p>
                    @if($event->venue_google_maps)
                        <a href="{{ $event->venue_google_maps }}" target="_blank" class="btn btn-sm btn-gold mt-2">
                            <i class="fas fa-map"></i> Get Directions
                        </a>
                    @endif
                </div>
            </div>

            @if($event->special_instructions)
            <div class="event-detail">
                <i class="fas fa-info-circle"></i>
                <div class="detail-content">
                    <h5>Special Instructions</h5>
                    <p>{{ $event->special_instructions }}</p>
                </div>
            </div>
            @endif

            @if($event->rsvp_deadline)
            <div class="event-detail">
                <i class="fas fa-clock"></i>
                <div class="detail-content">
                    <h5>RSVP Deadline</h5>
                    <p>Please respond by {{ $event->rsvp_deadline->format('F j, Y') }}</p>
                </div>
            </div>
            @endif

            <!-- RSVP Form -->
            <div class="rsvp-form">
                <h3 class="text-center mb-4">RSVP</h3>

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <form action="{{ route('rsvp.submit', $event->invitation_code) }}" method="POST">
                    @csrf

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="guest_name" class="form-label">Your Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('guest_name') is-invalid @enderror"
                                   id="guest_name" name="guest_name" value="{{ old('guest_name') }}" required>
                            @error('guest_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="guest_email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('guest_email') is-invalid @enderror"
                                   id="guest_email" name="guest_email" value="{{ old('guest_email') }}" required>
                            @error('guest_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="guest_phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="guest_phone"
                               name="guest_phone" value="{{ old('guest_phone') }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Will you be attending? <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2">
                            <div class="form-check flex-fill">
                                <input class="form-check-input" type="radio" name="response_status"
                                       id="response_yes" value="yes" {{ old('response_status') == 'yes' ? 'checked' : '' }} required>
                                <label class="form-check-label" for="response_yes">
                                    <i class="fas fa-check text-success"></i> Yes, I'll be there!
                                </label>
                            </div>
                            <div class="form-check flex-fill">
                                <input class="form-check-input" type="radio" name="response_status"
                                       id="response_no" value="no" {{ old('response_status') == 'no' ? 'checked' : '' }}>
                                <label class="form-check-label" for="response_no">
                                    <i class="fas fa-times text-danger"></i> Sorry, can't make it
                                </label>
                            </div>
                            <div class="form-check flex-fill">
                                <input class="form-check-input" type="radio" name="response_status"
                                       id="response_maybe" value="maybe" {{ old('response_status') == 'maybe' ? 'checked' : '' }}>
                                <label class="form-check-label" for="response_maybe">
                                    <i class="fas fa-question text-warning"></i> Maybe
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3" id="guestCountDiv" style="display: none;">
                        <label for="number_of_guests" class="form-label">Number of Guests <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="number_of_guests"
                               name="number_of_guests" value="{{ old('number_of_guests', 1) }}" min="1">
                        @if($event->allow_plus_one)
                            <small class="text-muted">You may bring a plus one</small>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label for="dietary_restrictions" class="form-label">Dietary Restrictions</label>
                        <input type="text" class="form-control" id="dietary_restrictions"
                               name="dietary_restrictions" value="{{ old('dietary_restrictions') }}"
                               placeholder="e.g., Vegetarian, Vegan, Allergies">
                    </div>

                    <div class="mb-3">
                        <label for="message" class="form-label">Message to the Host</label>
                        <textarea class="form-control" id="message" name="message" rows="3">{{ old('message') }}</textarea>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn btn-gold btn-lg">
                            <i class="fas fa-paper-plane"></i> Submit RSVP
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Countdown timer
        const eventDate = new Date("{{ $event->event_date->format('Y-m-d') }} {{ $event->event_time }}").getTime();

        function updateCountdown() {
            const now = new Date().getTime();
            const distance = eventDate - now;

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            document.getElementById("countdown").innerHTML = `
                <div class="countdown-item">
                    <span class="number">${days}</span>
                    <span class="label">Days</span>
                </div>
                <div class="countdown-item">
                    <span class="number">${hours}</span>
                    <span class="label">Hours</span>
                </div>
                <div class="countdown-item">
                    <span class="number">${minutes}</span>
                    <span class="label">Minutes</span>
                </div>
                <div class="countdown-item">
                    <span class="number">${seconds}</span>
                    <span class="label">Seconds</span>
                </div>
            `;

            if (distance < 0) {
                document.getElementById("countdown").innerHTML = "The event has started!";
            }
        }

        updateCountdown();
        setInterval(updateCountdown, 1000);

        // Show/hide guest count based on response
        $('input[name="response_status"]').on('change', function() {
            if ($(this).val() === 'yes') {
                $('#guestCountDiv').show();
                $('#number_of_guests').prop('required', true);
            } else {
                $('#guestCountDiv').hide();
                $('#number_of_guests').prop('required', false);
            }
        });

        // Trigger on page load if yes is selected
        if ($('input[name="response_status"]:checked').val() === 'yes') {
            $('#guestCountDiv').show();
        }
    </script>
</body>
</html>
