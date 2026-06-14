<?php

namespace App\Services\Tools;

class GenerationToolsService
{
    /**
     * Generate API key
     */
    public function generateApiKey(): string
    {
        return 'api_' . bin2hex(random_bytes(32));
    }

    /**
     * Generate password
     */
    public function generatePassword(int $length, bool $uppercase = true, bool $lowercase = true, bool $numbers = true, bool $special = false): array
    {
        if ($length < 4 || $length > 128) {
            return [
                'success' => false,
                'error' => 'Password length must be between 4 and 128 characters',
                'length' => $length
            ];
        }
        
        if (!$uppercase && !$lowercase && !$numbers && !$special) {
            return [
                'success' => false,
                'error' => 'At least one character type must be selected',
                'length' => $length
            ];
        }
        
        $chars = '';
        if ($uppercase) $chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        if ($lowercase) $chars .= 'abcdefghijklmnopqrstuvwxyz';
        if ($numbers) $chars .= '0123456789';
        if ($special) $chars .= '!@#$%^&*()_+-=[]{}|;:,.<>?';
        
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        $strength = $this->assessPasswordStrength($password);
        
        return [
            'success' => true,
            'password' => $password,
            'length' => $length,
            'character_types' => [
                'uppercase' => $uppercase,
                'lowercase' => $lowercase,
                'numbers' => $numbers,
                'special' => $special
            ],
            'strength' => $strength,
            'entropy' => $this->calculateEntropy($password),
            'recommendations' => $this->getPasswordRecommendations($password, false)
        ];
    }

    /**
     * Check password strength
     */
    public function checkPasswordStrength(string $password): array
    {
        $strength = $this->assessPasswordStrength($password);
        $isBreached = $this->checkPasswordBreach($password)['is_breached'] ?? false;
        
        return [
            'success' => true,
            'password' => $password,
            'length' => strlen($password),
            'strength' => $strength,
            'score' => $this->getPasswordScore($password),
            'entropy' => $this->calculateEntropy($password),
            'is_breached' => $isBreached,
            'recommendations' => $this->getPasswordRecommendations($password, $isBreached),
            'analysis' => $this->analyzePassword($password)
        ];
    }

    /**
     * Check if password has been breached
     */
    public function checkPasswordBreach(string $password): array
    {
        try {
            $hash = strtoupper(sha1($password));
            $hashPrefix = substr($hash, 0, 5);
            $hashSuffix = substr($hash, 5);
            
            // Simulate breach check (in production, use HaveIBeenPwned API)
            $breachCount = $this->simulateBreachCheck($hashPrefix, $hashSuffix);
            
            return [
                'success' => true,
                'password' => $password,
                'is_breached' => $breachCount > 0,
                'breach_count' => $breachCount,
                'hash_prefix' => $hashPrefix,
                'recommendation' => $breachCount > 0 ? 'Change this password immediately' : 'Password appears to be safe'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Breach check failed: ' . $e->getMessage(),
                'password' => $password
            ];
        }
    }

    /**
     * Generate random numbers
     */
    public function generateRandomNumbers(int $min, int $max, int $count): array
    {
        if ($min >= $max) {
            return [
                'success' => false,
                'error' => 'Minimum value must be less than maximum value',
                'min' => $min,
                'max' => $max,
                'count' => $count
            ];
        }
        
        if ($count < 1 || $count > 10000) {
            return [
                'success' => false,
                'error' => 'Count must be between 1 and 10,000',
                'min' => $min,
                'max' => $max,
                'count' => $count
            ];
        }
        
        $numbers = [];
        for ($i = 0; $i < $count; $i++) {
            $numbers[] = random_int($min, $max);
        }
        
        return [
            'success' => true,
            'numbers' => $numbers,
            'min' => $min,
            'max' => $max,
            'count' => $count,
            'unique_count' => count(array_unique($numbers)),
            'statistics' => $this->calculateNumberStatistics($numbers)
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
            default:
                return [
                    'success' => false,
                    'error' => 'Invalid type. Use: words, sentences, or paragraphs',
                    'type' => $type
                ];
        }
        
        return [
            'success' => true,
            'generated_text' => $result,
            'type' => $type,
            'count' => $count,
            'word_count' => str_word_count($result),
            'character_count' => strlen($result)
        ];
    }

