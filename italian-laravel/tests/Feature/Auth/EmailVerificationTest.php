<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    public function test_scaffold_endpoints_remain_disabled(): void
    {
        $this->get('/verify-email')->assertNotFound();
        $this->get('/verify-email/1/synthetic')->assertNotFound();
        $this->post('/email/verification-notification')->assertNotFound();
    }
}
