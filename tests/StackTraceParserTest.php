<?php
declare(strict_types=1);

namespace Xepozz\StacktraceParser\Tests;

use Exception;
use Phplrt\Compiler\Compiler;
use PHPUnit\Framework\TestCase;
use Xepozz\StacktraceParser\StacktraceParser;

class StackTraceParserTest extends TestCase
{
    /**
     * @dataProvider stacktraceDataProvider()
     */
    public function testParse(string $stacktrace, array $expectedResult): void
    {
        $result = $this->parse($stacktrace);
        $expectedResult = $this->cleanTrace($expectedResult);

        $this->assertEquals($expectedResult, $result);
    }

    private function parse(string $stacktrace): iterable
    {
        $parser = new StackTraceParser(new Compiler());
        return $parser->parse($stacktrace);
    }

    public function stacktraceDataProvider(): array
    {
        $exception1 = new Exception();
        return [
            'Complex stacktrace' => [
                $exception1->getTraceAsString(),
                $exception1->getTrace(),
            ],
            'Usual stacktrace' => [
                <<<TEXT
                Stack trace:
                #0 /in/hVvRE(11): A->__g()
                #1 {main}
                  thrown in /in/hVvRE on line 5
               TEXT
                ,
                [
                    [
                        'file' => '/in/hVvRE',
                        'line' => 11,
                        'class' => 'A',
                        'type' => '->',
                        'function' => '__g',
                    ],
                ],
            ],
            'Ignore exception message' => [
                <<<TEXT
                Fatal error: Uncaught Exception in /in/hVvRE:5
                Stack trace:
                #0 /in/hVvRE(11): A->__g()
                #1 {main}
                  thrown in /in/hVvRE on line 5
                TEXT,
                [
                    [
                        'file' => '/in/hVvRE',
                        'line' => 11,
                        'class' => 'A',
                        'type' => '->',
                        'function' => '__g',
                    ],
                ],
            ],
        ];
    }

    private function cleanTrace(array $expectedResult): array
    {
        $result = [];
        foreach ($expectedResult as $value) {
            unset($value['args']);
            $result[] = $value;
        }
        return $result;
    }
}