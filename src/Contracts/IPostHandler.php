<?php


namespace SamParish\WordPress\Contracts;


use SamParish\WordPress\Models\Post;

interface IPostHandler
{
    function handle(Post $post);
}
