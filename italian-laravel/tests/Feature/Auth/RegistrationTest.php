<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class RegistrationTest extends TestCase
{
    public function test_scaffold_endpoints_remain_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();
    }
}
