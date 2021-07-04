<?php
declare(strict_types=1);

namespace Xepozz\StacktraceParser;

use Phplrt\Compiler\Compiler;
use Phplrt\Compiler\SampleNode;
use Phplrt\Contracts\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Throwable;

class StacktraceParser
{
    private Compiler $compiler;

    public function __construct(Compiler $compiler)
    {
        $this->compiler = $compiler;
    }

    /**
     * @param string $source
     * @return iterable
     * @throws RuntimeExceptionInterface
     * @throws Throwable
     */
    public function parse(string $source): iterable
    {
        $ast = $this->parseByPP2($source);

        return $this->parseAstToArray($ast);
    }

    /**
     * @param SampleNode $ast
     * @return iterable
     */
    private function parseAstToArray(SampleNode $ast): iterable
    {
        $resultTrace = [];
        /** @var SampleNode|TokenInterface $item */
        foreach ($ast->children as $i => $child) {
            switch ($child->getState()) {
                case 'TraceLine':
                    $iterator = $child->getIterator();
                    foreach ($iterator as $value) {
                        if (is_iterable($value)) {
                            $resultTrace[$i] = [];
                            $this->iterate($child, $value, $resultTrace[$i]);
                        }
                    }
            }
        }
        return $resultTrace;
    }

    /**
     * @param string $source
     * @return SampleNode
     * @throws RuntimeExceptionInterface
     * @throws Throwable
     */
    private function parseByPP2(string $source): iterable
    {
        $this->compiler->load(<<<EBNF
        %token  T_NUMBER                    \d+
        %token  T_COLON                     :
        %token  T_BRACKET_OPEN              \(
        %token  T_BRACKET_CLOSE             \)
        %token  T_LINE_NUMBER               \#\d+
        %skip   T_WHITESPACE                \s+
        %skip   T_EXCEPTION_MESSAGE         .+?:.+in\s.+?:\d+$
        %skip  T_STACKTRACE                Stack\strace:$
        %skip  T_THROWN_IN                 thrown\sin\s.+?\son\sline\s\d+$

        %token  T_INTERNAL_FUNCTION         \[internal\sfunction\]
        %token  T_FILE_PATH                 /.+?(?=\(\d+\):)
        %token  T_MAIN_FUNCTIONAL           \{.+?\}
        %token  T_CLASS_AND_FUNCTION_CALL   (?:(?<T_CLASS>[\w\\\]+?)(?<T_FUNCTION_CALL_TYPE>\-\>|::))?(?<T_FUNCTION>[\w_]+?)\(.*?\)$

        #Expression
          : TheStacktraceLine()? TraceLine()* MainTraceLine() ThrownInLine()?
          ;
        
        #TraceLine
          : ::T_LINE_NUMBER:: (FilePathAndLine() | ::T_INTERNAL_FUNCTION::)::T_COLON::<T_CLASS_AND_FUNCTION_CALL>?
          ;
        #MainTraceLine
          : ::T_LINE_NUMBER:: <T_MAIN_FUNCTIONAL>
          ;
        #TheStacktraceLine
          : ::T_STACKTRACE:: 
          ;
        #ThrownInLine
          : <T_THROWN_IN> 
          ;
        #FilePathAndLine
          : <T_FILE_PATH>::T_BRACKET_OPEN::<T_NUMBER>::T_BRACKET_CLOSE::
          ;
        EBNF
        );

        return $this->compiler->parse($source);
    }

    private function iterate($parent, iterable $iterable, array &$carry): void
    {
        foreach ($iterable as $value) {
            if (is_iterable($value)) {
                $this->iterate($iterable, $value, $carry);
            }
            if (!$value instanceof TokenInterface) {
                continue;
            }
            switch ($value->getName()) {
                case 'T_INTERNAL_FUNCTION':
                case 'T_FILE_PATH':
                    $carry['file'] = $value->getValue();
                    break;
                case 'T_CLASS':
                    $carry['class'] = $value->getValue();
                    break;
                case 'T_FUNCTION_CALL_TYPE':
                    $carry['type'] = $value->getValue();
                    break;
                case 'T_FUNCTION':
                    $carry['function'] = $value->getValue();
                    break;
                case 'T_NUMBER':
                    if ($parent instanceof SampleNode) {
                        if ($parent->getState() === 'FilePathAndLine') {
                            $carry['line'] = (int)$value->getValue();
                        }
                    }
                    break;
            }
        }
    }

}