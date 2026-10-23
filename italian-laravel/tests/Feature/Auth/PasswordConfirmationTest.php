<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    public function test_scaffold_endpoints_remain_disabled(): void
    {
        $this->get('/confirm-password')->assertNotFound();
        $this->post('/confirm-password', [])->assertNotFound();
    }
}
