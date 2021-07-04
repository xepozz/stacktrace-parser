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
        return [
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
            'Complex stacktrace' => [
                ($exception1 = new Exception())->getTraceAsString(),
                $exception1->getTrace(),
            ],
            'Stacktrace with previous one' => [
                <<<TEXT
                PHP Fatal error:  Uncaught RuntimeException: parent in /in/hVvRE:23
                Stack trace:
                #0 /in/hVvRE(29): THIS_WILL_NOT_PARSE_ONE()
                #1 /in/hVvRE(34): THIS_WILL_NOT_PARSE_TWO()
                #2 {main}
                
                Next RuntimeException: child in /in/hVvRE:29
                Stack trace:
                #0 /in/hVvRE(34): THIS_WILL_NOT_PARSE_TWO()
                #1 {main}
                  thrown in /in/hVvRE on line 29
                
                Fatal error: Uncaught RuntimeException: parent in /in/hVvRE:23
                Stack trace:
                #0 /in/hVvRE(29): function_one()
                #1 /in/hVvRE(34): function_two()
                #2 {main}
               TEXT
                /**
                 * Need to append test with
                 *
                 * Next RuntimeException: child in /in/hVvRE on line 29
                 *
                 * RuntimeException: child in /in/hVvRE on line 29
                 *
                 * Call Stack:
                 * 0.0007     393304   1. {main}() /in/hVvRE:0
                 *
                 * and make it worked
                 */
                ,
                [
                    [
                        'file' => '/in/hVvRE',
                        'line' => 29,
                        'class' => '',
                        'type' => '',
                        'function' => 'function_one',
                    ],
                    [
                        'file' => '/in/hVvRE',
                        'line' => 34,
                        'class' => '',
                        'type' => '',
                        'function' => 'function_two',
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