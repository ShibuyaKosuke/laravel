<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected User|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model $adminUser;

    public function createAdminUser()
    {
        $this->adminUser = User::factory()->admin()->create();
    }
}