    /**
     * Generate AI text (simulated)
     */
    public function generateAiText(string $prompt, string $type = 'creative', int $length = 100): array
    {
        $templates = [
            'creative' => [
                'Once upon a time, in a world where {prompt}, there lived a character who discovered that {prompt} was not just an idea, but a way of life.',
                'The concept of {prompt} has fascinated thinkers for centuries, leading to profound insights about the nature of existence and human experience.',
                'In the realm of {prompt}, possibilities are endless and imagination knows no bounds.'
            ],
            'technical' => [
                'The implementation of {prompt} requires careful consideration of various factors including performance, scalability, and maintainability.',
                'When working with {prompt}, developers must follow best practices to ensure code quality and system reliability.',
                'The architecture of {prompt} systems involves multiple components working together to achieve optimal results.'
            ],
            'business' => [
                'The market for {prompt} has shown significant growth over the past decade, with companies investing heavily in this area.',
                'Strategic planning around {prompt} can lead to competitive advantages and improved market positioning.',
                'Customer demand for {prompt} solutions continues to drive innovation and product development.'
            ]
        ];
        
        $template = $templates[$type] ?? $templates['creative'];
        $selectedTemplate = $template[array_rand($template)];
        $generatedText = str_replace('{prompt}', $prompt, $selectedTemplate);
        
        // Extend text to desired length
        while (strlen($generatedText) < $length) {
            $generatedText .= ' ' . $this->generateAdditionalText($type);
        }
        
        $generatedText = substr($generatedText, 0, $length);
        
        return [
            'success' => true,
            'prompt' => $prompt,
            'type' => $type,
            'requested_length' => $length,
            'generated_text' => $generatedText,
            'actual_length' => strlen($generatedText),
            'word_count' => str_word_count($generatedText)
        ];
    }

    /**
     * Generate chart data
     */
    public function generateChart(array $data, string $type = 'line', array $options = []): array
    {
        try {
            $chartData = $this->processChartData($data, $type, $options);
            
            return [
                'success' => true,
                'type' => $type,
                'data' => $chartData,
                'options' => $options,
                'data_points' => count($data),
                'chart_config' => $this->generateChartConfig($type, $chartData, $options)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Chart generation failed: ' . $e->getMessage(),
                'data' => $data,
                'type' => $type
            ];
        }
    }

    /**
     * Generate QR code for WiFi
     */
    public function generateWifiQr(string $ssid, string $password, string $encryption = 'WPA', bool $hidden = false): array
    {
        try {
            $wifiString = $this->generateWifiString($ssid, $password, $encryption, $hidden);
            $qrCodeUrl = $this->generateQrCodeUrl($wifiString);
            
            return [
                'success' => true,
                'ssid' => $ssid,
                'password' => $password,
                'encryption' => $encryption,
                'hidden' => $hidden,
                'wifi_string' => $wifiString,
                'qr_code_url' => $qrCodeUrl,
                'instructions' => 'Scan this QR code with your mobile device to connect to the WiFi network'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'WiFi QR generation failed: ' . $e->getMessage(),
                'ssid' => $ssid
            ];
        }
    }

    /**
     * Generate sitemap
     */
    public function generateSitemap(string $url, int $maxPages = 100): array
    {
        try {
            $pages = $this->crawlWebsite($url, $maxPages);
            
            $sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
            
            foreach ($pages as $page) {
                $sitemap .= '  <url>' . "\n";
                $sitemap .= '    <loc>' . htmlspecialchars($page['url']) . '</loc>' . "\n";
                $sitemap .= '    <lastmod>' . $page['lastmod'] . '</lastmod>' . "\n";
                $sitemap .= '    <changefreq>' . $page['changefreq'] . '</changefreq>' . "\n";
                $sitemap .= '    <priority>' . $page['priority'] . '</priority>' . "\n";
                $sitemap .= '  </url>' . "\n";
            }
            
            $sitemap .= '</urlset>';
            
            return [
                'success' => true,
                'url' => $url,
                'sitemap' => $sitemap,
                'pages_found' => count($pages),
                'max_pages' => $maxPages,
                'pages' => $pages
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Sitemap generation failed: ' . $e->getMessage(),
                'url' => $url
            ];
        }
    }

    // Private helper methods

    private function assessPasswordStrength(string $password): string
    {
        $score = $this->getPasswordScore($password);
        
        if ($score < 3) return 'Very Weak';
        if ($score < 5) return 'Weak';
        if ($score < 7) return 'Fair';
        if ($score < 9) return 'Good';
        return 'Strong';
    }

