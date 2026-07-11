<?php

namespace App\Services;

use Exception;

class JsonMergeService extends BaseService
{
    /**
     * Process JSON merge request
     */
    public function mergeJsonInput(string $jsonInput, string $mergeStrategy = 'deep'): array
    {
        // Parse JSON objects - handle both single multiline JSON and multiple JSON objects
        $jsonObjects = $this->parseJsonInput($jsonInput);

        if (empty($jsonObjects)) {
            throw new Exception('No valid JSON objects found');
        }

        // Merge JSON objects based on strategy
        return $this->mergeJsonObjects($jsonObjects, $mergeStrategy);
    }

    /**
     * Merge JSON objects based on the specified strategy
     */
    private function mergeJsonObjects(array $objects, string $strategy): array
    {
        if (empty($objects)) {
            return [];
        }

        $result = array_shift($objects);

        foreach ($objects as $object) {
            switch ($strategy) {
                case 'deep':
                    $result = $this->deepMerge($result, $object);
                    break;
                case 'shallow':
                    $result = array_merge($result, $object);
                    break;
                case 'preserve_duplicates':
                    $result = $this->preserveDuplicatesMerge($result, $object);
                    break;
            }
        }

        return $result;
    }

    /**
     * Deep merge two arrays, combining nested arrays and objects
     */
    private function deepMerge(array $array1, array $array2): array
    {
        $merged = $array1;

        foreach ($array2 as $key => $value) {
            if (isset($merged[$key]) && is_array($merged[$key]) && is_array($value)) {
                $merged[$key] = $this->deepMerge($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * Merge preserving duplicate keys by converting them to arrays
     */
    private function preserveDuplicatesMerge(array $array1, array $array2): array
    {
        $merged = $array1;

        foreach ($array2 as $key => $value) {
            if (isset($merged[$key])) {
                // If key already exists, convert to array or append to existing array
                if (! is_array($merged[$key]) || ! isset($merged[$key][0])) {
                    $merged[$key] = [$merged[$key]];
                }
                $merged[$key][] = $value;
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * Parse JSON input - handles both single multiline JSON and multiple JSON objects
     */
    private function parseJsonInput(string $jsonInput): array
    {
        $jsonInput = trim($jsonInput);
        $jsonObjects = [];

        // First, try to parse as a single JSON object (even if multiline)
        $decoded = json_decode($jsonInput, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            // It's a valid single JSON object
            // Check for potential duplicate keys using a simple heuristic
            // Look for any quoted key that appears more than once
            if (preg_match_all('/"([^"]+)"\s*:/', $jsonInput, $matches)) {
                $keys = $matches[1];
                $duplicateKeys = array_diff_assoc($keys, array_unique($keys));

                if (! empty($duplicateKeys)) {
                    // Possible duplicate keys detected, do detailed check
                    if ($this->hasDuplicateKeys($jsonInput)) {
                        // Has duplicate keys, process them
                        $processedJson = $this->convertDuplicateKeysToSeparateObjects($jsonInput);

                        // Parse the JSON objects properly - they are separated by newlines but may contain internal newlines
                        $jsonObjects = $this->parseMultipleJsonObjects($processedJson);

                        return $jsonObjects;
                    }
                }
            }

            // No duplicate keys detected or processing not needed
            $jsonObjects[] = $decoded;

            return $jsonObjects;
        }

        // If single JSON parsing failed, try splitting by lines for multiple JSON objects
        // $jsonLines = array_filter(explode("\n", $jsonInput));

        $jsonLines = array_filter($this->extractJsonStrings($jsonInput));

        foreach ($jsonLines as $lineNumber => $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Try to parse each line as valid JSON first
            $decoded = json_decode($line, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $jsonObjects[] = $decoded;

                continue;
            }

            // If parsing failed, try duplicate key processing
            try {
                $processedJson = $this->preprocessDuplicateKeys($line);

                // The preprocessing might return multiple lines if duplicates were found
                $processedLines = array_filter(explode("\n", $processedJson));

                foreach ($processedLines as $processedLine) {
                    $processedLine = trim($processedLine);
                    if (empty($processedLine)) {
                        continue;
                    }

                    $decoded = json_decode($processedLine, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new Exception('Invalid JSON on line '.($lineNumber + 1).': '.$processedLine.'. Error: '.json_last_error_msg());
                    }
                    $jsonObjects[] = $decoded;
                }
            } catch (Exception $e) {
                throw new Exception('Invalid JSON on line '.($lineNumber + 1).': '.$line.'. Error: '.$e->getMessage());
            }
        }

        return $jsonObjects;
    }

    public function extractJsonStrings(string $input): array
    {
        $input = trim($input);
        $results = [];

        $depth = 0;   // brace nesting level
        $inString = false;
        $buffer = '';

        $len = strlen($input);
        for ($i = 0; $i < $len; $i++) {
            $c = $input[$i];

            // Toggle in/out of quoted strings (ignore escaped quotes)
            if ($c === '"' && ($i === 0 || $input[$i - 1] !== '\\')) {
                $inString = ! $inString;
            }

            // When we see an opening brace outside quotes,
            // start (or deepen) collection.
            if (! $inString && $c === '{') {
                if ($depth === 0) {
                    $buffer = '';          // new JSON object begins
                }
                $depth++;
            }

            // If currently collecting, add the character
            if ($depth > 0) {
                $buffer .= $c;
            }

            // Closing brace outside quotes
            if (! $inString && $c === '}') {
                $depth--;
                if ($depth === 0) {        // JSON object complete
                    $results[] = trim($buffer);
                    $buffer = '';
                }
            }
        }

        return $results;
    }

    /**
     * Preprocess JSON string to handle duplicate keys by converting to array format
     */
    private function preprocessDuplicateKeys(string $jsonString): string
    {
        $jsonString = trim($jsonString);

        // First, check if it's valid JSON - if so and no duplicates, return as-is
        $testDecode = json_decode($jsonString, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            // Valid JSON - check if we actually have duplicate keys by manual inspection
            if (! $this->hasDuplicateKeys($jsonString)) {
                return $jsonString; // No duplicate keys, return original
            }
        }

        // Check if it contains potential duplicate keys by looking for patterns
        if (! preg_match('/["\']([^"\']+)["\']:\s*[^,}]+,.*["\']\\1["\']:\s*/', $jsonString)) {
            return $jsonString; // No duplicate keys detected by regex either
        }

        // Convert single object with duplicate keys into multiple objects
        return $this->convertDuplicateKeysToSeparateObjects($jsonString);
    }

    /**
     * Check if JSON string has duplicate top-level keys
     */
    private function hasDuplicateKeys(string $jsonString): bool
    {
        if (! str_starts_with(trim($jsonString), '{')) {
            return false;
        }

        try {
            // Remove outer braces and parse manually to get only top-level keys
            $content = trim($jsonString);
            if (substr($content, 0, 1) === '{' && substr($content, -1) === '}') {
                $content = substr($content, 1, -1);
                $content = trim($content);
            }

            $pairs = $this->extractTopLevelPairs($content);

            // Extract just the keys
            $keys = array_map(function ($pair) {
                return $pair['key'];
            }, $pairs);

            // Check for duplicates
            return count($keys) !== count(array_unique($keys));
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Convert a JSON object with duplicate keys into separate objects for merging
     */
    private function convertDuplicateKeysToSeparateObjects(string $jsonString): string
    {
        // Parse manually to handle complex nested structures
        $jsonString = trim($jsonString);

        // Remove outer braces
        $content = trim($jsonString, '{}');
        $content = trim($content);

        // Parse key-value pairs manually with proper nesting support
        $pairs = $this->extractTopLevelPairs($content);

        if (empty($pairs)) {
            return $jsonString;
        }

        // Group by keys
        $keyGroups = [];
        foreach ($pairs as $pair) {
            $key = $pair['key'];
            if (! isset($keyGroups[$key])) {
                $keyGroups[$key] = [];
            }
            $keyGroups[$key][] = $pair['value'];
        }

        // Find which keys have duplicates
        $duplicateKeys = array_filter($keyGroups, function ($values) {
            return count($values) > 1;
        });

        if (empty($duplicateKeys)) {
            return $jsonString;
        }

        // Create objects
        $objects = [];

        // First object: all unique keys + first instance of duplicate keys
        $firstObjectPairs = [];
        foreach ($keyGroups as $key => $values) {
            $firstObjectPairs[] = '"'.$key.'": '.$values[0];
        }
        $objects[] = '{'.implode(', ', $firstObjectPairs).'}';

        // Additional objects for duplicate instances
        $maxDuplicates = max(array_map('count', $duplicateKeys));

        for ($i = 1; $i < $maxDuplicates; $i++) {
            $objectPairs = [];
            foreach ($duplicateKeys as $key => $values) {
                if (isset($values[$i])) {
                    $objectPairs[] = '"'.$key.'": '.$values[$i];
                }
            }

            if (! empty($objectPairs)) {
                $objects[] = '{'.implode(', ', $objectPairs).'}';
            }
        }

        return implode("\n", $objects);
    }

    /**
     * Extract top-level key-value pairs from JSON content
     */
    private function extractTopLevelPairs(string $content): array
    {
        $pairs = [];
        $length = strlen($content);
        $i = 0;

        while ($i < $length) {
            // Skip whitespace
            while ($i < $length && in_array($content[$i], [' ', "\t", "\n", "\r"])) {
                $i++;
            }

            if ($i >= $length) {
                break;
            }

            // Skip comma
            if ($content[$i] === ',') {
                $i++;

                continue;
            }

            // Find the key
            if ($content[$i] !== '"') {
                break; // Invalid format
            }

            $keyStart = $i + 1;
            $i++; // Skip opening quote

            // Find closing quote of key
            while ($i < $length && $content[$i] !== '"') {
                if ($content[$i] === '\\') {
                    $i += 2; // Skip escaped character
                } else {
                    $i++;
                }
            }

            if ($i >= $length) {
                break;
            }

            $key = substr($content, $keyStart, $i - $keyStart);
            $i++; // Skip closing quote

            // Skip whitespace and find colon
            while ($i < $length && in_array($content[$i], [' ', "\t", "\n", "\r"])) {
                $i++;
            }

            if ($i >= $length || $content[$i] !== ':') {
                break; // Invalid format
            }

            $i++; // Skip colon

            // Skip whitespace
            while ($i < $length && in_array($content[$i], [' ', "\t", "\n", "\r"])) {
                $i++;
            }

            // Extract the value
            $valueStart = $i;
            $value = $this->extractValue($content, $i);

            if ($value !== null) {
                $pairs[] = [
                    'key' => $key,
                    'value' => $value,
                ];
            }
        }

        return $pairs;
    }

    /**
     * Extract a JSON value starting at position $i and update $i to the end position
     */
    private function extractValue(string $content, int &$i): ?string
    {
        $length = strlen($content);
        $start = $i;

        if ($i >= $length) {
            return null;
        }

        $char = $content[$i];

        if ($char === '"') {
            // String value
            $i++; // Skip opening quote
            while ($i < $length && $content[$i] !== '"') {
                if ($content[$i] === '\\') {
                    $i += 2; // Skip escaped character
                } else {
                    $i++;
                }
            }
            if ($i < $length) {
                $i++;
            } // Skip closing quote

        } elseif ($char === '{') {
            // Object value - need to handle quotes properly
            $depth = 0;
            $inString = false;

            while ($i < $length) {
                $char = $content[$i];

                if (! $inString) {
                    if ($char === '"') {
                        $inString = true;
                    } elseif ($char === '{') {
                        $depth++;
                    } elseif ($char === '}') {
                        $depth--;
                        if ($depth === 0) {
                            $i++; // Include the closing brace
                            break;
                        }
                    }
                } else {
                    // We're inside a string
                    if ($char === '"' && ($i === 0 || $content[$i - 1] !== '\\')) {
                        $inString = false;
                    }
                }

                $i++;
            }

        } elseif ($char === '[') {
            // Array value - need to handle quotes properly
            $depth = 0;
            $inString = false;

            while ($i < $length) {
                $char = $content[$i];

                if (! $inString) {
                    if ($char === '"') {
                        $inString = true;
                    } elseif ($char === '[') {
                        $depth++;
                    } elseif ($char === ']') {
                        $depth--;
                        if ($depth === 0) {
                            $i++; // Include the closing bracket
                            break;
                        }
                    }
                } else {
                    // We're inside a string
                    if ($char === '"' && ($i === 0 || $content[$i - 1] !== '\\')) {
                        $inString = false;
                    }
                }

                $i++;
            }

        } else {
            // Primitive value (number, boolean, null)
            while ($i < $length && ! in_array($content[$i], [',', '}', ']', ' ', "\t", "\n", "\r"])) {
                $i++;
            }
        }

        return substr($content, $start, $i - $start);
    }

    /**
     * Parse multiple JSON objects that are separated by newlines but may contain internal newlines
     */
    private function parseMultipleJsonObjects(string $jsonString): array
    {
        $jsonObjects = [];
        $lines = explode("\n", $jsonString);
        $currentJson = '';
        $braceDepth = 0;
        $inString = false;

        foreach ($lines as $line) {
            $currentJson .= $line;

            // Count braces to determine when we have a complete JSON object
            for ($i = 0; $i < strlen($line); $i++) {
                $char = $line[$i];

                if (! $inString) {
                    if ($char === '"' && ($i === 0 || $line[$i - 1] !== '\\')) {
                        $inString = true;
                    } elseif ($char === '{') {
                        $braceDepth++;
                    } elseif ($char === '}') {
                        $braceDepth--;
                    }
                } else {
                    if ($char === '"' && ($i === 0 || $line[$i - 1] !== '\\')) {
                        $inString = false;
                    }
                }
            }

            // If we've closed all braces, we have a complete JSON object
            if ($braceDepth === 0 && ! empty(trim($currentJson))) {
                $decoded = json_decode($currentJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Invalid JSON after processing: '.substr($currentJson, 0, 50).'... | Error: '.json_last_error_msg());
                }
                $jsonObjects[] = $decoded;
                $currentJson = '';
            } else {
                // Add newline back since we're continuing the same JSON object
                $currentJson .= "\n";
            }
        }

        // Handle any remaining JSON
        if (! empty(trim($currentJson))) {
            $decoded = json_decode($currentJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON after processing: '.substr($currentJson, 0, 50).'... | Error: '.json_last_error_msg());
            }
            $jsonObjects[] = $decoded;
        }

        return $jsonObjects;
    }
}
