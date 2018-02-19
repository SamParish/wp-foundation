<?php


namespace SamParish\WordPress\Contracts;


use Thunder\Shortcode\Shortcode\ShortcodeInterface;

interface IShortCodeHandler
{
    function handle(ShortcodeInterface $shortcode);
}