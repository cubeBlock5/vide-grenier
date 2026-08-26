<?php

namespace Tests\Controllers;

use App\Controllers\Contact;
use PHPUnit\Framework\TestCase;

/**
 * Small fixture subclass exposing public wrappers around the protected
 * methods extracted from Contact::indexAction(), so they can be unit
 * tested without going through the real HTTP flow (which reads $_POST,
 * calls Flash/Mailer, and can header()+die()).
 */
class ContactTestFixture extends Contact
{
    public function callValidateContactSubmission(array $data): ?string
    {
        return $this->validateContactSubmission($data);
    }

    public function callBuildContactMessage(array $article, array $data): array
    {
        return $this->buildContactMessage($article, $data);
    }
}

/**
 * NOTE: indexAction() itself is intentionally not exercised here. It calls
 * App\Models\Articles::getOne() (a real DB call) and can header()+die() on
 * both the "article not found" branch and the "message sent" success
 * branch, either of which would kill the PHPUnit process. The validation
 * and message-building logic that matters has been extracted into the two
 * protected methods below and is fully covered instead.
 */
class ContactTest extends TestCase
{
    private ContactTestFixture $contact;

    protected function setUp(): void
    {
        $this->contact = new ContactTestFixture(['id' => 5]);
    }

    public function testValidateContactSubmissionReturnsNullForValidData(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'message' => 'Bonjour, est-ce toujours disponible ?',
        ];

        $this->assertNull($this->contact->callValidateContactSubmission($data));
    }

    public function testValidateContactSubmissionRejectsMissingName(): void
    {
        $data = [
            'name' => '   ',
            'email' => 'john.doe@example.com',
            'message' => 'Bonjour',
        ];

        $this->assertSame(
            "Merci de remplir tous les champs.",
            $this->contact->callValidateContactSubmission($data)
        );
    }

    public function testValidateContactSubmissionRejectsMissingMessage(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'message' => '',
        ];

        $this->assertSame(
            "Merci de remplir tous les champs.",
            $this->contact->callValidateContactSubmission($data)
        );
    }

    public function testValidateContactSubmissionRejectsInvalidEmail(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'not-an-email',
            'message' => 'Bonjour',
        ];

        $this->assertSame(
            "L'adresse email n'est pas valide.",
            $this->contact->callValidateContactSubmission($data)
        );
    }

    public function testBuildContactMessageBuildsExpectedSubjectAndBody(): void
    {
        $article = ['name' => 'Mappemonde à gratter'];
        $data = [
            'name' => '  John Doe  ',
            'email' => '  john.doe@example.com  ',
            'message' => '  Toujours disponible ?  ',
        ];

        $message = $this->contact->callBuildContactMessage($article, $data);

        $this->assertSame(
            'Nouveau message concernant votre annonce "Mappemonde à gratter"',
            $message['subject']
        );
        $this->assertSame(
            "Message de John Doe (john.doe@example.com) :\n\nToujours disponible ?",
            $message['body']
        );
    }
}
