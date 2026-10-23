<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    public function test_scaffold_endpoints_remain_disabled(): void
    {
        $this->get('/forgot-password')->assertNotFound();
        $this->post('/forgot-password', [])->assertNotFound();
        $this->get('/reset-password/synthetic-token')->assertNotFound();
        $this->post('/reset-password', [])->assertNotFound();
    }
}
