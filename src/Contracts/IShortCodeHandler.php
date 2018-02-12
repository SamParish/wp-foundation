<?php


namespace JB000\WordPress\Contracts;


use Thunder\Shortcode\Shortcode\ShortcodeInterface;

interface IShortCodeHandler
{
    function handle(ShortcodeInterface $shortcode);
}