<?php

namespace Tests\Controllers;

use App\Controllers\Product;
use PHPUnit\Framework\TestCase;

/**
 * Small fixture subclass exposing public wrappers around the protected
 * methods extracted from Product::indexAction().
 */
class ProductTestFixture extends Product
{
    public function callValidateProductSubmission(array $product, array $files): ?string
    {
        return $this->validateProductSubmission($product, $files);
    }

    public function callPictureErrorMessage(int $errorCode): string
    {
        return $this->pictureErrorMessage($errorCode);
    }
}

/**
 * NOTE: indexAction() and showAction() are intentionally not exercised
 * here. Both hit real DB calls (App\Models\Articles) and/or file uploads,
 * and indexAction() can Flash+render+die() on the validation-error path.
 * The validation logic that matters has been extracted into the two
 * protected methods below and is fully covered instead.
 */
class ProductTest extends TestCase
{
    private ProductTestFixture $product;

    protected function setUp(): void
    {
        $this->product = new ProductTestFixture(['id' => 1]);
    }

    private function validFiles(): array
    {
        return [
            'picture' => [
                'error' => UPLOAD_ERR_OK,
            ],
        ];
    }

    public function testValidateProductSubmissionReturnsNullForValidData(): void
    {
        $product = ['name' => 'Vélo', 'description' => 'Bon état'];

        $this->assertNull($this->product->callValidateProductSubmission($product, $this->validFiles()));
    }

    public function testValidateProductSubmissionRejectsMissingName(): void
    {
        $product = ['name' => '   ', 'description' => 'Bon état'];

        $this->assertSame(
            "Le titre est obligatoire !",
            $this->product->callValidateProductSubmission($product, $this->validFiles())
        );
    }

    public function testValidateProductSubmissionRejectsMissingDescription(): void
    {
        $product = ['name' => 'Vélo', 'description' => ''];

        $this->assertSame(
            "La description est obligatoire !",
            $this->product->callValidateProductSubmission($product, $this->validFiles())
        );
    }

    public function testValidateProductSubmissionRejectsMissingFile(): void
    {
        $product = ['name' => 'Vélo', 'description' => 'Bon état'];

        $this->assertSame(
            "Veuillez ajouter une photo !",
            $this->product->callValidateProductSubmission($product, [])
        );
    }

    public function testValidateProductSubmissionRejectsUploadErrNoFile(): void
    {
        $product = ['name' => 'Vélo', 'description' => 'Bon état'];
        $files = ['picture' => ['error' => UPLOAD_ERR_NO_FILE]];

        $this->assertSame(
            "Veuillez ajouter une photo !",
            $this->product->callValidateProductSubmission($product, $files)
        );
    }

    public function testValidateProductSubmissionRejectsUploadErrIniSize(): void
    {
        $product = ['name' => 'Vélo', 'description' => 'Bon état'];
        $files = ['picture' => ['error' => UPLOAD_ERR_INI_SIZE]];

        $this->assertSame(
            "Le fichier est trop volumineux (" . ini_get('upload_max_filesize') . " maximum).",
            $this->product->callValidateProductSubmission($product, $files)
        );
    }

    public function testValidateProductSubmissionRejectsUploadErrFormSize(): void
    {
        $product = ['name' => 'Vélo', 'description' => 'Bon état'];
        $files = ['picture' => ['error' => UPLOAD_ERR_FORM_SIZE]];

        $this->assertSame(
            "Le fichier est trop volumineux (" . ini_get('upload_max_filesize') . " maximum).",
            $this->product->callValidateProductSubmission($product, $files)
        );
    }

    public function testValidateProductSubmissionRejectsOtherUploadError(): void
    {
        $product = ['name' => 'Vélo', 'description' => 'Bon état'];
        $files = ['picture' => ['error' => UPLOAD_ERR_PARTIAL]];

        $this->assertSame(
            "Une erreur est survenue lors de l'envoi du fichier.",
            $this->product->callValidateProductSubmission($product, $files)
        );
    }

    public function testPictureErrorMessageForNoFile(): void
    {
        $this->assertSame(
            "Veuillez ajouter une photo !",
            $this->product->callPictureErrorMessage(UPLOAD_ERR_NO_FILE)
        );
    }

    public function testPictureErrorMessageForIniSize(): void
    {
        $this->assertSame(
            "Le fichier est trop volumineux (" . ini_get('upload_max_filesize') . " maximum).",
            $this->product->callPictureErrorMessage(UPLOAD_ERR_INI_SIZE)
        );
    }

    public function testPictureErrorMessageForFormSize(): void
    {
        $this->assertSame(
            "Le fichier est trop volumineux (" . ini_get('upload_max_filesize') . " maximum).",
            $this->product->callPictureErrorMessage(UPLOAD_ERR_FORM_SIZE)
        );
    }

    public function testPictureErrorMessageForOtherErrorCode(): void
    {
        $this->assertSame(
            "Une erreur est survenue lors de l'envoi du fichier.",
            $this->product->callPictureErrorMessage(UPLOAD_ERR_EXTENSION)
        );
    }
}
