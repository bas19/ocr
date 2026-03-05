<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Receipt>
 */
class ReceiptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10, 200);
        $taxAmount = $subtotal * 0.08;
        $totalAmount = $subtotal + $taxAmount;

        return [
            'image_path' => 'receipts/'.fake()->uuid().'.jpg',
            'merchant_name' => fake()->company(),
            'total_amount' => $totalAmount,
            'tax_amount' => $taxAmount,
            'subtotal' => $subtotal,
            'transaction_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'transaction_time' => fake()->time('H:i:s'),
            'raw_text' => fake()->paragraph(10),
            'items' => [
                [
                    'name' => fake()->word(),
                    'quantity' => fake()->numberBetween(1, 3),
                    'price' => fake()->randomFloat(2, 5, 50),
                ],
                [
                    'name' => fake()->word(),
                    'quantity' => fake()->numberBetween(1, 3),
                    'price' => fake()->randomFloat(2, 5, 50),
                ],
            ],
            'metadata' => [
                'confidence' => fake()->randomFloat(2, 0.7, 1.0),
            ],
            'status' => fake()->randomElement(['pending', 'processed', 'failed']),
        ];
    }
}
