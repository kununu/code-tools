<?php
declare(strict_types=1);

namespace Kununu\Sniffs\Formatting;

use PHP_CodeSniffer\Exceptions\DeepExitException;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Based on MethodSignatureParametersLineBreakMethodSniff from https://github.com/spryker/code-sniffer/blob/master/Spryker/Sniffs/Formatting/MethodSignatureParametersLineBreakMethodSniff.php which has MIT license.
 *
 * Prevent the usage of multiline for short method signatures and single lines for long ones.
 */
class MethodSignatureArgumentsSniff implements Sniff
{
    public $methodSignatureLengthHardBreak = 120;

    public $methodSignatureLengthSoftBreak = 80;

    public $methodSignatureNumberParameterSoftBreak = 3;

    public function register(): array
    {
        return [
            T_FUNCTION,
        ];
    }

    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $openParenthesisPosition = $tokens[$stackPtr]['parenthesis_opener'];
        $closeParenthesisPosition = $tokens[$stackPtr]['parenthesis_closer'];

        $isSingleLineSignature = $this->areTokensOnTheSameLine($tokens, $openParenthesisPosition, $closeParenthesisPosition);
        $signatureLength = $this->getMethodSignatureLength($phpcsFile, $stackPtr);
        $parametersCount = count($phpcsFile->getMethodParameters($stackPtr));
        if ($isSingleLineSignature) {
            // single line only allowed when the length don't go over the hard break or there are no parameters
            if (
                $signatureLength <= $this->methodSignatureLengthHardBreak
                || $parametersCount === 0
            ) {
                return;
            }

            $fix = $phpcsFile->addFixableError('The parameters on this method definition need to be multi-line.', $stackPtr, 'Multiline');
            if (!$fix) {
                return;
            }

            $this->makeMethodSignatureMultiline($phpcsFile, $stackPtr);

            return;
        }

        // multiline allowed when signature is longer than the soft break
        if ($signatureLength >= $this->methodSignatureLengthSoftBreak) {
            return;
        }
        // multiline allowed if parameter count is too high.
        if (
            $parametersCount >= $this->methodSignatureNumberParameterSoftBreak
        ) {
            return;
        }
        $fix = $phpcsFile->addFixableError('The parameters on this method definition need to be on a single line.', $stackPtr, 'Inline');
        if (!$fix) {
            return;
        }

