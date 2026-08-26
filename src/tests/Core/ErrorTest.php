<?php

namespace Tests\Core;

use Core\Error;
use PHPUnit\Framework\TestCase;

class ErrorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
    }

    // --- errorHandler() ------------------------------------------------------

    public function testErrorHandlerThrowsErrorExceptionWhenReportingEnabled(): void
    {
        $old = error_reporting(E_ALL);

        try {
            try {
                Error::errorHandler(E_WARNING, 'Test warning', 'file.php', 42);
                $this->fail('Expected ErrorException to be thrown.');
            } catch (\ErrorException $e) {
                $this->assertSame('Test warning', $e->getMessage());
                $this->assertSame('file.php', $e->getFile());
                $this->assertSame(42, $e->getLine());
                $this->assertSame(E_WARNING, $e->getSeverity());
            }
        } finally {
            error_reporting($old);
        }
    }

    public function testErrorHandlerDoesNothingWhenErrorReportingIsSuppressed(): void
    {
        $old = error_reporting(0);

        try {
            $result = Error::errorHandler(E_WARNING, 'Suppressed', 'file.php', 10);
            $this->assertNull($result);
        } finally {
            error_reporting($old);
        }
    }

    // --- exceptionHandler() --------------------------------------------------
    // App\Config::SHOW_ERRORS is hardcoded to `true` in this codebase, so the
    // "log to file + render template" branch (SHOW_ERRORS === false) is dead
    // code and is intentionally not exercised here.

    public function testExceptionHandlerOutputsFatalErrorForGenericException(): void
    {
        $exception = new \Exception('Something broke');

        ob_start();
        Error::exceptionHandler($exception);
        $output = ob_get_clean();

        $this->assertStringContainsString('Fatal error', $output);
        $this->assertStringContainsString('Something broke', $output);
        $this->assertStringContainsString('Exception', $output);
        $this->assertSame(500, http_response_code());
    }

    public function testExceptionHandlerSets404ResponseCodeForNotFoundException(): void
    {
        $exception = new \Exception('Missing page', 404);

        ob_start();
        Error::exceptionHandler($exception);
        $output = ob_get_clean();

        $this->assertStringContainsString('Fatal error', $output);
        $this->assertStringContainsString('Missing page', $output);
        $this->assertSame(404, http_response_code());
    }

    public function testExceptionHandlerIncludesFileAndLine(): void
    {
        $exception = new \Exception('boom');
        $expectedFile = $exception->getFile();
        $expectedLine = $exception->getLine();

        ob_start();
        Error::exceptionHandler($exception);
        $output = ob_get_clean();

        $this->assertStringContainsString($expectedFile, $output);
        $this->assertStringContainsString((string) $expectedLine, $output);
    }
}
