<?php

namespace App\Services\Tools;

class TextToolsService
{
    /**
     * Count words, sentences, paragraphs, and characters in text
     */
    public function countWords(string $text): array
    {
        $wordCount = $this->countWordsMultilingual($text);
        $sentenceCount = $this->countSentencesMultilingual($text);
        $paragraphCount = $this->countParagraphs($text);
        $characterCount = mb_strlen($text);
        $characterCountNoSpaces = mb_strlen(preg_replace('/\s+/', '', $text));
        
        $language = $this->detectLanguage($text);
        $languageInfo = $this->getLanguageSupportInfo($language);
        
        $smsInfo = $this->calculateSmsCount($text);
        
        return [
            'word_count' => $wordCount,
            'sentence_count' => $sentenceCount,
            'paragraph_count' => $paragraphCount,
            'character_count' => $characterCount,
            'character_count_no_spaces' => $characterCountNoSpaces,
            'language' => $language,
            'language_info' => $languageInfo,
            'sms_info' => $smsInfo,
            'reading_time' => $this->calculateReadingTime($wordCount, $language),
            'speaking_time' => $this->calculateSpeakingTime($wordCount, $language)
        ];
    }

    /**
     * Reverse a string
     */
    public function reverseString(string $text): string
    {
        return strrev($text);
    }

    /**
     * Check if text is a palindrome
     */
    public function checkPalindrome(string $text, bool $preserveCase = false, bool $includeSpaces = false): array
    {
        $originalText = $text;
        $cleanText = $this->cleanTextForPalindrome($text, $preserveCase, $includeSpaces);
        $isPalindrome = $this->isPalindrome($cleanText);
        
        return $this->analyzePalindrome($cleanText, $originalText);
    }

    /**
     * Count vowels in text
     */
    public function countVowels(string $text): array
    {
        $vowels = ['a', 'e', 'i', 'o', 'u'];
        $count = 0;
        $vowelCounts = [];
        
        foreach ($vowels as $vowel) {
            $vowelCounts[$vowel] = substr_count(strtolower($text), $vowel);
            $count += $vowelCounts[$vowel];
        }
        
        return [
            'total_vowels' => $count,
            'vowel_breakdown' => $vowelCounts,
            'consonants' => $this->countConsonants($text),
            'vowel_percentage' => $count > 0 ? round(($count / strlen($text)) * 100, 2) : 0
        ];
    }

    /**
     * Caesar cipher encryption/decryption
     */
    public function caesarCipher(string $text, int $shift): string
    {
        $result = '';
        $shift = $shift % 26; // Ensure shift is within 0-25 range
        
        for ($i = 0; $i < strlen($text); $i++) {
            $char = $text[$i];
            
            if (ctype_alpha($char)) {
                $ascii = ord($char);
                $isUpper = ctype_upper($char);
                $base = $isUpper ? 65 : 97;
                $newAscii = (($ascii - $base + $shift + 26) % 26) + $base;
                $result .= chr($newAscii);
            } else {
                $result .= $char;
            }
        }
        
        return $result;
    }

    /**
     * Character counter with detailed analysis
     */
    public function characterCounter(string $text): array
    {
        $characters = $this->analyzeCharacterTypes($text);
        
        return [
            'total_characters' => strlen($text),
            'characters_no_spaces' => strlen(preg_replace('/\s+/', '', $text)),
            'characters' => $characters,
            'most_common' => $this->getMostCommonCharacters($text),
            'unique_characters' => count(array_unique(str_split($text)))
        ];
    }

