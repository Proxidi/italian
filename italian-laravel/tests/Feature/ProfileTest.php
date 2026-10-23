<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProfileTest extends TestCase
{
    public function test_scaffold_endpoints_remain_disabled(): void
    {
        $this->get('/profile')->assertNotFound();
        $this->patch('/profile', [])->assertNotFound();
        $this->delete('/profile', [])->assertNotFound();
    }
}
