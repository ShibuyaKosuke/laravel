<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ViewMakeCommandTest extends TestCase
{
    public function test_console_command()
    {
        $this->artisan('make:view', [
            'model' => 'Example',
            '--crud' => true,
        ])->assertExitCode(0);

        $this->assertFileExists(resource_path('views/examples/index.blade.php'));
        $this->assertFileExists(resource_path('views/examples/show.blade.php'));
        $this->assertFileExists(resource_path('views/examples/create.blade.php'));
        $this->assertFileExists(resource_path('views/examples/edit.blade.php'));

        File::deleteDirectory(resource_path('views/examples'), true);
        File::deleteDirectory(resource_path('views/examples'));
    }
}
