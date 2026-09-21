<?php

function escape($value)
{
    return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
}

function displayText($value)
{
    return nl2br(escape($value));
}

function renderMarkdown($value)
{
    $lines = explode("\n", escape($value));
    $html = "";
    $inUl = false;
    $inOl = false;

    $closeLists = function () use (&$html, &$inUl, &$inOl) {
        if ($inUl) {
            $html .= "</ul>";
            $inUl = false;
        }
        if ($inOl) {
            $html .= "</ol>";
            $inOl = false;
        }
    };

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if ($trimmed === "") {
            $closeLists();
            continue;
        }

        if (preg_match('/^-{3,}$/', $trimmed)) {
            $closeLists();
            $html .= "<hr>";
            continue;
        }

        if (preg_match('/^(#{1,6})\s+(.*)$/', $trimmed, $m)) {
            $closeLists();
            $level = min(strlen($m[1]) + 2, 6);
            $html .= "<h{$level}>" . inlineMarkdown($m[2]) . "</h{$level}>";
            continue;
        }

        if (preg_match('/^[\*\-]\s+(.*)$/', $trimmed, $m)) {
            if ($inOl) {
                $html .= "</ol>";
                $inOl = false;
            }
            if (!$inUl) {
                $html .= "<ul>";
                $inUl = true;
            }
            $html .= "<li>" . inlineMarkdown($m[1]) . "</li>";
            continue;
        }

        if (preg_match('/^\d+\.\s+(.*)$/', $trimmed, $m)) {
            if ($inUl) {
                $html .= "</ul>";
                $inUl = false;
            }
            if (!$inOl) {
                $html .= "<ol>";
                $inOl = true;
            }
            $html .= "<li>" . inlineMarkdown($m[1]) . "</li>";
            continue;
        }

        $closeLists();
        $html .= "<p>" . inlineMarkdown($trimmed) . "</p>";
    }

    $closeLists();

    return $html;
}

function inlineMarkdown($text)
{
    return preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
}