    private function getPasswordScore(string $password): int
    {
        $score = 0;
        $length = strlen($password);
        
        // Length scoring
        if ($length >= 8) $score += 2;
        if ($length >= 12) $score += 1;
        if ($length >= 16) $score += 1;
        
        // Character variety
        if (preg_match('/[a-z]/', $password)) $score += 1;
        if (preg_match('/[A-Z]/', $password)) $score += 1;
        if (preg_match('/[0-9]/', $password)) $score += 1;
        if (preg_match('/[^a-zA-Z0-9]/', $password)) $score += 2;
        
        // Complexity
        if (preg_match('/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])/', $password)) $score += 1;
        if (preg_match('/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^a-zA-Z0-9])/', $password)) $score += 1;
        
        return min($score, 10);
    }

    private function calculateEntropy(string $password): float
    {
        $charset = 0;
        if (preg_match('/[a-z]/', $password)) $charset += 26;
        if (preg_match('/[A-Z]/', $password)) $charset += 26;
        if (preg_match('/[0-9]/', $password)) $charset += 10;
        if (preg_match('/[^a-zA-Z0-9]/', $password)) $charset += 32;
        
        return log(pow($charset, strlen($password)), 2);
    }

    private function getPasswordRecommendations(string $password, bool $isBreached): array
    {
        $recommendations = [];
        
        if (strlen($password) < 8) {
            $recommendations[] = 'Use at least 8 characters';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $recommendations[] = 'Include lowercase letters';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $recommendations[] = 'Include uppercase letters';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $recommendations[] = 'Include numbers';
        }
        
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $recommendations[] = 'Include special characters';
        }
        
        if ($isBreached) {
            $recommendations[] = 'This password has been found in data breaches - change it immediately';
        }
        
        if (empty($recommendations)) {
            $recommendations[] = 'Password meets basic security requirements';
        }
        
