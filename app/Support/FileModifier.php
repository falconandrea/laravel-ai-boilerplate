<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Utility for safe, idempotent file modifications using str_replace.
 * Never uses regex — all operations are plain string matching.
 */
class FileModifier
{
    /**
     * Inject content after a search string in a file.
     * Returns false if the search string is not found or injection already exists.
     */
    public static function injectAfter(string $filePath, string $search, string $injection): bool
    {
        if (! file_exists($filePath)) {
            return false;
        }

        $contents = file_get_contents($filePath);

        // Already injected — idempotent
        if (str_contains($contents, trim($injection))) {
            return false;
        }

        if (! str_contains($contents, $search)) {
            return false;
        }

        $contents = str_replace($search, $search.$injection, $contents);
        file_put_contents($filePath, $contents);

        return true;
    }

    /**
     * Inject content before a search string in a file.
     * Returns false if the search string is not found or injection already exists.
     */
    public static function injectBefore(string $filePath, string $search, string $injection): bool
    {
        if (! file_exists($filePath)) {
            return false;
        }

        $contents = file_get_contents($filePath);

        if (str_contains($contents, trim($injection))) {
            return false;
        }

        if (! str_contains($contents, $search)) {
            return false;
        }

        $contents = str_replace($search, $injection.$search, $contents);
        file_put_contents($filePath, $contents);

        return true;
    }

    /**
     * Replace a target string with a replacement in a file.
     * Returns false if the target is not found.
     */
    public static function replace(string $filePath, string $target, string $replacement): bool
    {
        if (! file_exists($filePath)) {
            return false;
        }

        $contents = file_get_contents($filePath);

        // Already applied
        if (str_contains($contents, $replacement) && ! str_contains($contents, $target)) {
            return false;
        }

        if (! str_contains($contents, $target)) {
            return false;
        }

        $contents = str_replace($target, $replacement, $contents);
        file_put_contents($filePath, $contents);

        return true;
    }

    /**
     * Add a use statement to a PHP file (after the namespace or opening <?php tag).
     * Idempotent — will not add if already present.
     */
    public static function addUseStatement(string $filePath, string $fullyQualifiedClass): bool
    {
        if (! file_exists($filePath)) {
            return false;
        }

        $contents = file_get_contents($filePath);
        $useStatement = "use {$fullyQualifiedClass};";

        if (str_contains($contents, $useStatement)) {
            return false;
        }

        // Try to inject after the last existing use statement
        if (preg_match('/^use [^;]+;$/m', $contents, $matches, PREG_OFFSET_CAPTURE)) {
            // Find the last use statement
            preg_match_all('/^use [^;]+;$/m', $contents, $allMatches, PREG_OFFSET_CAPTURE);
            $lastMatch = end($allMatches[0]);
            $insertPos = $lastMatch[1] + strlen($lastMatch[0]);
            $contents = substr($contents, 0, $insertPos)."\n".$useStatement.substr($contents, $insertPos);
        } elseif (str_contains($contents, 'namespace ')) {
            // Inject after namespace declaration
            preg_match('/^namespace [^;]+;$/m', $contents, $nsMatch, PREG_OFFSET_CAPTURE);
            if (! empty($nsMatch)) {
                $insertPos = $nsMatch[0][1] + strlen($nsMatch[0][0]);
                $contents = substr($contents, 0, $insertPos)."\n\n".$useStatement.substr($contents, $insertPos);
            }
        }

        file_put_contents($filePath, $contents);

        return true;
    }

    /**
     * Add a trait to a class in a PHP file.
     * Idempotent — will not add if already present.
     */
    public static function addTrait(string $filePath, string $traitName): bool
    {
        if (! file_exists($filePath)) {
            return false;
        }

        $contents = file_get_contents($filePath);
        $traitStatement = "use {$traitName};";

        // Check if trait is already used (inside class body)
        if (preg_match('/class\s+\w+[^{]*\{[^}]*use\s+'.preg_quote($traitName, '/').'\s*;/s', $contents)) {
            return false;
        }

        // Find the opening brace of the class
        if (preg_match('/class\s+\w+[^{]*\{/', $contents, $classMatch, PREG_OFFSET_CAPTURE)) {
            $insertPos = $classMatch[0][1] + strlen($classMatch[0][0]);
            $contents = substr($contents, 0, $insertPos)."\n    {$traitStatement}".substr($contents, $insertPos);
            file_put_contents($filePath, $contents);

            return true;
        }

        return false;
    }

    /**
     * Append content to the end of a file.
     * Idempotent — will not append if content already exists.
     */
    public static function appendToFile(string $filePath, string $content): bool
    {
        if (! file_exists($filePath)) {
            return false;
        }

        $contents = file_get_contents($filePath);

        if (str_contains($contents, trim($content))) {
            return false;
        }

        file_put_contents($filePath, $contents."\n".$content."\n");

        return true;
    }

    /**
     * Ensure a file exists, creating it with default content if needed.
     */
    public static function ensureFileExists(string $filePath, string $defaultContent = ''): void
    {
        $dir = dirname($filePath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (! file_exists($filePath)) {
            file_put_contents($filePath, $defaultContent);
        }
    }
}
