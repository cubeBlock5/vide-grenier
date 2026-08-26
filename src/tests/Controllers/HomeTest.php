<?php

namespace Tests\Controllers;

use App\Controllers\Home;
use PHPUnit\Framework\TestCase;

class HomeTest extends TestCase
{
    public function testIndexActionRendersTemplateWithoutThrowing(): void
    {
        $home = new Home(['id' => null]);

        ob_start();
        $home->indexAction();
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
    }
}
