<?php

namespace App\Jobs;

use Carbon\Carbon;
use App\Models\Event;
use App\Models\Metasession;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handle(): void
    {
        DB::transaction(function () {

            if (Event::where('event_hash', $this->data['event_hash'])->exists()) {
                return;
            }


            $session = Metasession::firstOrCreate(
                [
                    'tenant_id'  => $this->data['tenant_id'],
                    'session_id' => $this->data['session_id'],
                ],
                [
                    'first_seen_at' => Carbon::now(),
                    'last_seen_at'  => Carbon::now(),
                ]
            );

            $session->update([
                'last_seen_at' => Carbon::now()
            ]);

            Event::create([
                'tenant_id'       => $this->data['tenant_id'],
                'session_id'      => $this->data['session_id'],
                'event_type'      => $this->data['event_type'],
                'event_hash'      => $this->data['event_hash'],
                'event_timestamp' => $this->data['timestamp'],
            ]);
        });
    }
}
