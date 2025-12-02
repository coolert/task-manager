<?php

use App\MessagePipeline\Consumer\ConsumerDiscovery;
use App\MessagePipeline\Consumer\ConsumerRegistry;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $stubsBase   = base_path('tests/Stubs');
    $handersPath = base_path('tests/Stubs/Handlers');

    File::deleteDirectory($stubsBase);
    File::makeDirectory($handersPath, 0755, true);

    ConsumerRegistry::clear();
    ConsumerDiscovery::setScanPath(base_path('tests/Stubs/Handlers'), 'Tests\\Stubs\\Handlers');
});

afterEach(function () {
    ConsumerDiscovery::reset();
    ConsumerRegistry::clear();

    $stubsBase = base_path('tests/Stubs');
    File::deleteDirectory($stubsBase);
});

function writeHandlerFile(string $filename, string $content): void
{
    file_put_contents("tests/Stubs/Handlers/$filename", $content);
}

it('registers a handler with a single consume pattern', function () {
    writeHandlerFile('StubSinglePatternHandler.php', <<<PHP
    <?php
    namespace Tests\Stubs\Handlers;

    use App\MessagePipeline\Consumer\Attributes\Consumes;
    use App\MessagePipeline\Handlers\BaseHandler;

    #[Consumes('test.single')]
    class StubSinglePatternHandler extends BaseHandler {
        public function process(array \$payload): void {}
    }
    PHP);

    ConsumerDiscovery::discover();

    expect(ConsumerRegistry::resolve('test.single'))
        ->toBe('Tests\\Stubs\\Handlers\\StubSinglePatternHandler');
});

it('registers a handler with multiple consume patterns', function () {
    writeHandlerFile('StubMultiPatternHandler.php', <<<'PHP'
    <?php
    namespace Tests\Stubs\Handlers;

    use App\MessagePipeline\Consumer\Attributes\Consumes;
    use App\MessagePipeline\Handlers\BaseHandler;

    #[Consumes(['test.a', 'test.b'])]
    class StubMultiPatternHandler extends BaseHandler {
        public function process(array $payload): void {}
    }
    PHP);

    ConsumerDiscovery::discover();

    expect(ConsumerRegistry::resolve('test.a'))
        ->toBe('Tests\\Stubs\\Handlers\\StubMultiPatternHandler')
        ->and(ConsumerRegistry::resolve('test.b'))
        ->toBe('Tests\\Stubs\\Handlers\\StubMultiPatternHandler');

});

it('ignores handler classes that do not define a Consumes attribute', function () {
    writeHandlerFile('StubNoAttributeHandler.php', <<<'PHP'
    <?php
    namespace Tests\Stubs\Handlers;

    use App\MessagePipeline\Handlers\BaseHandler;

    class StubNoAttributeHandler extends BaseHandler {
        public function process(array $payload): void {}
    }
    PHP);

    ConsumerDiscovery::discover();

    expect(ConsumerRegistry::resolve('any.event'))->toBeNull();
});

it('ignores abstract handler classes even when they declare Consumes attributes', function () {
    writeHandlerFile('StubAbstractHandler.php', <<<'PHP'
    <?php
    namespace Tests\Stubs\Handlers;

    use App\MessagePipeline\Consumer\Attributes\Consumes;
    use App\MessagePipeline\Handlers\BaseHandler;

    #[Consumes('abstract.event')]
    abstract class StubAbstractHandler extends BaseHandler {}
    PHP);

    ConsumerDiscovery::discover();

    expect(ConsumerRegistry::resolve('abstract.event'))->toBeNull();
});
