<?php

namespace App\Services\Event;

use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\User;
use Illuminate\Support\Str;

class EventService
{
    /**
     * Create a new social or public event.
     *
     * @param  array<string, mixed>  $data
     */
    public function createEvent(User $creator, array $data): Event
    {
        return Event::create([
            'creator_id' => $creator->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'] ?? null,
            'location_type' => $data['location_type'] ?? 'venue',
            'address' => $data['address'] ?? null,
            'online_link' => $data['online_link'] ?? null,
            'cover_image' => $data['cover_image'] ?? null,
            'max_attendees' => $data['max_attendees'] ?? null,
            'ticket_price' => $data['ticket_price'] ?? 0.00,
        ]);
    }

    /**
     * RSVP to an event (going, interested, declined) with unique ticket code.
     */
    public function rsvp(Event $event, User $user, string $status = 'going'): EventAttendee
    {
        $ticketCode = ($status === 'going') ? 'TKT-'.strtoupper(Str::random(8)) : null;

        $attendee = EventAttendee::updateOrCreate(
            ['event_id' => $event->id, 'user_id' => $user->id],
            [
                'status' => $status,
                'ticket_code' => $ticketCode,
            ]
        );

        $event->update([
            'going_count' => $event->attendees()->where('status', 'going')->count(),
            'interested_count' => $event->attendees()->where('status', 'interested')->count(),
        ]);

        return $attendee;
    }

    /**
     * Export event to iCalendar (.ics) format string.
     */
    public function generateIcs(Event $event): string
    {
        $dtStart = $event->start_time->format('Ymd\THis\Z');
        $dtEnd = $event->end_time ? $event->end_time->format('Ymd\THis\Z') : $dtStart;

        return "BEGIN:VCALENDAR\r\n".
            "VERSION:2.0\r\n".
            "PRODID:-//Bondhoo//Social Network Events//BN\r\n".
            "BEGIN:VEVENT\r\n".
            "UID:event-{$event->id}@bondhoo.com\r\n".
            'DTSTAMP:'.now()->format('Ymd\THis\Z')."\r\n".
            "DTSTART:{$dtStart}\r\n".
            "DTEND:{$dtEnd}\r\n".
            "SUMMARY:{$event->title}\r\n".
            'DESCRIPTION:'.addcslashes($event->description ?? '', "\n\r")."\r\n".
            'LOCATION:'.addcslashes($event->address ?? 'Online', "\n\r")."\r\n".
            "STATUS:CONFIRMED\r\n".
            "END:VEVENT\r\n".
            "END:VCALENDAR\r\n";
    }
}
