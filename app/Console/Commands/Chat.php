<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class Chat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    
    /**
     * workouts
     *
     * @var array
     */
    private $workouts = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $running = true;
        $question = 'Halo, ada yang bisa dibantu';

        while ($running)
        {
            $this->table(['nama', 'kategori', 'otot', 'set', 'komplit'], array_map(function ($workout) {
                return [
                    'nama' => $workout['name'],
                    'kategori' => $workout['category'],
                    'otot' => $workout['muscle'],
                    'set' => implode(',', array_map(function ($set) {
                        if ($set['duration']) {
                            return $set['duration'];
                        }
                        return $set['repetition'];
                    }, $workout['sets'])),
                    'komplit' => $workout['is_complete']
                ];
            }, $this->workouts));

            $message = $this->ask($question);

            $res = $this->handleMessage($message);
        
            $intent = $res->intent;
            $question = $res->reply_message;

            if ($intent === 'other') {

            } else if ($intent === 'add_exercise') {
                $question = $this->getRelatedQuestion();
            } else if ($intent === 'stop_exercise') {
                $running = false;
            }
        }
    }
    
    /**
     * handleMessage
     *
     * @param  mixed $message
     * @return mixed
     */
    private function handleMessage(string $message) : mixed {
        $systemMessage = $this->getSystemMessage();
        $res = $this->askAi($systemMessage, $message);

        if (isset($res->workouts) && count($res->workouts)) {
            $workout = $res->workouts[0];
            $lastWorkoutIndex = count($this->workouts) - 1;

            if (count($this->workouts) && $this->workouts[$lastWorkoutIndex] && !$this->workouts[$lastWorkoutIndex]['is_complete']) {
                $lastWorkout = $this->workouts[$lastWorkoutIndex];

                $updateData = [
                    'name' => $workout->exercise_name ? $workout->exercise_name : $lastWorkout['name'],
                    'category' => $workout->exercise_category ? $workout->exercise_category : $lastWorkout['category'],
                    'muscle' => $workout->muscle_category ? $workout->muscle_category : $lastWorkout['muscle'],
                    'sets' => !isset($workout->sets) ? $lastWorkout['sets'] : array_map(function ($set) {
                        return [
                            'duration' => isset($set->duration) ? $set->duration : null,
                            'repetition' => isset($set->repetition) ? $set->repetition : null
                        ];
                    }, $workout->sets),
                ];

                $this->workouts[$lastWorkoutIndex] = array_merge(
                    $updateData,
                    [
                        'is_complete' => !!$updateData['name'] && count($updateData['sets']) && collect($updateData['sets'])->every(function ($set) {
                            return $set['repetition'] || $set['duration'];
                        })
                    ]
                );
            } else {
                $isComplete = !!$workout->exercise_name && isset($workout->sets) && count($workout->sets) && collect($workout->sets)->every(function ($set) {
                    if (isset($set->repetition) && $set->repetition) {
                        return true;

                    }

                    return isset($set->duration) && $set->duration;
                });

                $this->workouts[] = [
                    'name' => $workout->exercise_name,
                    'category' => $workout->exercise_category,
                    'muscle' => $workout->muscle_category,
                    'sets' => !isset($workout->sets) ? [] : array_map(function ($set) {
                        return [
                            'duration' => isset($set->duration) ? $set->duration : null,
                            'repetition' => isset($set->repetition) ? $set->repetition : null
                        ];
                    }, $workout->sets),
                    'is_complete' => $isComplete
                ];
            }
        }

        return $res;
    }
    
    /**
     * getRelatedQuestion
     *
     * @return string
     */
    private function getRelatedQuestion() : string {
        $need = null;
        $lastWorkout = $this->workouts[count($this->workouts) - 1];

        if ($lastWorkout['is_complete']) {
            return 'Data latihan sudah disimpan, ada yang ingin ditambahkan lagi?';
        }

        if (!$lastWorkout['name']) {
            $need = 'nama latihan';
        } else if (!$lastWorkout['sets']) {
            $need = 'set dan repetisi atau durasi';
        }

        return OpenAI::chat()->create([
            'model' => 'gpt-4.1-nano',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "Buatkan pesan singkat untuk meminta user untuk informasi $need"
                ]
            ]
        ])->choices[0]->message->content;
    }
    
    /**
     * getSystemMessage
     *
     * @return string
     */
    private function getSystemMessage() : string {
        return <<<EOD
Kamu adalah GymBro, asisten latihan gym. Tugasmu membaca pesan user dan menghasilkan jawaban sesuai schema JSON yang sudah diberikan di payload.

Daftar:
- Nama latihan: pushup, squat, plank
- Kategori latihan: upper body, lower body, core
- Kategori otot: lengan, paha, perut

Aturan:

1. Tentukan intent:
   - 'add_exercise' → jika user menyebut latihan atau set (misal: "mau push up", "2x20").
   - 'stop_exercise' → jika user ingin berhenti latihan.
   - 'other' → selain itu.

2. Ekstrak workout:
   - Jika user menyebut nama latihan, otomatis isi `exercise_name`, `exercise_category`, dan `muscle_category` sesuai daftar:
       - pushup → upper body, lengan
       - squat → lower body, paha
       - plank → core, perut
   - Sets:
     - Jika user menyebut durasi, masukkan di `duration`.
     - Jika user menyebut repetisi, masukkan di `repetition`.
     - Bisa ada beberapa set, simpan sebagai array.
   - Jika user hanya menyebut kategori otot atau kategori latihan, isi field terkait, biarkan yang lain kosong.

3. Balasan di `reply_message` harus singkat dan sopan, memberi konfirmasi atau saran.

Contoh pesan dan interpretasinya:
- "mau push up" → isi `exercise_name = pushup`, `exercise_category = upper body`, `muscle_category = lengan`.
- "mau plank 30s" → isi `exercise_name = plank`, `exercise_category = core`, `muscle_category = perut`, `sets[0].duration = 30s`.
- "2x20" → buat array 2 elemen, masing-masing set `repetition = 20`, `duration = null`.
- "3x30s" → buat array 3 elemen, masing-masing set `duration = 30s`, `repetition = null`.
- "latihan dada" → isi `muscle_category = lengan`, yang lain kosong.
- "latihan core" → isi `exercise_category = core`, yang lain kosong.

Balas hanya dengan JSON sesuai schema yang sudah diberikan di payload, jangan menambahkan teks lain.
EOD;
    }
    
    /**
     * askAi
     *
     * @param  mixed $systemMessage
     * @param  mixed $userMessage
     * @return mixed
     */
    private function askAi(string $systemMessage, string $userMessage) : mixed {
        $res = OpenAI::chat()->create([
            'model' => 'gpt-4.1-nano',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemMessage
                ],
                [
                    'role' => 'user',
                    'content' => $userMessage
                ]
            ],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'gymbro_response',
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'intent' => ['type' => 'string', 'enum' => ['other', 'stop_exercise', 'add_exercise']],
                            'reply_message' => ['type' => 'string'],
                            'workouts' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'exercise_name' => ['type' => 'string'],
                                        'exercise_category' => ['type' => 'string'],
                                        'muscle_category' => ['type' => 'string'],
                                        'sets' => [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'object',
                                                'properties' => [
                                                    'duration' => ['type' => ['string', 'null']],
                                                    'repetition' => ['type' => ['string', 'null']]
                                                ],
                                                'required' => []
                                            ]
                                        ]
                                    ],
                                    'required' => ['exercise_name', 'sets']
                                ]
                            ]
                        ],
                        'required' => ['intent', 'reply_message']
                    ]
                ]
            ],
        ]);

        return json_decode($res->choices[0]->message->content);
    }
}
