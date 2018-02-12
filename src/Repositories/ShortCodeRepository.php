<?php


namespace JB000\WordPress\Repositories;


use Illuminate\Contracts\Container\Container;
use JB000\WordPress\Contracts\IShortCodeHandler;
use JB000\WordPress\Models\Post;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;
use Thunder\Shortcode\ShortcodeFacade;

class ShortCodeRepository extends BaseRepository
{
    public function __construct(Container $container, $handlers = [])
    {
        parent::__construct($container, IShortCodeHandler::class, $handlers);
    }


    /**
     * Processes the short codes from the post content and post excerpt through the handlers registered with the repository.
     *
     * @param Post $post
     */
    public function handlePost(Post $post)
    {

        /*
         * we use the thunder/shortcode package to parse the shortcodes.
         *
         * The way we do it is we register the name of the shortcode with a annonymous function that will
         * pass the processing to the correct handler registered with this repo
         */
        $processor = new ShortcodeFacade();

        foreach($this->handlers as $name=>$class)
        {
            //get a instance of the handler
            $instance = $this->makeHandler($name);

            $processor->addHandler($name,function(ShortcodeInterface $shortcode) use($instance)
            {
                //call the handler and return the response
                return $instance->handle($shortcode);
            });

        }
        $post->postContent = $processor->process($post->postContent);
        $post->postExcerpt = $processor->process($post->postExcerpt);
    }

}