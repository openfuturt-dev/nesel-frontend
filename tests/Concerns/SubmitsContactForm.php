<?php

namespace Tests\Concerns;

trait SubmitsContactForm
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function contactPayload(array $overrides = []): array
    {
        return [
            'name' => 'Nassim Namous',
            'email' => 'nassim@example.com',
            'phone' => '+212 6 12 34 56 78',
            'city' => 'Marrakech',
            'message' => 'Je souhaite domicilier une nouvelle société.',
            ...$overrides,
        ];
    }
}
