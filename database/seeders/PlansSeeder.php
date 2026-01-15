<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlansSeeder extends Seeder
{
    public function run(): void
    {
        Plan::updateOrCreate(
            ['slug' => 'monthly'],
            [
                'name' => 'Mensuel',
                'description' => 'Abonnement mensuel flexible',
                'interval' => 'month',
                'interval_count' => 1,
                'price' => 19.00,
                'currency' => 'EUR',
                'stripe_price_id' => env('STRIPE_MONTHLY_PRICE_ID'),
                'paypal_plan_id' => env('PAYPAL_MONTHLY_PLAN_ID'),
                'features' => [
                    'Factures et devis illimités',
                    'Génération PDF professionnelle',
                    'Envoi par email',
                    'Multi-devises',
                    'Support technique',
                ],
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        Plan::updateOrCreate(
            ['slug' => 'yearly'],
            [
                'name' => 'Annuel',
                'description' => 'Économisez 2 mois par an',
                'interval' => 'year',
                'interval_count' => 1,
                'price' => 190.00,
                'currency' => 'EUR',
                'stripe_price_id' => env('STRIPE_YEARLY_PRICE_ID'),
                'paypal_plan_id' => env('PAYPAL_YEARLY_PLAN_ID'),
                'features' => [
                    'Factures et devis illimités',
                    'Génération PDF professionnelle',
                    'Envoi par email',
                    'Multi-devises',
                    'Support technique prioritaire',
                    '2 mois gratuits',
                ],
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
            ]
        );
    }
}
