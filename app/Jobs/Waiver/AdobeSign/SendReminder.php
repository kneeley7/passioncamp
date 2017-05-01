<?php

namespace App\Jobs\Waiver\AdobeSign;

use Illuminate\Bus\Queueable;
use App\Contracts\EsignProvider;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendReminder implements ShouldQueue
{
    public $waiver;

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($waiver)
    {
        $this->waiver = $waiver;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(EsignProvider $adobesign)
    {
        $status = $adobesign->sendReminder($this->waiver->provider_agreement_id);

        $this->waiver->fill([
            'status' => $status,
        ])->touch();
    }
}
