<?php

use App\Modules\Identity\Models\Customer;

test('confirm password screen can be rendered', function () {
    $user = Customer::factory()->create();

    $response = $this->actingAs($user, 'customer')->get(route('password.confirm'));

    $response->assertOk();
});
