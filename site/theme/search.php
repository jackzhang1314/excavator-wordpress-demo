<?php
/**
 * Search results.
 *
 * @package b2b-starter
 */

get_header(); ?>
<main id="main" class="eq-page eq-search-results shell">
	<?php Starter\Theme\component_page_head([
		'eyebrow' => 'Search',
		'title' => 'Results for “' . get_search_query() . '”',
		'description' => sprintf(_n('%d result found', '%d results found', (int) $GLOBALS['wp_query']->found_posts), (int) $GLOBALS['wp_query']->found_posts),
	]); ?>
	<?php get_search_form(); ?>
	<?php Starter\Theme\component_card_grid(['main_query' => true, 'pagination' => true, 'empty_text' => 'No results. Try a product type, certification or application.']); ?>
</main>
<?php get_footer(); ?>
