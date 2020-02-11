<?php

namespace App\Jobs;

use Facades\App\Services\Esign\ProviderFactory as EsignProviderFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CancelSignatureRequest implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    public $provider;
    public $provider_agreement_id;

    /**
     * Create a new job instance.
     *
     * @param mixed $provider
     * @param mixed $provider_agreement_id
     */
    public function __construct($provider, $provider_agreement_id)
    {
        $this->provider = $provider;
        $this->provider_agreement_id = $provider_agreement_id;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        EsignProviderFactory::make($this->provider)->cancelSignatureRequest($this->provider_agreement_id);
    }
}
