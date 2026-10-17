<?php

namespace Tests\Feature;

use App\Models\AccessLink;
use App\Models\Event;
use App\Models\GuestScan;
use App\Models\GuestScanMatch;
use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaceMatchingFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_media_and_guest_scan_match_relations_are_consistent(): void
    {
        $user = User::create([
            'full_name' => 'Photographe',
            'email' => 'photo@example.com',
            'password' => 'password',
        ]);
        $event = Event::create(['user_id' => $user->id, 'name' => 'Mariage']);
        $photo = Media::create([
            'event_id' => $event->id,
            'storage_path' => "events/{$event->id}/photo.jpg",
            'original_filename' => 'photo.jpg',
        ]);
        $link = AccessLink::create([
            'event_id' => $event->id,
            'token' => str_repeat('a', 64),
            'expires_at' => now()->addDays(30),
        ]);
        $scan = GuestScan::create([
            'event_id' => $event->id,
            'access_link_id' => $link->id,
        ]);
        GuestScanMatch::create([
            'guest_scan_id' => $scan->id,
            'media_id' => $photo->id,
            'similarity' => 0.87654,
            'rank' => 1,
            'face_index' => 0,
        ]);

        $this->assertCount(1, $event->media);
        $this->assertCount(1, $event->accessLinks);
        $this->assertSame($photo->id, $scan->matches->first()->media_id);
        $this->assertSame('0.87654', $scan->matches->first()->similarity);
    }
}



