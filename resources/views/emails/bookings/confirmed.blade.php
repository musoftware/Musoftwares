<x-mail::message>
# Booking Confirmed

Hi {{ $booking->guest_name }},

Your booking for **{{ $booking->eventType->title }}** has been confirmed.

**Details:**
- **Host:** {{ $booking->eventType->user->name }}
- **When:** {{ $booking->starts_at->format('l, F j, Y \a\t g:i A') }} ({{ $booking->timezone }})
- **Duration:** {{ $booking->eventType->duration_minutes }} minutes
@if($booking->payment_status === 'paid')
- **Amount Paid:** {{ $booking->price }} {{ $booking->currency }}
@endif

You can manage or cancel your appointment by contacting the host.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
