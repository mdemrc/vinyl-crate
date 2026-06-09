<?php

function format_duration($seconds) {
    $seconds = (int)$seconds;
    $minutes = intdiv($seconds, 60);
    $rest = $seconds % 60;
    return $minutes . ":" . str_pad($rest, 2, "0", STR_PAD_LEFT);
}

function render_stars($rating) {
    $rating = (int)round($rating);
    $html = '<span class="stars" title="' . $rating . '/5">';
    for ($i = 1; $i <= 5; $i++) {
        $class = $i <= $rating ? "star on" : "star";
        $html .= '<span class="' . $class . '">&#9733;</span>';
    }
    $html .= '</span>';
    return $html;
}

function decade_label($year) {
    $decade = (int)(floor($year / 10) * 10);
    return $decade . "s";
}

function parse_duration($value) {
    $value = trim($value);
    if ($value === "") {
        return null;
    }
    if (strpos($value, ":") !== false) {
        $parts = explode(":", $value);
        $minutes = (int)$parts[0];
        $seconds = (int)($parts[1] ?? 0);
        return $minutes * 60 + $seconds;
    }
    return is_numeric($value) ? (int)$value : null;
}

function slugify($text) {
    $text = strtolower(trim($text));
    $text = preg_replace("/[^a-z0-9]+/", "-", $text);
    return trim($text, "-");
}
?>
