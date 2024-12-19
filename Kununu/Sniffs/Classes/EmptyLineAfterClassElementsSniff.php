<?php
declare(strict_types=1);

namespace Kununu\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class EmptyLineAfterClassElementsSniff implements Sniff
{
    public function register(): array
    {
        return [T_CLASS];
    }

    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        $classOpen = $tokens[$stackPtr]['scope_opener'];
        $classClose = $tokens[$stackPtr]['scope_closer'];

        $this->checkLastElement($phpcsFile, $classOpen, $classClose, T_CONST, 'constant');

        $this->checkProperties($phpcsFile, $classOpen, $classClose);
    }

    protected function checkProperties(File $phpcsFile, int $scopeStart, int $scopeEnd): void
    {
        $tokens = $phpcsFile->getTokens();
        $lastProperty = null;
        $position = $scopeStart;

        while ($position < $scopeEnd) {
            // Find next visibility modifier or var keyword
            $modifier = $phpcsFile->findNext([T_PUBLIC, T_PROTECTED, T_PRIVATE, T_VAR], $position, $scopeEnd);
            if ($modifier === false) {
                break;
            }

            // Look for the next non-whitespace token
            $nextToken = $phpcsFile->findNext(T_WHITESPACE, $modifier + 1, null, true);
            if ($nextToken === false) {
                break;
            }

            if ($tokens[$nextToken]['code'] === T_FUNCTION) {
                break;
            }

            if ($tokens[$nextToken]['code'] === T_CLOSE_CURLY_BRACKET) {
                break;
            }

            // Check if this is a property declaration by looking for variable or type declaration
            $isProperty = false;
            if ($tokens[$nextToken]['code'] === T_VARIABLE) {
                $isProperty = true;
            } elseif ($tokens[$nextToken]['code'] === T_STRING || $tokens[$nextToken]['code'] === T_NULLABLE) {
                // Look for variable after type declaration
                $variable = $phpcsFile->findNext(T_VARIABLE, $nextToken + 1);
                if ($variable !== false) {
                    $isProperty = true;
                    $nextToken = $variable;
                }
            }

            if ($isProperty) {
                $lastProperty = $nextToken;
            }

            $position = $nextToken + 1;
        }

        if ($lastProperty !== null) {
            // Find the semicolon that ends the property declaration
            $semicolon = $phpcsFile->findNext(T_SEMICOLON, $lastProperty);
            if ($semicolon === false) {
                return;
            }

            // Check the next non-whitespace token
            $nextContent = $phpcsFile->findNext(T_WHITESPACE, $semicolon + 1, null, true);
            if ($nextContent === false) {
                return;
            }

            if ($tokens[$nextContent]['code'] === T_CLOSE_CURLY_BRACKET) {
                return;
            }

            // If there's not exactly one blank line after the last property
            if ($tokens[$nextContent]['line'] !== ($tokens[$semicolon]['line'] + 2)) {
                $error = 'Expected 1 blank line after the last property declaration; %s found';
                $found = $tokens[$nextContent]['line'] - $tokens[$semicolon]['line'] - 1;
                $data = [$found];

                $fix = $phpcsFile->addFixableError($error, $semicolon, 'IncorrectLinesAfterLastProperty', $data);

                if ($fix === true) {
                    $this->fix($phpcsFile, $found, $semicolon, $nextContent, $tokens);
                }
            }
        }
    }

    protected function checkLastElement(
        File $phpcsFile,
        int $scopeStart,
        int $scopeEnd,
        int $tokenType,
        string $elementType
    ): void {
        $tokens = $phpcsFile->getTokens();

        // Find all elements of the given type in the class
        $lastElement = null;
        $position = $scopeStart;

        while ($position < $scopeEnd) {
            $element = $phpcsFile->findNext($tokenType, $position, $scopeEnd);
            if ($element === false) {
                break;
            }

            $lastElement = $element;
            $position = $element + 1;
        }

        // If no elements found, return
        if ($lastElement === null) {
            return;
        }

        // Find the semicolon that ends the last element declaration
        $semicolon = $phpcsFile->findNext(T_SEMICOLON, $lastElement);
        if ($semicolon === false) {
            return;
        }

        // Check the next non-whitespace token
        $nextContent = $phpcsFile->findNext(T_WHITESPACE, $semicolon + 1, null, true);
        if ($nextContent === false) {
            return;
        }

        if ($tokens[$nextContent]['code'] === T_CLOSE_CURLY_BRACKET) {
            return;
        }

        // If there's not exactly one blank line after the last element
        if ($tokens[$nextContent]['line'] !== ($tokens[$semicolon]['line'] + 2)) {
            $error = 'Expected 1 blank line after the last %s declaration; %s found';
            $found = $tokens[$nextContent]['line'] - $tokens[$semicolon]['line'] - 1;
            $data = [$elementType, $found];

            $fix = $phpcsFile->addFixableError(
                $error,
                $semicolon,
                sprintf('IncorrectLinesAfterLast%s', ucfirst($elementType)),
                $data
            );

            if ($fix === true) {
                $this->fix($phpcsFile, $found, $semicolon, $nextContent, $tokens);
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $tokens
     */
    protected function fix(File $phpcsFile, mixed $found, int $semicolon, int $nextContent, array $tokens): void
    {
        $phpcsFile->fixer->beginChangeset();
        if ($found > 1) {
            // Remove extra blank lines
            for ($i = ($semicolon + 1); $i < $nextContent; ++$i) {
                if ($tokens[$i]['line'] > ($tokens[$semicolon]['line'] + 2)) {
                    $phpcsFile->fixer->replaceToken($i, '');
                }
            }
        } else {
            // Add a blank line
            $phpcsFile->fixer->addNewline($semicolon);
        }
        $phpcsFile->fixer->endChangeset();
    }
}
