<?php

namespace Modules\WrittenCoursesEngine\app\Services;

class MarkdownParserService
{
    /**
     * Parses a Markdown file with YAML frontmatter.
     *
     * @param string $content
     * @return array
     */
    public function parse(string $content): array
    {
        $frontmatter = [];
        $markdown = $content;

        // Check if the content starts with frontmatter (---)
        if (preg_match('/^---\s*[\r\n]+(.*?)\s*[\r\n]+---\s*[\r\n]+/s', $content, $matches)) {
            $yaml = $matches[1];
            $markdown = substr($content, strlen($matches[0]));
            $frontmatter = $this->parseSimpleYaml($yaml);
        }

        return [
            'frontmatter' => $frontmatter,
            'content'     => $markdown,
        ];
    }

    /**
     * Fallback simple YAML parser for frontmatter.
     *
     * @param string $yaml
     * @return array
     */
    private function parseSimpleYaml(string $yaml): array
    {
        $result = [];
        $lines = explode("\n", str_replace("\r", "", $yaml));

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            if (preg_match('/^([a-zA-Z0-9_-]+)\s*:\s*(.*)$/', $line, $matches)) {
                $key = $matches[1];
                $value = $matches[2];
                $result[$key] = $this->parseValue($value);
            }
        }

        return $result;
    }

    /**
     * Parse the string value into proper types.
     */
    private function parseValue(string $value)
    {
        // Remove quotes if present
        if (preg_match('/^"(.*)"$/', $value, $matches) || preg_match("/^'(.*)'$/", $value, $matches)) {
            return $matches[1];
        }

        // Arrays like ["a", "b"]
        if (preg_match('/^\[(.*)\]$/', $value, $matches)) {
            $items = array_map('trim', explode(',', $matches[1]));
            return array_map(function($item) {
                return trim($item, "\"' ");
            }, $items);
        }

        if (strtolower($value) === 'true') return true;
        if (strtolower($value) === 'false') return false;
        if (strtolower($value) === 'null') return null;

        if (is_numeric($value)) {
            return $value + 0;
        }

        return $value;
    }
}
