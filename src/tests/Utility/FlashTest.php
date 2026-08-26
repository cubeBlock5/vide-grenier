<?php

namespace Tests\Utility;

use App\Utility\Flash;
use PHPUnit\Framework\TestCase;

class FlashTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
    }

    public function testDangerSetsAndReturnsValue(): void
    {
        $result = Flash::danger('Something went wrong');

        $this->assertSame('Something went wrong', $result);
        $this->assertSame('Something went wrong', $_SESSION['flash_danger']);
    }

    public function testDangerReadsAndClearsValue(): void
    {
        Flash::danger('Something went wrong');

        $read = Flash::danger();

        $this->assertSame('Something went wrong', $read);
        $this->assertArrayNotHasKey('flash_danger', $_SESSION);
    }

    public function testDangerReturnsNullWhenNothingSet(): void
    {
        $this->assertNull(Flash::danger());
    }

    public function testDangerSecondReadAfterFirstReturnsNull(): void
    {
        Flash::danger('Only once');

        Flash::danger();
        $secondRead = Flash::danger();

        $this->assertNull($secondRead);
    }

    public function testInfoSetsAndReadsValue(): void
    {
        Flash::info('Heads up');

        $this->assertSame('Heads up', Flash::info());
        $this->assertNull(Flash::info());
    }

    public function testSuccessSetsAndReadsValue(): void
    {
        Flash::success('It worked');

        $this->assertSame('It worked', Flash::success());
        $this->assertNull(Flash::success());
    }

    public function testWarningSetsAndReadsValue(): void
    {
        Flash::warning('Be careful');

        $this->assertSame('Be careful', Flash::warning());
        $this->assertNull(Flash::warning());
    }

    public function testDifferentFlashTypesDoNotInterfereWithEachOther(): void
    {
        Flash::danger('danger message');
        Flash::info('info message');
        Flash::success('success message');
        Flash::warning('warning message');

        $this->assertSame('danger message', Flash::danger());
        $this->assertSame('info message', Flash::info());
        $this->assertSame('success message', Flash::success());
        $this->assertSame('warning message', Flash::warning());
    }
}
