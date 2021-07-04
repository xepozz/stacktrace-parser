<?php
declare(strict_types=1);

namespace Xepozz\StacktraceParser;

use RuntimeException;

class StacktraceParser
{
    public function parse(string $source): iterable
    {
        return $this->parseRegexp($source);
    }

    private function parseRegexp(string $source): iterable
    {
        $pattern = <<<REGEXP
        /
        (?:\#\d\s{main}[^$]*)
        |
        (?:
            (?:
                \#\d+\s+
                (
                    (?:\[internal\sfunction\])
                    |
                    (?<FILE>.+?)
                    \(
                        (?<LINE>\d+)
                    \)
                )
                :\s+
                (?:
                    (?<CLASS>[\w\\\]+)
                    (?<TYPE>::|->)
                )?
                (?<FUNCTION>[\w_]+)
                \(.*?\)\n
            )
        )
        /x
        REGEXP;


        $matches = [];

        $matches = $this->runRegexp($pattern, $source, $matches);
        $matches = $this->filterMatches($matches);

        return $this->mapResult($matches);
    }

    protected function filterMatches($matches): array
    {
        return array_filter(
            $matches,
            static fn(array $match) => $match['FUNCTION'] !== null || $match['LINE'] !== null
        );
    }

    protected function mapResult(array $matches): array
    {
        return array_map(static function ($match) {
            $result = [
                'class' => (string)$match['CLASS'],
                'type' => $match['TYPE'],
                'function' => $match['FUNCTION'],
            ];
            if ($match['FILE'] !== null) {
                $result['file'] = $match['FILE'];
            }
            if ($match['LINE'] !== null) {
                $result['line'] = (int)$match['LINE'];
            }
            return $result;
        }, $matches);
    }

    protected function runRegexp(string $pattern, string $source, array &$matches): array
    {
        if (!preg_match_all($pattern, $source, $matches, PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL)) {
            throw new RuntimeException(
                'Parse error'
            );
        }
        return $matches;
    }
}