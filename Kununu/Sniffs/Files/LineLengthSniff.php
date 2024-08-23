<?php

namespace Kununu\Sniffs\Files;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineLengthSniff as PHP_CodeSnifferLineLengthSniff;

class LineLengthSniff extends PHP_CodeSnifferLineLengthSniff
{
    public $lineLimit = 100;
    public $absoluteLineLimit = 120;
    public bool $ignoreUseStatements = false;

    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        for ($i = 1; $i < $phpcsFile->numTokens; $i++) {
            if ($tokens[$i]['column'] === 1) {
                // Skip lines with use statements
                if ($this->ignoreUseStatements && $phpcsFile->findNext(T_USE, $i, $i + 1) !== false) {
                    continue;
                }

                $this->checkLineLength($phpcsFile, $tokens, $i);
            }
        }

        $this->checkLineLength($phpcsFile, $tokens, $i);

        // Ignore the rest of the file.
        return $phpcsFile->numTokens;
    }
}