    /**
     * Case converter
     */
    public function caseConverter(string $text, string $mode): array
    {
        $result = $text;
        
        switch ($mode) {
            case 'uppercase':
                $result = strtoupper($text);
                break;
            case 'lowercase':
                $result = strtolower($text);
                break;
            case 'title_case':
                $result = ucwords(strtolower($text));
                break;
            case 'camel_case':
                $result = lcfirst(str_replace(' ', '', ucwords(strtolower($text))));
                break;
            case 'snake_case':
                $result = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $text));
                break;
            case 'kebab_case':
                $result = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $text));
                break;
            case 'alternating':
                $result = $this->alternatingCase($text);
                break;
        }
        
        return [
            'original' => $text,
            'converted' => $result,
            'mode' => $mode,
            'character_count' => strlen($result)
        ];
    }

    /**
     * Remove duplicate lines
     */
    public function removeDuplicateLines(string $text, bool $keepOrder = true): array
    {
        $lines = explode("\n", $text);
        $originalCount = count($lines);
        
        if ($keepOrder) {
            $uniqueLines = array_unique($lines);
        } else {
            $uniqueLines = array_values(array_unique($lines));
        }
        
        $removedCount = $originalCount - count($uniqueLines);
        
        return [
            'original_text' => $text,
            'processed_text' => implode("\n", $uniqueLines),
            'original_lines' => $originalCount,
            'unique_lines' => count($uniqueLines),
            'removed_lines' => $removedCount
        ];
    }

    /**
     * Sort lines
     */
    public function sortLines(string $text, string $direction = 'asc', bool $caseSensitive = false): array
    {
        $lines = explode("\n", $text);
        $originalCount = count($lines);
        
        if ($caseSensitive) {
            if ($direction === 'asc') {
                sort($lines);
            } else {
                rsort($lines);
            }
        } else {
            if ($direction === 'asc') {
                sort($lines, SORT_STRING | SORT_FLAG_CASE);
            } else {
                rsort($lines, SORT_STRING | SORT_FLAG_CASE);
            }
        }
        
        return [
            'original_text' => $text,
            'sorted_text' => implode("\n", $lines),
            'line_count' => $originalCount,
            'direction' => $direction,
            'case_sensitive' => $caseSensitive
        ];
    }

    /**
     * Text cleaner with various options
     */
    public function textCleaner(string $text, array $options): array
    {
        $result = $text;
        $changes = [];
        
        if ($options['remove_extra_spaces'] ?? false) {
            $result = preg_replace('/\s+/', ' ', $result);
            $changes[] = 'Removed extra spaces';
        }
        
        if ($options['remove_empty_lines'] ?? false) {
            $result = preg_replace('/^\s*$/m', '', $result);
            $changes[] = 'Removed empty lines';
        }
        
        if ($options['trim_lines'] ?? false) {
            $lines = explode("\n", $result);
            $lines = array_map('trim', $lines);
            $result = implode("\n", $lines);
            $changes[] = 'Trimmed lines';
        }
        
        return [
            'original_text' => $text,
            'cleaned_text' => $result,
            'changes_made' => $changes,
            'character_reduction' => strlen($text) - strlen($result)
        ];
    }

    /**
     * Text diff comparison
     */
    public function textDiff(string $textA, string $textB): array
    {
        $linesA = explode("\n", $textA);
        $linesB = explode("\n", $textB);
        
        $diff = [];
        $maxLines = max(count($linesA), count($linesB));
        
        for ($i = 0; $i < $maxLines; $i++) {
            $lineA = $linesA[$i] ?? '';
            $lineB = $linesB[$i] ?? '';
            
            if ($lineA === $lineB) {
                $diff[] = ['type' => 'equal', 'line' => $lineA];
            } else {
                $diff[] = ['type' => 'removed', 'line' => $lineA];
                $diff[] = ['type' => 'added', 'line' => $lineB];
            }
        }
        
        return [
            'text_a' => $textA,
            'text_b' => $textB,
            'diff' => $diff,
            'similarity' => $this->calculateSimilarity($textA, $textB)
        ];
    }

    /**
     * Find and replace text
     */
    public function findAndReplace(string $text, string $find, string $replace, bool $isRegex = false, bool $caseSensitive = false): array
    {
        $originalText = $text;
        $flags = $caseSensitive ? '' : 'i';
        
        if ($isRegex) {
            $result = preg_replace('/' . $find . '/' . $flags, $replace, $text);
            $count = preg_match_all('/' . $find . '/' . $flags, $text);
        } else {
            if ($caseSensitive) {
                $result = str_replace($find, $replace, $text);
                $count = substr_count($text, $find);
            } else {
                $result = str_ireplace($find, $replace, $text);
                $count = substr_count(strtolower($text), strtolower($find));
            }
        }
        
        return [
            'original_text' => $originalText,
            'modified_text' => $result,
            'find' => $find,
            'replace' => $replace,
            'replacements_made' => $count,
            'is_regex' => $isRegex,
            'case_sensitive' => $caseSensitive
        ];
    }

    /**
     * Base64 encode
     */
    public function base64Encode(string $text): string
    {
        return base64_encode($text);
    }

    /**
     * Base64 decode
     */
    public function base64Decode(string $text): array
    {
        $decoded = base64_decode($text, true);
        
        if ($decoded === false) {
            return [
                'success' => false,
                'error' => 'Invalid base64 string',
                'original' => $text
            ];
        }
        
        return [
            'success' => true,
            'decoded_text' => $decoded,
            'original' => $text,
            'is_binary' => $this->isBinary($decoded)
        ];
    }

    /**
     * Summarize text
     */
    public function summarizeText(string $text, int $sentences): array
    {
        $sentences = $this->splitIntoSentences($text);
        $wordCount = $this->countWordsMultilingual($text);
        
        if (count($sentences) <= $sentences) {
            return [
                'original_text' => $text,
                'summary' => $text,
                'sentences_requested' => $sentences,
                'sentences_found' => count($sentences),
                'word_count' => $wordCount
            ];
        }
        
        // Simple summarization - take first N sentences
        $summary = implode('. ', array_slice($sentences, 0, $sentences)) . '.';
        
        return [
            'original_text' => $text,
            'summary' => $summary,
            'sentences_requested' => $sentences,
            'sentences_found' => count($sentences),
            'word_count' => $wordCount,
            'compression_ratio' => round((strlen($summary) / strlen($text)) * 100, 2)
        ];
    }

    /**
     * Check keyword density
     */
    public function checkKeywordDensity(string $text): array
    {
        $words = str_word_count(strtolower($text), 1);
        $wordCount = count($words);
        $wordFreq = array_count_values($words);
        
        arsort($wordFreq);
        $topWords = array_slice($wordFreq, 0, 10, true);
        
        $keywordDensity = [];
        foreach ($topWords as $word => $count) {
            $keywordDensity[$word] = round(($count / $wordCount) * 100, 2);
        }
        
        return [
            'text' => $text,
            'total_words' => $wordCount,
            'unique_words' => count($wordFreq),
            'top_keywords' => $topWords,
            'keyword_density' => $keywordDensity
        ];
    }

    /**
     * Generate Lorem Ipsum text
     */
    public function generateLoremIpsum(int $count, string $type): array
    {
        $loremWords = [
            'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit',
            'sed', 'do', 'eiusmod', 'tempor', 'incididunt', 'ut', 'labore', 'et', 'dolore',
            'magna', 'aliqua', 'enim', 'ad', 'minim', 'veniam', 'quis', 'nostrud',
            'exercitation', 'ullamco', 'laboris', 'nisi', 'aliquip', 'ex', 'ea', 'commodo',
            'consequat', 'duis', 'aute', 'irure', 'in', 'reprehenderit', 'voluptate',
            'velit', 'esse', 'cillum', 'fugiat', 'nulla', 'pariatur', 'excepteur', 'sint',
            'occaecat', 'cupidatat', 'non', 'proident', 'sunt', 'culpa', 'qui', 'officia',
            'deserunt', 'mollit', 'anim', 'id', 'est', 'laborum'
        ];
        
        $result = '';
        
        switch ($type) {
            case 'words':
                $selectedWords = array_slice($loremWords, 0, min($count, count($loremWords)));
                $result = implode(' ', $selectedWords);
                break;
            case 'sentences':
                for ($i = 0; $i < $count; $i++) {
                    $sentence = $this->generateSentence($loremWords);
                    $result .= $sentence . ' ';
                }
                $result = trim($result);
                break;
            case 'paragraphs':
                for ($i = 0; $i < $count; $i++) {
                    $paragraph = $this->generateParagraph($loremWords);
                    $result .= $paragraph . "\n\n";
                }
                $result = trim($result);
                break;
        }
        
        return [
            'generated_text' => $result,
            'type' => $type,
            'count' => $count,
            'word_count' => str_word_count($result),
            'character_count' => strlen($result)
        ];
    }

    /**
     * Format text in various ways
     */
    public function formatText(string $text, string $format): array
    {
        $result = $text;
        
        switch ($format) {
            case 'uppercase':
                $result = strtoupper($text);
                break;
            case 'lowercase':
                $result = strtolower($text);
                break;
            case 'title_case':
                $result = ucwords(strtolower($text));
                break;
            case 'camel_case':
                $result = lcfirst(str_replace(' ', '', ucwords(strtolower($text))));
                break;
            case 'snake_case':
                $result = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $text));
                break;
            case 'kebab_case':
                $result = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $text));
                break;
            case 'pascal_case':
                $result = str_replace(' ', '', ucwords(strtolower($text)));
                break;
            case 'constant_case':
                $result = strtoupper(preg_replace('/([a-z])([A-Z])/', '$1_$2', $text));
                break;
        }
        
        return [
            'original_text' => $text,
            'formatted_text' => $result,
            'format' => $format,
            'character_count' => strlen($result)
        ];
    }

    /**
     * Test regex patterns
     */
    public function testRegex(string $pattern, string $text, string $flags): array
    {
        $regex = '/' . $pattern . '/' . $flags;
        $matches = [];
        $matchCount = preg_match_all($regex, $text, $matches, PREG_OFFSET_CAPTURE);
        
        $results = [];
        if ($matchCount > 0) {
            foreach ($matches[0] as $match) {
                $results[] = [
                    'match' => $match[0],
                    'position' => $match[1],
                    'length' => strlen($match[0])
                ];
            }
        }
        
        return [
            'pattern' => $pattern,
            'text' => $text,
            'flags' => $flags,
            'regex' => $regex,
            'match_count' => $matchCount,
            'matches' => $results,
            'is_valid' => $this->isValidRegex($pattern)
        ];
    }

    /**
     * Check spelling and grammar (basic implementation)
     */
    public function checkSpellingGrammar(string $text): array
    {
        // This is a basic implementation - in a real app, you'd use a proper spell checker
        $words = str_word_count($text, 1);
        $suggestions = [];
        $errors = [];
        
        foreach ($words as $word) {
            if (strlen($word) < 3) continue; // Skip very short words
            
            // Basic spell check - check if word contains only letters
            if (!ctype_alpha($word)) {
                $errors[] = [
                    'word' => $word,
                    'type' => 'invalid_characters',
                    'suggestion' => preg_replace('/[^a-zA-Z]/', '', $word)
                ];
            }
        }
        
        return [
            'text' => $text,
            'word_count' => count($words),
            'errors' => $errors,
            'error_count' => count($errors),
            'suggestions' => $suggestions
        ];
    }

    // Private helper methods

    private function countWordsMultilingual(string $text): int
    {
        // Remove extra whitespace and normalize
        $text = preg_replace('/\s+/', ' ', trim($text));
        
        if (empty($text)) {
            return 0;
        }
        
        // Split by whitespace and count non-empty elements
        $words = preg_split('/\s+/', $text);
        $words = array_filter($words, function($word) {
            return !empty(trim($word));
        });
        
        return count($words);
    }

    private function countSentencesMultilingual(string $text): int
    {
        if (empty(trim($text))) {
            return 0;
        }
        
        // Common sentence endings
        $sentenceEndings = ['[.!?]', '[。！？]', '[।॥]', '[؟!]'];
        $pattern = '/(' . implode('|', $sentenceEndings) . ')\s*/u';
        
        $sentences = preg_split($pattern, $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentences = array_filter($sentences, function($sentence) {
            return !empty(trim($sentence));
        });
        
        return count($sentences);
    }

    private function countParagraphs(string $text): int
    {
        if (empty(trim($text))) {
            return 0;
        }
        
        $paragraphs = preg_split('/\n\s*\n/', $text);
        $paragraphs = array_filter($paragraphs, function($paragraph) {
            return !empty(trim($paragraph));
        });
        
        return count($paragraphs);
    }

    private function calculateSmsCount(string $text): array
    {
        $length = strlen($text);
        $smsCount = 1;
        
        if ($length > 160) {
            $smsCount = ceil($length / 153); // 153 characters per SMS after first
        }
        
        return [
            'character_count' => $length,
            'sms_count' => $smsCount,
            'characters_remaining' => $smsCount == 1 ? 160 - $length : 153 - ($length % 153)
        ];
    }

    private function detectLanguage(string $text): string
    {
        // Simple language detection based on character sets
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $text)) {
            return 'Arabic';
        } elseif (preg_match('/[\x{4E00}-\x{9FFF}]/u', $text)) {
            return 'Chinese';
        } elseif (preg_match('/[\x{3040}-\x{309F}]/u', $text)) {
            return 'Japanese';
        } elseif (preg_match('/[\x{AC00}-\x{D7AF}]/u', $text)) {
            return 'Korean';
        } elseif (preg_match('/[\x{0400}-\x{04FF}]/u', $text)) {
            return 'Cyrillic';
        } else {
            return 'English';
        }
    }

    private function getLanguageSupportInfo(string $language): array
    {
        $languages = [
            'English' => [
                'description' => 'Full support with space-separated words and standard punctuation',
                'word_separator' => ' ', 
                'sentence_endings' => ['.', '!', '?']
            ],
            'Arabic' => [
                'description' => 'Complete RTL support with proper Arabic punctuation and word separation',
                'word_separator' => ' ', 
                'sentence_endings' => ['؟', '!', '.']
            ],
            'Chinese' => [
                'description' => 'Character-based counting with Chinese punctuation marks',
                'word_separator' => '', 
                'sentence_endings' => ['。', '！', '？']
            ],
            'Japanese' => [
                'description' => 'Multi-script support for Hiragana, Katakana, and Kanji with Japanese punctuation',
                'word_separator' => '', 
                'sentence_endings' => ['。', '！', '？']
            ],
            'Korean' => [
                'description' => 'Hangul script support with Korean punctuation marks',
                'word_separator' => ' ', 
                'sentence_endings' => ['。', '！', '？']
            ],
            'Cyrillic' => [
                'description' => 'Cyrillic script support for Russian and other Slavic languages',
                'word_separator' => ' ', 
                'sentence_endings' => ['.', '!', '?']
            ]
        ];
        
        return $languages[$language] ?? $languages['English'];
    }

    private function calculateReadingTime(int $wordCount, string $language): int
    {
        $wordsPerMinute = [
            'English' => 200,
            'Arabic' => 150,
            'Chinese' => 300,
            'Japanese' => 300,
            'Korean' => 200,
            'Cyrillic' => 180
        ];
        
        $wpm = $wordsPerMinute[$language] ?? 200;
        return max(1, round($wordCount / $wpm));
    }

    private function calculateSpeakingTime(int $wordCount, string $language): int
    {
        $wordsPerMinute = [
            'English' => 150,
            'Arabic' => 120,
            'Chinese' => 200,
            'Japanese' => 200,
            'Korean' => 150,
            'Cyrillic' => 140
        ];
        
        $wpm = $wordsPerMinute[$language] ?? 150;
        return max(1, round($wordCount / $wpm));
    }

    private function cleanTextForPalindrome(string $text, bool $preserveCase, bool $includeSpaces): string
    {
        $cleaned = $text;
        
        if (!$includeSpaces) {
            $cleaned = preg_replace('/\s+/', '', $cleaned);
        }
        
        if (!$preserveCase) {
            $cleaned = strtolower($cleaned);
        }
        
        // Remove punctuation
        $cleaned = preg_replace('/[^\p{L}\p{N}\s]/u', '', $cleaned);
        
        return $cleaned;
    }

    private function isPalindrome(string $text): bool
    {
        return $text === strrev($text);
    }

    private function analyzePalindrome(string $cleanText, string $originalText): array
    {
        $isPalindrome = $this->isPalindrome($cleanText);
        $length = strlen($cleanText);
        
        return [
            'is_palindrome' => $isPalindrome,
            'original_text' => $originalText,
            'cleaned_text' => $cleanText,
            'length' => $length,
            'character_analysis' => $this->analyzeCharacterTypes($cleanText),
            'language_family' => $this->detectLanguageFamily($originalText)
        ];
    }

    private function detectLanguageFamily(string $text): string
    {
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $text)) {
            return 'Semitic';
        } elseif (preg_match('/[\x{4E00}-\x{9FFF}]/u', $text)) {
            return 'Sino-Tibetan';
        } elseif (preg_match('/[\x{3040}-\x{309F}]/u', $text)) {
            return 'Japonic';
        } elseif (preg_match('/[\x{AC00}-\x{D7AF}]/u', $text)) {
            return 'Koreanic';
        } elseif (preg_match('/[\x{0400}-\x{04FF}]/u', $text)) {
            return 'Slavic';
        } else {
            return 'Indo-European';
        }
    }

    private function isWordPalindrome(array $words): bool
    {
        return $words === array_reverse($words);
    }

    private function analyzeCharacterTypes(string $text): array
    {
        return [
            'letters' => preg_match_all('/[a-zA-Z]/', $text),
            'digits' => preg_match_all('/[0-9]/', $text),
            'spaces' => preg_match_all('/\s/', $text),
            'punctuation' => preg_match_all('/[^\w\s]/', $text),
            'uppercase' => preg_match_all('/[A-Z]/', $text),
            'lowercase' => preg_match_all('/[a-z]/', $text)
        ];
    }

    private function countConsonants(string $text): int
    {
        return preg_match_all('/[bcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ]/', $text);
    }

    private function alternatingCase(string $text): string
    {
        $result = '';
        $upper = true;
        
        for ($i = 0; $i < strlen($text); $i++) {
            $char = $text[$i];
            if (ctype_alpha($char)) {
                $result .= $upper ? strtoupper($char) : strtolower($char);
                $upper = !$upper;
            } else {
                $result .= $char;
            }
        }
        
        return $result;
    }

    private function getMostCommonCharacters(string $text): array
    {
        $chars = str_split($text);
        $charCount = array_count_values($chars);
        arsort($charCount);
        
        return array_slice($charCount, 0, 5, true);
    }

    private function calculateSimilarity(string $textA, string $textB): float
    {
        $lenA = strlen($textA);
        $lenB = strlen($textB);
        
        if ($lenA == 0 && $lenB == 0) return 100.0;
        if ($lenA == 0 || $lenB == 0) return 0.0;
        
        $maxLen = max($lenA, $lenB);
        $levenshtein = levenshtein($textA, $textB);
        
        return round((($maxLen - $levenshtein) / $maxLen) * 100, 2);
    }

    private function isBinary(string $text): bool
    {
        return preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $text) === 1;
    }

    private function splitIntoSentences(string $text): array
    {
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        return array_map('trim', $sentences);
    }

    private function generateSentence(array $words): string
    {
        $sentenceLength = rand(8, 15);
        $sentenceWords = array_rand($words, min($sentenceLength, count($words)));
        
        if (!is_array($sentenceWords)) {
            $sentenceWords = [$sentenceWords];
        }
        
        $sentence = '';
        foreach ($sentenceWords as $index) {
            $sentence .= $words[$index] . ' ';
        }
        
        return ucfirst(trim($sentence)) . '.';
    }

    private function generateParagraph(array $words): string
    {
        $sentenceCount = rand(3, 6);
        $paragraph = '';
        
        for ($i = 0; $i < $sentenceCount; $i++) {
            $paragraph .= $this->generateSentence($words) . ' ';
        }
        
        return trim($paragraph);
    }

    private function isValidRegex(string $pattern): bool
    {
        return @preg_match('/' . $pattern . '/', '') !== false;
    }
}
