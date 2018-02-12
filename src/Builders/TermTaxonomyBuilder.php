<?php


namespace JB000\WordPress\Builders;


use Illuminate\Database\Eloquent\Builder;

class TermTaxonomyBuilder extends Builder
{

    public function posts()
    {
        return $this->with('posts');
    }


    public function category()
    {
        return $this->where('taxonomy', 'category');
    }


    public function menu()
    {
        return $this->where('taxonomy', 'nav_menu');
    }


    public function slug($slug = null)
    {
        if (!is_null($slug) and !empty($slug))
        {

            // exception to filter on specific slug
            $exception = function ($query) use ($slug)
            {
                $query->where('slug', '=', $slug);
            };

            // load term to filter
            return $this->whereHas('term', $exception);
        }
        return $this;
    }
}