        return $recommendations;
    }

    private function analyzePassword(string $password): array
    {
        return [
            'length' => strlen($password),
            'has_lowercase' => preg_match('/[a-z]/', $password),
            'has_uppercase' => preg_match('/[A-Z]/', $password),
            'has_numbers' => preg_match('/[0-9]/', $password),
            'has_special' => preg_match('/[^a-zA-Z0-9]/', $password),
            'common_patterns' => $this->detectCommonPatterns($password),
            'character_distribution' => $this->analyzeCharacterDistribution($password)
        ];
    }

    private function detectCommonPatterns(string $password): array
    {
        $patterns = [];
        
        if (preg_match('/123|abc|qwe/i', $password)) {
            $patterns[] = 'Sequential characters';
        }
        
        if (preg_match('/(.)\1{2,}/', $password)) {
            $patterns[] = 'Repeated characters';
        }
        
        if (preg_match('/password|123456|qwerty/i', $password)) {
            $patterns[] = 'Common passwords';
        }
        
        if (preg_match('/^\d+$/', $password)) {
            $patterns[] = 'Numbers only';
        }
        
        if (preg_match('/^[a-zA-Z]+$/', $password)) {
            $patterns[] = 'Letters only';
        }
        
        return $patterns;
    }

    private function analyzeCharacterDistribution(string $password): array
    {
        $chars = str_split($password);
        $distribution = array_count_values($chars);
        arsort($distribution);
        
        return [
            'unique_characters' => count($distribution),
            'most_common' => array_slice($distribution, 0, 3, true),
            'distribution_entropy' => $this->calculateDistributionEntropy($distribution)
        ];
    }

    private function calculateDistributionEntropy(array $distribution): float
    {
        $total = array_sum($distribution);
        $entropy = 0;
        
        foreach ($distribution as $count) {
            $probability = $count / $total;
            $entropy -= $probability * log($probability, 2);
        }
        
        return $entropy;
    }

    private function simulateBreachCheck(string $hashPrefix, string $hashSuffix): int
    {
        // This is a simulation - in production, use HaveIBeenPwned API
        // For demo purposes, return 0 (no breach) for most passwords
        $simulatedBreaches = [
            'password' => 1000000,
            '123456' => 500000,
            'qwerty' => 200000
        ];
        
        // Simple simulation based on common passwords
        foreach ($simulatedBreaches as $commonPass => $count) {
            if (strtoupper(sha1($commonPass)) === $hashPrefix . $hashSuffix) {
                return $count;
            }
        }
        
        return 0;
    }

    private function calculateNumberStatistics(array $numbers): array
    {
        return [
            'min' => min($numbers),
            'max' => max($numbers),
            'sum' => array_sum($numbers),
            'average' => round(array_sum($numbers) / count($numbers), 2),
            'median' => $this->calculateMedian($numbers),
            'mode' => $this->calculateMode($numbers)
        ];
    }

    private function calculateMedian(array $numbers): float
    {
        sort($numbers);
        $count = count($numbers);
        $middle = floor($count / 2);
        
        if ($count % 2 === 0) {
            return ($numbers[$middle - 1] + $numbers[$middle]) / 2;
        } else {
            return $numbers[$middle];
        }
    }

    private function calculateMode(array $numbers): array
    {
        $frequency = array_count_values($numbers);
        $maxFrequency = max($frequency);
        
        return array_keys($frequency, $maxFrequency);
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

    private function generateAdditionalText(string $type): string
    {
        $additionalTexts = [
            'creative' => [
                'The possibilities are endless.', 'Imagination knows no bounds.', 'Every moment brings new opportunities.',
                'The journey continues.', 'Dreams become reality.', 'Innovation drives progress.'
            ],
            'technical' => [
                'Performance optimization is crucial.', 'Best practices ensure reliability.', 'Code quality matters.',
                'Scalability is essential.', 'Maintainability is key.', 'Testing is important.'
            ],
            'business' => [
                'Market trends indicate growth.', 'Customer satisfaction is paramount.', 'Strategic planning is essential.',
                'Competitive advantage is crucial.', 'Innovation drives success.', 'Quality service matters.'
            ]
        ];
        
        $texts = $additionalTexts[$type] ?? $additionalTexts['creative'];
        return $texts[array_rand($texts)];
    }

    private function processChartData(array $data, string $type, array $options): array
    {
        switch ($type) {
            case 'line':
            case 'bar':
                return $this->processLineBarData($data);
            case 'pie':
                return $this->processPieData($data);
            default:
                return $data;
        }
    }

    private function processLineBarData(array $data): array
    {
        $processed = [];
        foreach ($data as $item) {
            if (is_array($item) && isset($item['x']) && isset($item['y'])) {
                $processed[] = $item;
            } elseif (is_numeric($item)) {
                $processed[] = ['x' => count($processed) + 1, 'y' => $item];
            }
        }
        return $processed;
    }

    private function processPieData(array $data): array
    {
        $processed = [];
        foreach ($data as $label => $value) {
            if (is_numeric($value)) {
                $processed[] = ['label' => $label, 'value' => $value];
            }
        }
        return $processed;
    }

    private function generateChartConfig(string $type, array $data, array $options): array
    {
        return [
            'type' => $type,
            'data' => $data,
            'options' => array_merge([
                'responsive' => true,
                'maintainAspectRatio' => false,
                'title' => ['display' => true, 'text' => 'Generated Chart']
            ], $options)
        ];
    }

    private function generateWifiString(string $ssid, string $password, string $encryption, bool $hidden): string
    {
        $wifiString = "WIFI:T:{$encryption};S:{$ssid};P:{$password};";
        
        if ($hidden) {
            $wifiString .= "H:true;";
        }
        
        $wifiString .= ";";
        
        return $wifiString;
    }

    private function generateQrCodeUrl(string $data): string
    {
        return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($data);
    }

    private function crawlWebsite(string $url, int $maxPages): array
    {
        $pages = [];
        $visited = [];
        $toVisit = [$url];
        
        while (!empty($toVisit) && count($pages) < $maxPages) {
            $currentUrl = array_shift($toVisit);
            
            if (in_array($currentUrl, $visited)) {
                continue;
            }
            
            $visited[] = $currentUrl;
            
            // Simulate page crawling
            $page = [
                'url' => $currentUrl,
                'lastmod' => date('Y-m-d'),
                'changefreq' => 'weekly',
                'priority' => '0.8'
            ];
            
            $pages[] = $page;
            
            // Simulate finding more links (in production, parse HTML)
            if (count($pages) < $maxPages) {
                $newUrls = $this->simulateLinkDiscovery($currentUrl);
                $toVisit = array_merge($toVisit, array_slice($newUrls, 0, $maxPages - count($pages)));
            }
        }
        
        return $pages;
    }

    private function simulateLinkDiscovery(string $url): array
    {
        // Simulate finding links on a page
        $baseUrl = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST);
        $simulatedLinks = [
            $baseUrl . '/about',
            $baseUrl . '/contact',
            $baseUrl . '/products',
            $baseUrl . '/services',
            $baseUrl . '/blog'
        ];
        
        return array_slice($simulatedLinks, 0, rand(2, 5));
    }
}
