<?php

namespace App\Services\Tools;

class ConversionToolsService
{
    /**
     * Convert JSON to CSV
     */
    public function convertJsonToCsv(string $json): array
    {
        try {
            $data = json_decode($json, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'error' => 'Invalid JSON: ' . json_last_error_msg(),
                    'input' => $json
                ];
            }
            
            if (!is_array($data)) {
                return [
                    'success' => false,
                    'error' => 'JSON must contain an array or object',
                    'input' => $json
                ];
            }
            
            $csv = $this->arrayToCsv($data);
            
            return [
                'success' => true,
                'json' => $json,
                'csv' => $csv,
                'rows' => count($data),
                'columns' => $this->getColumnCount($data)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Conversion failed: ' . $e->getMessage(),
                'input' => $json
            ];
        }
    }

    /**
     * Convert CSV to JSON
     */
    public function convertCsvToJson(string $csv): array
    {
        try {
            $lines = str_getcsv($csv, "\n");
            $data = [];
            $headers = [];
            
            foreach ($lines as $index => $line) {
                $row = str_getcsv($line);
                
                if ($index === 0) {
                    $headers = $row;
                } else {
                    $data[] = array_combine($headers, $row);
                }
            }
            
            $json = json_encode($data, JSON_PRETTY_PRINT);
            
            return [
                'success' => true,
                'csv' => $csv,
                'json' => $json,
                'rows' => count($data),
                'columns' => count($headers)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Conversion failed: ' . $e->getMessage(),
                'input' => $csv
            ];
        }
    }

    /**
     * Format and validate JSON
     */
    public function formatValidateJson(string $json): array
    {
        try {
            $decoded = json_decode($json, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'error' => 'Invalid JSON: ' . json_last_error_msg(),
                    'input' => $json,
                    'formatted' => null
                ];
            }
            
            $formatted = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            
            return [
                'success' => true,
                'input' => $json,
                'formatted' => $formatted,
                'is_valid' => true,
                'size_bytes' => strlen($formatted),
                'depth' => $this->getJsonDepth($decoded)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'JSON processing failed: ' . $e->getMessage(),
                'input' => $json
            ];
        }
    }

    /**
     * Simplify JSON structure
     */
    public function simplifyJson(string $json, string $outputStyle = 'original'): array
    {
        try {
            $decoded = json_decode($json, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'error' => 'Invalid JSON: ' . json_last_error_msg(),
                    'input' => $json
                ];
            }
            
            $simplified = $this->simplifyArray($decoded);
            
            $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
            if ($outputStyle === 'compact') {
                $flags |= JSON_NUMERIC_CHECK;
            } else {
                $flags |= JSON_PRETTY_PRINT;
            }
            
            $output = json_encode($simplified, $flags);
            
            return [
                'success' => true,
                'input' => $json,
                'simplified' => $output,
                'output_style' => $outputStyle,
                'original_size' => strlen($json),
                'simplified_size' => strlen($output),
                'compression_ratio' => round((1 - strlen($output) / strlen($json)) * 100, 2)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'JSON simplification failed: ' . $e->getMessage(),
                'input' => $json
            ];
        }
    }

    /**
     * Minify HTML
     */
    public function minifyHtml(string $html): array
    {
        try {
            $minified = $html;
            
            // Remove comments
            $minified = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $minified);
            
            // Remove extra whitespace
            $minified = preg_replace('/\s+/', ' ', $minified);
            
            // Remove whitespace around tags
            $minified = preg_replace('/>\s+</', '><', $minified);
            
            // Remove whitespace at start and end
            $minified = trim($minified);
            
            return [
                'success' => true,
                'original' => $html,
                'minified' => $minified,
                'original_size' => strlen($html),
                'minified_size' => strlen($minified),
                'compression_ratio' => round((1 - strlen($minified) / strlen($html)) * 100, 2)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'HTML minification failed: ' . $e->getMessage(),
                'input' => $html
            ];
        }
    }

    /**
     * Minify CSS or JavaScript
     */
    public function minifyCssJs(string $code, string $type): array
    {
        try {
            $minified = $code;
            
            if ($type === 'css') {
                // Remove comments
                $minified = preg_replace('/\/\*.*?\*\//s', '', $minified);
                
                // Remove extra whitespace
                $minified = preg_replace('/\s+/', ' ', $minified);
                
                // Remove whitespace around specific characters
                $minified = preg_replace('/\s*([{}:;,>+~])\s*/', '$1', $minified);
                
                // Remove trailing semicolons
                $minified = preg_replace('/;}/', '}', $minified);
                
            } elseif ($type === 'js') {
                // Remove single-line comments
                $minified = preg_replace('/\/\/.*$/m', '', $minified);
                
                // Remove multi-line comments
                $minified = preg_replace('/\/\*.*?\*\//s', '', $minified);
                
                // Remove extra whitespace
                $minified = preg_replace('/\s+/', ' ', $minified);
                
                // Remove whitespace around operators
                $minified = preg_replace('/\s*([=+\-*\/%<>!&|^~])\s*/', '$1', $minified);
            }
            
            $minified = trim($minified);
            
            return [
                'success' => true,
                'original' => $code,
                'minified' => $minified,
                'type' => $type,
                'original_size' => strlen($code),
                'minified_size' => strlen($minified),
                'compression_ratio' => round((1 - strlen($minified) / strlen($code)) * 100, 2)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Minification failed: ' . $e->getMessage(),
                'input' => $code,
                'type' => $type
            ];
        }
    }

    /**
     * Convert Markdown to HTML
     */
    public function convertMarkdownToHtml(string $markdown): array
    {
        try {
            $html = $this->parseMarkdown($markdown);
            
            return [
                'success' => true,
                'markdown' => $markdown,
                'html' => $html,
                'original_size' => strlen($markdown),
                'html_size' => strlen($html)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Markdown conversion failed: ' . $e->getMessage(),
                'input' => $markdown
            ];
        }
    }

    /**
     * Encode/Decode HTML entities
     */
    public function encodeDecodeHtmlEntities(string $text, string $operation): array
    {
        try {
            if ($operation === 'encode') {
                $result = htmlentities($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            } elseif ($operation === 'decode') {
                $result = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            } else {
                return [
                    'success' => false,
                    'error' => 'Invalid operation. Use "encode" or "decode"',
                    'operation' => $operation
                ];
            }
            
            return [
                'success' => true,
                'original' => $text,
                'result' => $result,
                'operation' => $operation,
                'original_size' => strlen($text),
                'result_size' => strlen($result)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'HTML entity operation failed: ' . $e->getMessage(),
                'input' => $text,
                'operation' => $operation
            ];
        }
    }

    /**
     * Decode JSON string
     */
    public function decodeJsonString(string $text): array
    {
        try {
            // Try to decode as JSON first
            $jsonDecoded = json_decode($text, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return [
                    'success' => true,
                    'input' => $text,
                    'decoded' => $jsonDecoded,
                    'type' => 'json',
                    'formatted' => json_encode($jsonDecoded, JSON_PRETTY_PRINT)
                ];
            }
            
            // Try to decode as URL encoded
            $urlDecoded = urldecode($text);
            if ($urlDecoded !== $text) {
                return [
                    'success' => true,
                    'input' => $text,
                    'decoded' => $urlDecoded,
                    'type' => 'url_encoded'
                ];
            }
            
            // Try to decode as base64
            $base64Decoded = base64_decode($text, true);
            if ($base64Decoded !== false && base64_encode($base64Decoded) === $text) {
                return [
                    'success' => true,
                    'input' => $text,
                    'decoded' => $base64Decoded,
                    'type' => 'base64'
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Unable to decode string. Not recognized as JSON, URL-encoded, or Base64',
                'input' => $text
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Decoding failed: ' . $e->getMessage(),
                'input' => $text
            ];
        }
    }

    /**
     * Convert XML to JSON
     */
    public function convertXmlToJson(string $xml): array
    {
        try {
            // Load XML
            $xmlObject = simplexml_load_string($xml);
            
            if ($xmlObject === false) {
                return [
                    'success' => false,
                    'error' => 'Invalid XML format',
                    'input' => $xml
                ];
            }
            
            // Convert to array
            $array = json_decode(json_encode($xmlObject), true);
            
            // Convert to JSON
            $json = json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            
            return [
                'success' => true,
                'xml' => $xml,
                'json' => $json,
                'array' => $array,
                'xml_size' => strlen($xml),
                'json_size' => strlen($json)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'XML to JSON conversion failed: ' . $e->getMessage(),
                'input' => $xml
            ];
        }
    }

    /**
     * Encrypt/Decrypt text
     */
    public function encryptDecryptText(string $text, string $operation, string $method, string $key): array
    {
        try {
            if ($operation === 'encrypt') {
                $result = $this->encrypt($text, $method, $key);
            } elseif ($operation === 'decrypt') {
                $result = $this->decrypt($text, $method, $key);
            } else {
                return [
                    'success' => false,
                    'error' => 'Invalid operation. Use "encrypt" or "decrypt"',
                    'operation' => $operation
                ];
            }
            
            return [
                'success' => true,
                'original' => $text,
                'result' => $result,
                'operation' => $operation,
                'method' => $method,
                'key_length' => strlen($key)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Encryption/Decryption failed: ' . $e->getMessage(),
                'input' => $text,
                'operation' => $operation,
                'method' => $method
            ];
        }
    }

    /**
     * Generate file hash
     */
    public function generateFileHash(string $text, string $algorithm): array
    {
        try {
            $algorithms = ['md5', 'sha1', 'sha256', 'sha512', 'crc32'];
            
            if (!in_array($algorithm, $algorithms)) {
                return [
                    'success' => false,
                    'error' => 'Unsupported algorithm. Use: ' . implode(', ', $algorithms),
                    'algorithm' => $algorithm
                ];
            }
            
            $hash = hash($algorithm, $text);
            $fileSize = strlen($text);
            
            return [
                'success' => true,
                'text' => $text,
                'hash' => $hash,
                'algorithm' => $algorithm,
                'file_size' => $fileSize,
                'hash_length' => strlen($hash)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Hash generation failed: ' . $e->getMessage(),
                'input' => $text,
                'algorithm' => $algorithm
            ];
        }
    }

    /**
     * Generate JWT token
     */
    public function generateJWT(string $payload, string $secret): array
    {
        try {
            $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
            $payload = json_encode(json_decode($payload, true));
            
            $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
            $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
            
            $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, $secret, true);
            $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
            
            $jwt = $base64Header . '.' . $base64Payload . '.' . $base64Signature;
            
            return [
                'success' => true,
                'payload' => $payload,
                'jwt' => $jwt,
                'header' => $base64Header,
                'payload_encoded' => $base64Payload,
                'signature' => $base64Signature
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'JWT generation failed: ' . $e->getMessage(),
                'input' => $payload
            ];
        }
    }

    /**
     * Decode JWT token
     */
    public function decodeJWT(string $token, string $secret): array
    {
        try {
            $parts = explode('.', $token);
            
            if (count($parts) !== 3) {
                return [
                    'success' => false,
                    'error' => 'Invalid JWT format',
                    'token' => $token
                ];
            }
            
            list($header, $payload, $signature) = $parts;
            
            // Decode header and payload
            $decodedHeader = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $header)), true);
            $decodedPayload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
            
            // Verify signature
            $expectedSignature = hash_hmac('sha256', $header . '.' . $payload, $secret, true);
            $expectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($expectedSignature));
            
            $isValid = hash_equals($signature, $expectedSignature);
            
            return [
                'success' => true,
                'token' => $token,
                'header' => $decodedHeader,
                'payload' => $decodedPayload,
                'signature_valid' => $isValid,
                'expired' => $this->isJWTExpired($decodedPayload)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'JWT decoding failed: ' . $e->getMessage(),
                'token' => $token
            ];
        }
    }

    /**
     * Convert GPS coordinates
     */
    public function convertGpsCoordinates(string $coordinates, string $fromFormat, string $toFormat): array
    {
        try {
            $parsed = $this->parseCoordinates($coordinates, $fromFormat);
            
            if (!$parsed) {
                return [
                    'success' => false,
                    'error' => 'Invalid coordinate format',
                    'coordinates' => $coordinates,
                    'from_format' => $fromFormat
                ];
            }
            
            $converted = $this->formatCoordinates($parsed, $toFormat);
            
            return [
                'success' => true,
                'original' => $coordinates,
                'from_format' => $fromFormat,
                'to_format' => $toFormat,
                'converted' => $converted,
                'latitude' => $parsed['lat'],
                'longitude' => $parsed['lng'],
                'decimal' => $parsed['lat'] . ', ' . $parsed['lng']
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Coordinate conversion failed: ' . $e->getMessage(),
                'coordinates' => $coordinates,
                'from_format' => $fromFormat,
                'to_format' => $toFormat
            ];
        }
    }

    /**
     * Convert timezone
     */
    public function convertTimezone(string $datetime, string $fromTimezone, string $toTimezone): array
    {
        try {
            $fromTz = new \DateTimeZone($fromTimezone);
            $toTz = new \DateTimeZone($toTimezone);
            
            $date = new \DateTime($datetime, $fromTz);
            $date->setTimezone($toTz);
            
            return [
                'success' => true,
                'original_datetime' => $datetime,
                'from_timezone' => $fromTimezone,
                'to_timezone' => $toTimezone,
                'converted_datetime' => $date->format('Y-m-d H:i:s'),
                'formatted' => $date->format('l, F j, Y g:i A T'),
                'utc_offset' => $date->format('P')
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Timezone conversion failed: ' . $e->getMessage(),
                'datetime' => $datetime,
                'from_timezone' => $fromTimezone,
                'to_timezone' => $toTimezone
            ];
        }
    }

    // Private helper methods

    private function arrayToCsv(array $data): string
    {
        if (empty($data)) {
            return '';
        }
        
        $output = fopen('php://temp', 'r+');
        
        // Get headers from first row
        $headers = array_keys($data[0]);
        fputcsv($output, $headers);
        
        // Write data rows
        foreach ($data as $row) {
            fputcsv($output, array_values($row));
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    private function getColumnCount(array $data): int
    {
        if (empty($data)) {
            return 0;
        }
        
        return count(array_keys($data[0]));
    }

    private function getJsonDepth(array $data, int $depth = 0): int
    {
        $maxDepth = $depth;
        
        foreach ($data as $value) {
            if (is_array($value)) {
                $maxDepth = max($maxDepth, $this->getJsonDepth($value, $depth + 1));
            }
        }
        
        return $maxDepth;
    }

    private function simplifyArray(array $data): array
    {
        $simplified = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $simplified[$key] = $this->simplifyArray($value);
            } elseif (is_string($value)) {
                // Try to convert string numbers to actual numbers
                if (is_numeric($value)) {
                    $simplified[$key] = strpos($value, '.') !== false ? (float)$value : (int)$value;
                } else {
                    $simplified[$key] = $value;
                }
            } else {
                $simplified[$key] = $value;
            }
        }
        
        return $simplified;
    }

    private function parseMarkdown(string $markdown): string
    {
        $html = $markdown;
        
        // Headers
        $html = preg_replace('/^### (.*$)/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^## (.*$)/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.*$)/m', '<h1>$1</h1>', $html);
        
        // Bold and italic
        $html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $html);
        $html = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $html);
        
        // Links
        $html = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $html);
        
        // Code blocks
        $html = preg_replace('/```(.*?)```/s', '<pre><code>$1</code></pre>', $html);
        $html = preg_replace('/`(.*?)`/', '<code>$1</code>', $html);
        
        // Lists
        $html = preg_replace('/^\* (.*$)/m', '<li>$1</li>', $html);
        $html = preg_replace('/^(\d+)\. (.*$)/m', '<li>$1. $2</li>', $html);
        
        // Line breaks
        $html = preg_replace('/\n\n/', '</p><p>', $html);
        $html = '<p>' . $html . '</p>';
        
        return $html;
    }

    private function encrypt(string $text, string $method, string $key): string
    {
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($text, $method, $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    private function decrypt(string $text, string $method, string $key): string
    {
        $data = base64_decode($text);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, $method, $key, 0, $iv);
    }

    private function isJWTExpired(array $payload): bool
    {
        if (!isset($payload['exp'])) {
            return false;
        }
        
        return time() > $payload['exp'];
    }

    private function parseCoordinates(string $coordinates, string $format): ?array
    {
        switch ($format) {
            case 'decimal':
                $parts = explode(',', $coordinates);
                if (count($parts) === 2) {
                    return [
                        'lat' => (float)trim($parts[0]),
                        'lng' => (float)trim($parts[1])
                    ];
                }
                break;
                
            case 'dms':
                // Parse Degrees Minutes Seconds format
                preg_match('/(\d+)°\s*(\d+)\'\s*([\d.]+)"\s*([NS])\s*(\d+)°\s*(\d+)\'\s*([\d.]+)"\s*([EW])/', $coordinates, $matches);
                if (count($matches) === 9) {
                    $lat = $matches[1] + $matches[2]/60 + $matches[3]/3600;
                    $lng = $matches[5] + $matches[6]/60 + $matches[7]/3600;
                    
                    if ($matches[4] === 'S') $lat = -$lat;
                    if ($matches[8] === 'W') $lng = -$lng;
                    
                    return ['lat' => $lat, 'lng' => $lng];
                }
                break;
        }
        
        return null;
    }

    private function formatCoordinates(array $coords, string $format): string
    {
        switch ($format) {
            case 'decimal':
                return $coords['lat'] . ', ' . $coords['lng'];
                
            case 'dms':
                $latDir = $coords['lat'] >= 0 ? 'N' : 'S';
                $lngDir = $coords['lng'] >= 0 ? 'E' : 'W';
                
                $lat = abs($coords['lat']);
                $lng = abs($coords['lng']);
                
                $latDeg = floor($lat);
                $latMin = floor(($lat - $latDeg) * 60);
                $latSec = (($lat - $latDeg) * 60 - $latMin) * 60;
                
                $lngDeg = floor($lng);
                $lngMin = floor(($lng - $lngDeg) * 60);
                $lngSec = (($lng - $lngDeg) * 60 - $lngMin) * 60;
                
                return sprintf('%d°%d\'%.2f"%s %d°%d\'%.2f"%s',
                    $latDeg, $latMin, $latSec, $latDir,
                    $lngDeg, $lngMin, $lngSec, $lngDir
                );
                
            default:
                return $coords['lat'] . ', ' . $coords['lng'];
        }
    }
}
