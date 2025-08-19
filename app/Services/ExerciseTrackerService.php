<?php

namespace App\Services;

use App\Models\Exercise;
use OpenAI\Laravel\Facades\OpenAI;

class ExerciseTrackerService
{
        
    /**
     * askAi
     *
     * @param  mixed $question
     * @return mixed
     */
    public function askAi(string $question) : mixed {
        $res = OpenAI::chat()->create([
            'model' => 'gpt-4.1-nano',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => <<<EOD
Kamu adalah GymBro asisten latihan gym yang tugasnya mengekstrak informasi latihan gym dari sebuah pesan.

Saya akan memberikan kamu pesan, tugas kamu adalah mengekstrak informasi tersebut: nama latihan, set dan repetisi, dan beban (kalau ada).

Buatkan juga satu pesan singkat 1 kalimat untuk response seperti latihan tercatat.

Hasilnya kamu buat dalam format json schema strukturnya sesuai yang sudah saya berikan, jangan dikurangi dan jangan ditambahi. 
EOD
                ],
                [
                    'role' => 'user',
                    'content' => $question
                ]
            ],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'gymbro_response',
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'reply_message' => ['type' => ['string', 'null']],
                            'exercise_name' => ['type' => ['string', 'null']],
                            'weight' => ['type' => ['number', 'null']],
                            'sets' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'duration' => ['type' => ['number', 'null']],
                                        'repetition' => ['type' => ['number', 'null']]
                                    ],
                                    'required' => ['duration', 'repetition']
                                ]
                            ]
                        ],
                        'required' => ['reply_message', 'exercise_name', 'weight', 'sets']
                    ]
                ]
            ]
        ]);

        return json_decode($res->choices[0]->message->content);
    }
    
    /**
     * parseInformation
     *
     * @param  mixed $information
     * @return array
     */
    public function parseInformation($information) : array {
        return [
            'exercise_name' => $information->exercise_name,
            'sets' => array_map(function ($set) {
                return [
                    'repetition' => $set->repetition,
                    'duration' => $set->duration
                ];
            }, $information->sets),
            'weight' => $information->weight,
        ];
    }
    
    /**
     * saveInformation
     *
     * @param  mixed $information
     * @param  mixed $customer
     * @return Exercise
     */
    public function saveInformation(array $information, array $customer) : Exercise {
        return Exercise::create(array_merge($information, $customer));
    }
    
}
