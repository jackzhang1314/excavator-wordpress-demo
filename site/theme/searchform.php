<?php
/**
 * Styled search form.
 *
 * @package b2b-starter
 */

$search_id = wp_unique_id('site-search-'); ?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr($search_id); ?>">Search equipment and guides</label>
	<input type="search" id="<?php echo esc_attr($search_id); ?>" class="search-field" placeholder="Search products, guides, applications…" value="<?php echo esc_attr((string) get_search_query()); ?>" name="s">
	<button type="submit" class="search-submit">Search</button>
</form>
