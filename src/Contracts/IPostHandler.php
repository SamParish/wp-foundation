<?php


namespace JB000\WordPress\Contracts;


use JB000\WordPress\Models\Post;

interface IPostHandler
{
    function handle(Post $post);
}