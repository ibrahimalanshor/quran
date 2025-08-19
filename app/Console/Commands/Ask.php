<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Ask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ask';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $phone = $this->ask('phone');
        $name = $this->ask('name');
        $message = $this->ask('message');

        $res = Http::accept('application\/vnd.api+json')
            ->post('http://localhost:8000/api/v1/webhooks/wa', [
                'object' => 'whatsapp_business_account',
                'entry' => [
                    [
                        'id' => '123456789012345',
                        'changes' => [
                            [
                                'field' => 'messages',
                                'value' => [
                                    'messaging_product' => 'whatsapp',
                                    'metadata' => [
                                        'display_phone_number' => $phone,
                                        'phone_number_id' => '112233445566778'
                                    ],
                                    'contacts' => [
                                        [
                                            'profile' => [
                                                'name' => $name
                                            ],
                                            'wa_id' => $phone
                                        ]
                                    ],
                                    'messages' => [
                                        [
                                            'from' => $phone,
                                            'id' => Str::random(30),
                                            'timestamp' => now()->timestamp, // 2024-06-18 10:00:00 UTC
                                            'text' => [
                                                'body' => $message
                                            ],
                                            'type' => 'text'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]);

        $res->dd();
    }
}
