<?php
declare(strict_types=1);

namespace Kununu\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Prevents empty new line before declare(strict_types=1).
 */
class NoNewLineBeforeDeclareStrictSniff implements Sniff
{
    public function register(): array
    {
        return [T_OPEN_TAG];
    }

    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $declare = $phpcsFile->findNext(T_DECLARE, ($stackPtr + 1));

        if ($declare !== false) {
            $isEmptyLine = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), $declare);

            if ($isEmptyLine !== false && str_contains($tokens[$isEmptyLine]['content'], "\n")) {
                $error = 'Empty line before declare(strict_types=1) is not allowed';
                $fix = $phpcsFile->addFixableError($error, $isEmptyLine, 'EmptyLineBeforeDeclare');

                if ($fix === true) {
                    $phpcsFile->fixer->replaceToken($isEmptyLine, '');
                }
            }
        }

        return $phpcsFile->numTokens;
    }
}