<?php


namespace SamParish\WordPress\Handlers;

use JB000\WordPress\Contracts\IPostHandler;
use JB000\WordPress\Models\Post;
use JB000\WordPress\Repositories\ShortCodeRepository;

/**
 * Class ShortCodes
 *
 * @package JB000\WordPress\Handlers
 *
 * This handler will parse the short codes in the post content and excerpt.
 *
 * Each application will have its own use for short codes so all this handler does is pass of the entity
 * to another handler repository specially just for short codes.
 */
class ShortCodes implements IPostHandler
{

    /**
     * @var ShortCodeRepository
     */
    protected $shortCodeRepository;


    public function __construct(ShortCodeRepository $repository)
    {
        $this->shortCodeRepository = $repository;
    }

    function handle(Post $post)
    {
        //simply pass the post onto the short code repository which will do all the hard work.
        $this->shortCodeRepository->handlePost($post);
    }
}