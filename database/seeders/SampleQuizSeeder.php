<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\Option;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SampleQuizSeeder extends Seeder
{
    public function run(): void
    {
        // Create or get admin user
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            $admin = User::where('role', 'master_admin')->first();
        }
        
        if (!$admin) {
            $this->command->error('No admin user found! Please create an admin first.');
            return;
        }
        
        $this->command->info('Admin user found: ' . $admin->name);
        
        // Create sample category
        $category = Category::create([
            'name' => 'General Knowledge',
            'slug' => 'general-knowledge',
            'description' => 'Test your general knowledge with these fun and challenging quizzes!',
            'icon' => 'fas fa-globe',
            'color' => '#3498db',
            'is_active' => true,
            'created_by' => $admin->id
        ]);
        
        $this->command->info('Category created: ' . $category->name);
        
        // Create sample quiz
        $quiz = Quiz::create([
            'title' => 'General Knowledge Quiz',
            'slug' => Str::slug('General Knowledge Quiz'),
            'description' => 'Test your knowledge with these 5 general knowledge questions. Good luck!',
            'category_id' => $category->id,
            'duration_minutes' => 10,
            'passing_score' => 60,
            'is_random_questions' => false,
            'is_published' => true,
            'max_attempts' => 3,
            'created_by' => $admin->id
        ]);
        
        $this->command->info('Quiz created: ' . $quiz->title);
        
        // Question 1
        $q1 = Question::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'What is the capital of France?',
            'question_type' => 'multiple_choice',
            'points' => 10,
            'time_seconds' => 30,
            'order' => 1,
            'created_by' => $admin->id
        ]);
        
        Option::create(['question_id' => $q1->id, 'option_text' => 'London', 'is_correct' => false, 'order' => 1]);
        Option::create(['question_id' => $q1->id, 'option_text' => 'Berlin', 'is_correct' => false, 'order' => 2]);
        Option::create(['question_id' => $q1->id, 'option_text' => 'Paris', 'is_correct' => true, 'order' => 3]);
        Option::create(['question_id' => $q1->id, 'option_text' => 'Madrid', 'is_correct' => false, 'order' => 4]);
        
        // Question 2
        $q2 = Question::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'The Earth is flat.',
            'question_type' => 'true_false',
            'points' => 5,
            'time_seconds' => 20,
            'order' => 2,
            'created_by' => $admin->id
        ]);
        
        Option::create(['question_id' => $q2->id, 'option_text' => 'True', 'is_correct' => false, 'order' => 1]);
        Option::create(['question_id' => $q2->id, 'option_text' => 'False', 'is_correct' => true, 'order' => 2]);
        
        // Question 3
        $q3 = Question::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'Which of these are programming languages? (Select all that apply)',
            'question_type' => 'multiple_choice',
            'points' => 15,
            'time_seconds' => 45,
            'order' => 3,
            'created_by' => $admin->id
        ]);
        
        Option::create(['question_id' => $q3->id, 'option_text' => 'Python', 'is_correct' => true, 'order' => 1]);
        Option::create(['question_id' => $q3->id, 'option_text' => 'HTML', 'is_correct' => false, 'order' => 2]);
        Option::create(['question_id' => $q3->id, 'option_text' => 'JavaScript', 'is_correct' => true, 'order' => 3]);
        Option::create(['question_id' => $q3->id, 'option_text' => 'CSS', 'is_correct' => false, 'order' => 4]);
        
        // Question 4
        $q4 = Question::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'What is the largest ocean on Earth?',
            'question_type' => 'single_choice',
            'points' => 10,
            'time_seconds' => 30,
            'order' => 4,
            'created_by' => $admin->id
        ]);
        
        Option::create(['question_id' => $q4->id, 'option_text' => 'Atlantic Ocean', 'is_correct' => false, 'order' => 1]);
        Option::create(['question_id' => $q4->id, 'option_text' => 'Indian Ocean', 'is_correct' => false, 'order' => 2]);
        Option::create(['question_id' => $q4->id, 'option_text' => 'Arctic Ocean', 'is_correct' => false, 'order' => 3]);
        Option::create(['question_id' => $q4->id, 'option_text' => 'Pacific Ocean', 'is_correct' => true, 'order' => 4]);
        
        // Question 5
        $q5 = Question::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'Who painted the Mona Lisa?',
            'question_type' => 'single_choice',
            'points' => 10,
            'time_seconds' => 30,
            'order' => 5,
            'created_by' => $admin->id
        ]);
        
        Option::create(['question_id' => $q5->id, 'option_text' => 'Vincent van Gogh', 'is_correct' => false, 'order' => 1]);
        Option::create(['question_id' => $q5->id, 'option_text' => 'Pablo Picasso', 'is_correct' => false, 'order' => 2]);
        Option::create(['question_id' => $q5->id, 'option_text' => 'Leonardo da Vinci', 'is_correct' => true, 'order' => 3]);
        Option::create(['question_id' => $q5->id, 'option_text' => 'Michelangelo', 'is_correct' => false, 'order' => 4]);
        
        // Update quiz totals
        $quiz->updateTotals();
        
        $this->command->info('====================================');
        $this->command->info('Sample quiz created successfully!');
        $this->command->info('Quiz ID: ' . $quiz->id);
        $this->command->info('Total Questions: ' . $quiz->total_questions);
        $this->command->info('Total Points: ' . $quiz->total_points);
        $this->command->info('====================================');
    }
}