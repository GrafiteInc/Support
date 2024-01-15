<?php

namespace Grafite\Support\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CloudflarePurge extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cloudflare:purge {zone} {email} {key}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge the Cloudflare cache';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $response = Http::withHeaders([
            'X-Auth-Email' => $this->argument('email'),
            'X-Auth-Key' => $this->argument('key'),
            'Content-Type' => 'application/json',
        ])->post('https://api.cloudflare.com/client/v4/zones/'.$this->argument('zone').'/purge_cache', [
            'purge_everything' => true,
        ]);

        if ($response->ok()) {
            $this->info('Purged.');
        } else {
            $this->error('Failed to purge.');
        }

        return 0;
    }
}
