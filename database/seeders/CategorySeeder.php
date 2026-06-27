<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Food & Dining',    'slug' => 'food',          'icon' => '🍔', 'type' => 'expense'],
            ['name' => 'Transport',         'slug' => 'transport',     'icon' => '🚗', 'type' => 'expense'],
            ['name' => 'Utilities',         'slug' => 'utilities',     'icon' => '🏠', 'type' => 'expense'],
            ['name' => 'Health',            'slug' => 'health',        'icon' => '🏥', 'type' => 'expense'],
            ['name' => 'Education',         'slug' => 'education',     'icon' => '📚', 'type' => 'expense'],
            ['name' => 'Entertainment',     'slug' => 'entertainment', 'icon' => '🎮', 'type' => 'expense'],
            ['name' => 'Salary',            'slug' => 'salary',        'icon' => '💼', 'type' => 'income'],
            ['name' => 'Freelance',         'slug' => 'freelance',     'icon' => '💻', 'type' => 'income'],
            ['name' => 'Investment',        'slug' => 'investment',    'icon' => '📈', 'type' => 'income'],
            ['name' => 'Transfer',          'slug' => 'transfer',      'icon' => '↔️', 'type' => 'both'],
            ['name' => 'Adjustment',        'slug' => 'adjustment',    'icon' => '⚖️', 'type' => 'both'],
            ['name' => 'Other',             'slug' => 'other',         'icon' => '❓', 'type' => 'both'],
            ['name' => 'Debt Payment',      'slug' => 'debt-payment',  'icon' => '💳', 'type' => 'expense'],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['slug' => $cat['slug']],
                array_merge($cat, ['is_system' => true])
            );
        }
    }
}
