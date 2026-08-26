<?php

namespace Tests\Utility;

use App\Utility\Mailer;
use PHPUnit\Framework\TestCase;

class MailerTest extends TestCase
{
    private string $logFile;
    private ?string $originalLogContents = null;
    private $originalMailDriver = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logFile = dirname(__DIR__, 2) . '/logs/mails.log';
        $this->originalLogContents = file_exists($this->logFile)
            ? file_get_contents($this->logFile)
            : null;

        $this->originalMailDriver = getenv('MAIL_DRIVER');
    }

    protected function tearDown(): void
    {
        if ($this->originalLogContents === null) {
            if (file_exists($this->logFile)) {
                unlink($this->logFile);
            }
        } else {
            file_put_contents($this->logFile, $this->originalLogContents);
        }

        if ($this->originalMailDriver === false) {
            putenv('MAIL_DRIVER');
        } else {
            putenv('MAIL_DRIVER=' . $this->originalMailDriver);
        }

        parent::tearDown();
    }

    public function testSendAppendsFormattedEntryToLogFileByDefault(): void
    {
        putenv('MAIL_DRIVER');

        $result = Mailer::send('to@example.com', 'reply@example.com', 'Test Subject', 'Test body content');

        $this->assertTrue($result);

        $contents = file_get_contents($this->logFile);
        $this->assertStringContainsString('To: to@example.com', $contents);
        $this->assertStringContainsString('Reply-To: reply@example.com', $contents);
        $this->assertStringContainsString('Subject: Test Subject', $contents);
        $this->assertStringContainsString('Test body content', $contents);
    }

    public function testSendAppendsFormattedEntryWhenDriverIsExplicitlyLog(): void
    {
        putenv('MAIL_DRIVER=log');

        $result = Mailer::send('someone@example.com', 'reply@example.com', 'Another Subject', 'Another body');

        $this->assertTrue($result);

        $contents = file_get_contents($this->logFile);
        $this->assertStringContainsString('To: someone@example.com', $contents);
        $this->assertStringContainsString('Subject: Another Subject', $contents);
        $this->assertStringContainsString('Another body', $contents);
    }

    public function testSendAppendsRatherThanOverwritesExistingLog(): void
    {
        putenv('MAIL_DRIVER');

        Mailer::send('first@example.com', 'reply@example.com', 'First Subject', 'First body');
        Mailer::send('second@example.com', 'reply@example.com', 'Second Subject', 'Second body');

        $contents = file_get_contents($this->logFile);
        $this->assertStringContainsString('To: first@example.com', $contents);
        $this->assertStringContainsString('To: second@example.com', $contents);
    }

    // The MAIL_DRIVER=mail branch calls PHP's mail() directly. There is no MTA
    // configured in this test environment and mail() cannot be stubbed without
    // extra tooling (e.g. namespace-level function overrides or a php.ini
    // sendmail_path shim), so that branch is intentionally left uncovered here.
}
