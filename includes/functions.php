<?php

function escape($value)
{
    return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
}

function displayText($value)
{
    return nl2br(escape($value));
}