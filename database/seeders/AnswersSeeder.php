<?php

namespace Database\Seeders;

use App\Models\Answer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AnswersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    protected $model = Answer::class;
    public function run(): void
    {
        //
        Answer::factory()->count(20)->create();
    }
}
