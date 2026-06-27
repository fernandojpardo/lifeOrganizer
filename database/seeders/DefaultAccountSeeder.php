<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Seeder;

class DefaultAccountSeeder extends Seeder
{
    public function run(): void
    {
        User::doesntHave('accounts')->each(function (User $user) {
            Account::create([
                'user_id'    => $user->id,
                'name'       => 'Main Account',
                'type'       => 'bank',
                'currency'   => 'USD',
                'balance'    => 0,
                'icon'       => '🏦',
                'color'      => '#1e293b',
                'is_default' => true,
            ]);
        });
    }
}