        $this->makeMethodSignatureSingleLine($phpcsFile, $stackPtr);
    }

    protected function makeMethodSignatureSingleLine(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        $openParenthesisPosition = $tokens[$stackPtr]['parenthesis_opener'];
        $closeParenthesisPosition = $tokens[$stackPtr]['parenthesis_closer'];
        // if null, it's an interface or abstract method.
        $scopeOpenerPosition = $tokens[$stackPtr]['scope_opener'] ?? null;
        $parameters = $phpcsFile->getMethodParameters($stackPtr);
        $properties = $phpcsFile->getMethodProperties($stackPtr);
        $returnTypePosition = $properties['return_type_token'];
        $indentation = $this->getIndentationWhitespace($phpcsFile, $stackPtr);

        $content = [];
        foreach ($parameters as $parameter) {
            $content[] = $parameter['content'];
        }
        $formattedParameters = implode(', ', $content);

        $phpcsFile->fixer->beginChangeset();
        if ($scopeOpenerPosition !== null) {
            $this->removeEverythingBetweenPositions($phpcsFile, $closeParenthesisPosition, $scopeOpenerPosition);
            $phpcsFile->fixer->addContentBefore($scopeOpenerPosition, "\n" . $indentation);
            if ($returnTypePosition !== false) {
                $phpcsFile->fixer->addContent($closeParenthesisPosition, ': ' . $tokens[$returnTypePosition]['content']);
            }
        }
        $this->removeEverythingBetweenPositions($phpcsFile, $openParenthesisPosition, $closeParenthesisPosition);
        $phpcsFile->fixer->addContentBefore($closeParenthesisPosition, $formattedParameters);
        $phpcsFile->fixer->endChangeset();
    }

    protected function makeMethodSignatureMultiline(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $openParenthesisPosition = $tokens[$stackPtr]['parenthesis_opener'];
        $closeParenthesisPosition = $tokens[$stackPtr]['parenthesis_closer'];
        // if null, it's an interface or abstract method.
        $scopeOpenerPosition = $tokens[$stackPtr]['scope_opener'] ?? null;

        $parameters = $phpcsFile->getMethodParameters($stackPtr);

        $formattedParameters = "\n";
        $parameterContent = [];
        $indentation = $this->getIndentationWhitespace($phpcsFile, $stackPtr);
        foreach ($parameters as $parameter) {
            $parameterContent[] = str_repeat($indentation, 2) . $parameter['content'];
        }
        $formattedParameters .= implode(",\n", $parameterContent);
        $formattedParameters .= "\n$indentation";

        $phpcsFile->fixer->beginChangeset();
        $this->removeEverythingBetweenPositions($phpcsFile, $openParenthesisPosition, $closeParenthesisPosition);
        $phpcsFile->fixer->addContentBefore($closeParenthesisPosition, $formattedParameters);
        if ($scopeOpenerPosition !== null) {
            if (!$this->areTokensOnTheSameLine($tokens, $closeParenthesisPosition, $scopeOpenerPosition)) {
                $endOfPreviousLine = $this->getLineEndingPosition($tokens, $closeParenthesisPosition);
                $this->removeEverythingBetweenPositions($phpcsFile, $endOfPreviousLine - 1, $scopeOpenerPosition);
                $phpcsFile->fixer->addContentBefore($scopeOpenerPosition, ' ');
            }
        }
        $phpcsFile->fixer->endChangeset();
    }

    protected function removeEverythingBetweenPositions(File $phpcsFile, int $fromPosition, int $toPosition): void
    {
        for ($i = $fromPosition + 1; $i < $toPosition; ++$i) {
            $phpcsFile->fixer->replaceToken($i, '');
        }
    }

    protected function areTokensOnTheSameLine(array $tokens, int $firstPosition, int $secondPosition): bool
    {
        return $tokens[$firstPosition]['line'] === $tokens[$secondPosition]['line'];
    }

    protected function getMethodSignatureLength(File $phpcsFile, int $stackPtr): int
    {
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$stackPtr]['code'] !== T_FUNCTION) {
            throw new DeepExitException('This can only be run on a method signature.');
        }
        $openParenthesisPosition = $tokens[$stackPtr]['parenthesis_opener'];
        $closeParenthesisPosition = $tokens[$stackPtr]['parenthesis_closer'];

        $methodProperties = $phpcsFile->getMethodProperties($stackPtr);
        $methodParameters = $phpcsFile->getMethodParameters($stackPtr);
        if ($this->areTokensOnTheSameLine($tokens, $openParenthesisPosition, $closeParenthesisPosition)) {
            return $this->getMethodSingleLineSignatureLength($tokens, $stackPtr);
        }

        return $this->getMethodSignatureMultilineLength($tokens, $stackPtr, $methodProperties, $methodParameters);
    }

    protected function getIndentationWhitespace(File $phpcsFile, int $prevIndex): string
    {
        $tokens = $phpcsFile->getTokens();

        $firstIndex = $this->getFirstTokenOfLine($tokens, $prevIndex);
        $whitespace = '';
        if ($tokens[$firstIndex]['type'] === 'T_WHITESPACE' || $tokens[$firstIndex]['type'] === 'T_DOC_COMMENT_WHITESPACE') {
            $whitespace = $tokens[$firstIndex]['content'];
        }

        return $whitespace;
    }

    protected function getLineEndingPosition(array $tokens, int $position): int
    {
        while (!empty($tokens[$position]) && !str_contains($tokens[$position]['content'], PHP_EOL)) {
            ++$position;
        }

        return $position;
    }

    protected function getMethodSingleLineSignatureLength(array $tokens, int $stackPtr): int
    {
        $position = $this->getLineEndingPosition($tokens, $stackPtr);

        return $tokens[$position]['column'] - 1;
    }

    protected function getFirstTokenOfLine(array $tokens, int $index): int
    {
        $line = $tokens[$index]['line'];

        $currentIndex = $index;
        while ($tokens[$currentIndex - 1]['line'] === $line) {
            --$currentIndex;
        }

        return $currentIndex;
    }

    protected function getMethodSignatureMultilineLength(
        array $tokens,
        int $stackPtr,
        array $methodProperties,
        array $methodParameters
    ): int {
        $totalLength = $this->getMethodSingleLineSignatureLength($tokens, $stackPtr);
        $firstLineEndPosition = $this->getLineEndingPosition($tokens, $stackPtr);
        foreach ($methodParameters as $parameter) {
            if ($tokens[$parameter['token']]['line'] === $tokens[$stackPtr]['line']) {
                // the parameters are on the first line of the signature.
                if ($tokens[$firstLineEndPosition - 1]['code'] === T_COMMA) {
                    // space after comma.
                    ++$totalLength;
                }

                continue;
            }
            $totalLength += $this->getParameterTotalLength($parameter);
            if ($parameter['comma_token'] !== false) {
                // comma + space
                $totalLength += 2;
            }
        }
        // closing parenthesis
        ++$totalLength;
        // column (:) and space before the returnType
        $totalLength += mb_strlen($methodProperties['return_type']) + 2;

        return $totalLength;
    }

    protected function getParameterTotalLength(array $methodParameter): int
    {
        $length = 0;
        $length += mb_strlen($methodParameter['content']);

        return $length;
    }
}
