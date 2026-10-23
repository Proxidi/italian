<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    public function test_scaffold_endpoints_remain_disabled(): void
    {
        $this->put('/password', [])->assertNotFound();
    }
}
