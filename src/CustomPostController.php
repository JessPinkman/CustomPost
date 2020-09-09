<?php

namespace CustomPost;

class CustomPostController
{
    public static $slug = 'post';

    public function __construct($id, bool $load_meta = true)
    {
        $this->ID = $id;
        if ($load_meta && $meta = $this->getMeta('', true)) {
            foreach ($meta as $key => $val) {
                $this->$key = $val[0];
            }
        }
    }

    public static function register(array $args)
    {
        add_action('init', function () use ($args) {
            \register_post_type(static::$slug, $args);
        });
    }

    public static function getListID($status = 'publish', int $count = -1, ?array $args = null)
    {
        $setup = [
            'post_type'     => static::$slug,
            'post_status'   => $status,
            'numberposts'   => $count,
            'fields'        => 'ids',
        ];

        if ($args) {
            $setup = \array_merge($setup, $args);
        }

        return \get_posts($setup);
    }

    public static function getObjects($status = 'publish', int $count = -1, ?array $args = null)
    {
        $objects = [];
        $list = static::getListID($status, $count, $args);
        foreach ($list as $id) {
            $objects[] = new static($id);
        }

        \usort($objects, function ($a, $b) {
            $a->getMenuOrder() > $b->getMenuOrder();
        });

        return $objects;
    }

    public static function verifyID(int $id, ?array $status = ['publish'])
    {
        $post = get_post($id);

        if (!$post) {
            return false;
        }
        $type = $post->post_type;

        if ($status) {
            $post_status = $post->post_status;
            return \in_array($post_status, $status) && $type === static::$slug;
        } else {
            return $type === static::$slug;
        }
    }

    public function getTags($string_only = true)
    {
        $tags = \get_the_tags($this->ID);
        return !$tags || !$string_only ? $tags : \array_column($tags, 'name');
    }

    public function getThumbnail($size = 'post-thumbnail')
    {
        return \get_the_post_thumbnail_url($this->ID, $size);
    }

    public function getTitle()
    {
        return get_the_title($this->ID);
    }

    public function getExcerpt(): string
    {
        return \get_the_excerpt($this->ID);
    }

    public function getContent(): string
    {
        $content = \get_the_content(null, false, $this->ID);
        $content = \apply_filters('the_content', $content);
        $content = str_replace(']]>', ']]&gt;', $content);

        return $content;
    }

    public function getPermalink(): string
    {
        return \get_the_permalink($this->ID);
    }

    public static function getArchiveLink(): string
    {
        return \get_post_type_archive_link(static::$slug);
    }

    public function getMeta(string $key, bool $single = false)
    {
        return get_post_meta($this->ID, $key, $single);
    }

    public function getMenuOrder(): int
    {
        return (int) (\get_post_field('menu_order', $this->ID) ?? 0);
    }

    public static function isArchivePage(): bool
    {
        return \is_post_type_archive(static::$slug);
    }

    public function getResponsiveThumbnail(): string
    {
        return \get_the_post_thumbnail($this->ID);
    }

    public function getThumbnailID(): int
    {
        return \get_post_thumbnail_id($this->ID);
    }
}
