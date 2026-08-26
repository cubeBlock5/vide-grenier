<?php

namespace Tests\Core;

use App\Utility\Flash;
use Core\View;
use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
{
    private const FIXTURE_NAME = '__test_fixture__.php';

    private string $fixturePath;

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];

        $this->fixturePath = dirname(__DIR__, 2) . '/App/Views/' . self::FIXTURE_NAME;
        file_put_contents($this->fixturePath, '<?php echo $foo ?? \'no-foo\'; ?>');
    }

    protected function tearDown(): void
    {
        if (is_file($this->fixturePath)) {
            unlink($this->fixturePath);
        }
        parent::tearDown();
    }

    // --- render() ----------------------------------------------------------

    public function testRenderOutputsViewContentWithProvidedArgs(): void
    {
        ob_start();
        View::render(self::FIXTURE_NAME, ['foo' => 'bar']);
        $output = ob_get_clean();

        $this->assertSame('bar', $output);
    }

    public function testRenderOutputsDefaultWhenArgIsMissing(): void
    {
        ob_start();
        View::render(self::FIXTURE_NAME);
        $output = ob_get_clean();

        $this->assertSame('no-foo', $output);
    }

    public function testRenderThrowsWhenViewFileDoesNotExist(): void
    {
        $this->expectException(\Exception::class);

        View::render('this-view-does-not-exist.php');
    }

    // --- renderTemplate() ----------------------------------------------------

    public function testRenderTemplateRendersTwigAndEchoesOutput(): void
    {
        ob_start();
        View::renderTemplate('Home/index.html', []);
        $output = ob_get_clean();

        $this->assertStringContainsString('Vide Grenier En Ligne | Accueil', $output);
    }

    // --- setDefaultVariables() -------------------------------------------------

    public function testSetDefaultVariablesPullsUserFromSession(): void
    {
        $_SESSION['user'] = ['id' => 1, 'username' => 'bob'];

        $args = View::setDefaultVariables();

        $this->assertSame(['id' => 1, 'username' => 'bob'], $args['user']);
    }

    public function testSetDefaultVariablesUserIsNullWhenNoSessionUser(): void
    {
        $_SESSION = [];

        $args = View::setDefaultVariables();

        $this->assertNull($args['user']);
    }

    public function testSetDefaultVariablesPreservesProvidedArgs(): void
    {
        $args = View::setDefaultVariables(['custom' => 'value']);

        $this->assertSame('value', $args['custom']);
        $this->assertArrayHasKey('user', $args);
        $this->assertArrayHasKey('flash_danger', $args);
    }

    public function testSetDefaultVariablesPullsAndConsumesFlashMessages(): void
    {
        $_SESSION = [];
        Flash::danger('bad');
        Flash::info('info');
        Flash::success('yay');
        Flash::warning('careful');

        $args = View::setDefaultVariables();

        $this->assertSame('bad', $args['flash_danger']);
        $this->assertSame('info', $args['flash_info']);
        $this->assertSame('yay', $args['flash_success']);
        $this->assertSame('careful', $args['flash_warning']);

        // Flash values are consumed (cleared) once read.
        $this->assertArrayNotHasKey('flash_danger', $_SESSION);

        $secondRead = View::setDefaultVariables();
        $this->assertNull($secondRead['flash_danger']);
    }
}
