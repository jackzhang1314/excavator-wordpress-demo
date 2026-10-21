<?php
namespace Starter\Model;
defined('ABSPATH') || exit;
function register_blog_page_designs(callable $field, callable $tab): void {
    acf_add_local_field_group([
        'key' => 'group_eq_blog_directory', 'title' => 'Blog overview content', 'show_in_rest' => true,
        'fields' => [
            $tab('field_eq_blog_tab_feature', 'tab_blog_feature', 'Featured reading'),
            $field('field_eq_blog_overline', 'blog_overline', 'Blog overline', 'text'),
            $field('field_eq_blog_featured', 'blog_featured', 'Featured articles', 'relationship', ['post_type' => ['post'], 'post_status' => ['publish'], 'return_format' => 'id', 'filters' => ['search'], 'max' => 3, 'instructions' => 'The first article is the large lead. The next two appear beside it. Drag to reorder. Titles, excerpts, images, authors and categories remain native article fields.']),
            $tab('field_eq_blog_tab_inquiry', 'tab_blog_inquiry', 'Equipment consultation'),
            $field('field_eq_blog_consult_title', 'blog_consult_title', 'Consultation heading', 'text'),
            $field('field_eq_blog_consult_intro', 'blog_consult_intro', 'Consultation introduction', 'textarea', ['rows' => 3]),
            $field('field_eq_blog_consult_image', 'blog_consult_image', 'Consultation photograph', 'image', ['return_format' => 'id', 'preview_size' => 'medium']),
            $field('field_eq_blog_form_title', 'blog_form_title', 'Consultation form heading', 'text'),
            $field('field_eq_blog_show_form', 'blog_show_form', 'Show consultation form', 'true_false', ['ui' => 1]),
            $field('field_eq_blog_range_title', 'blog_range_title', 'Equipment range heading', 'text'),
            $field('field_eq_blog_show_range', 'blog_show_range', 'Show equipment families', 'true_false', ['ui' => 1]),
        ],
        'location' => [[['param' => 'page_type', 'operator' => '==', 'value' => 'posts_page']]],
    ]);
    acf_add_local_field_group([
        'key' => 'group_eq_article_context', 'title' => 'Article context & equipment', 'show_in_rest' => true,
        'fields' => [
            $field('field_eq_article_notice', 'article_notice', 'Editorial notice (optional)', 'text'),
            $field('field_eq_article_context_title', 'article_context_title', 'Sidebar inquiry heading', 'text'),
            $field('field_eq_article_context_intro', 'article_context_intro', 'Sidebar inquiry introduction', 'textarea', ['rows' => 3]),
            $field('field_eq_article_products', 'article_products', 'Related equipment', 'relationship', ['post_type' => ['starter_product'], 'post_status' => ['publish'], 'return_format' => 'id', 'filters' => ['search'], 'max' => 4]),
            $field('field_eq_article_cta_title', 'article_cta_title', 'Closing inquiry heading', 'text'),
            $field('field_eq_article_cta_intro', 'article_cta_intro', 'Closing inquiry introduction', 'textarea', ['rows' => 3]),
            $field('field_eq_article_cta_image', 'article_cta_image', 'Closing inquiry photograph', 'image', ['return_format' => 'id', 'preview_size' => 'medium']),
        ],
        'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'post']]],
    ]);
}
