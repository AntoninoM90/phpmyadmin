<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\NormalizationController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;

use function in_array;

/**
 * @covers \PhpMyAdmin\Controllers\NormalizationController
 */
class NormalizationControllerTest extends AbstractTestCase
{
    /** @var DatabaseInterface */
    protected $dbi;

    /** @var DbiDummy */
    protected $dummyDbi;

    protected function setUp(): void
    {
        parent::setUp();
        parent::setLanguage();
        parent::setTheme();
        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        $GLOBALS['dbi'] = $this->dbi;
        parent::loadContainerBuilder();
        parent::loadDbiIntoContainerBuilder();
        $GLOBALS['server'] = 1;
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        parent::loadResponseIntoContainerBuilder();
        $GLOBALS['db'] = 'my_db';
        $GLOBALS['table'] = 'test_tbl';
    }

    public function testNormalization(): void
    {
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';
        $dbi = $this->createDatabaseInterface();
        $response = new ResponseRenderer();
        $template = new Template();

        $controller = new NormalizationController(
            $response,
            $template,
            new Normalization($dbi, new Relation($dbi), new Transformations(), $template)
        );
        $controller($this->createStub(ServerRequest::class));

        $files = $response->getHeader()->getScripts()->getFiles();
        $this->assertTrue(
            in_array(['name' => 'normalization.js', 'fire' => 1], $files, true),
            'normalization.js script was not included in the response.'
        );
        $this->assertTrue(
            in_array(['name' => 'vendor/jquery/jquery.uitablefilter.js', 'fire' => 0], $files, true),
            'vendor/jquery/jquery.uitablefilter.js script was not included in the response.'
        );

        $output = $response->getHTMLResult();
        $this->assertStringContainsString(
            '<form method="post" action="index.php?route=/normalization/1nf/step1&lang=en"'
            . ' name="normalize" id="normalizeTable"',
            $output
        );
        $this->assertStringContainsString('<input type="hidden" name="db" value="test_db">', $output);
        $this->assertStringContainsString('<input type="hidden" name="table" value="test_table">', $output);
        $this->assertStringContainsString('type="radio" name="normalizeTo"', $output);
        $this->assertStringContainsString('id="normalizeToRadio1" value="1nf" checked>', $output);
        $this->assertStringContainsString('id="normalizeToRadio2" value="2nf">', $output);
        $this->assertStringContainsString('id="normalizeToRadio3" value="3nf">', $output);
    }
}
