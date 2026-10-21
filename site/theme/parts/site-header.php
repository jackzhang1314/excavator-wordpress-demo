<?php
/** Approved equipment header; links remain native WordPress menu records. */
$note = Starter\Theme\get_setting('topbar_text');
?>
<div class="eq-utility"><div class="shell">
    <?php if ($note !== '') : ?><span class="eq-review-note"><?php echo esc_html($note); ?></span><?php endif; ?>
    <div class="eq-utility-actions"><?php wp_nav_menu(['theme_location' => 'utility', 'container' => false, 'fallback_cb' => false, 'depth' => 1, 'menu_class' => 'eq-utility-links']); ?>
    <div id="eq-language-origin"><?php get_template_part('parts/language-switcher'); ?></div></div>
</div></div>
<header class="site-header"><div class="shell">
<div class="brand"><?php if (has_custom_logo()) : the_custom_logo(); else : ?><a href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a><?php endif; ?></div>
<button class="eq-search-toggle" type="button" aria-label="Search website" aria-haspopup="dialog" aria-controls="eq-site-search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="10" cy="10" r="7"/><path d="m15 15 6 6"/></svg></button>
<button class="nav-toggle" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="primary-nav"><span></span><span></span><span></span></button>
<nav id="primary-nav" class="primary-nav" aria-label="Primary"><?php wp_nav_menu(['theme_location' => 'primary', 'container' => false, 'fallback_cb' => false, 'depth' => 3, 'menu_class' => 'primary-menu']); ?><div id="eq-mobile-language"></div></nav>
<a class="button header-cta" href="<?php echo esc_url(Starter\Theme\contact_url()); ?>"><?php echo esc_html(Starter\Theme\get_setting('header_cta_label', 'Request a Quote')); ?> <span aria-hidden="true">→</span></a>
</div></header>
<dialog id="eq-site-search" class="eq-search-dialog" aria-labelledby="eq-site-search-title"><div class="eq-search-dialog-heading"><h2 id="eq-site-search-title">Find equipment and information</h2><button type="button" data-search-close aria-label="Close website search">×</button></div><?php get_search_form(); ?></dialog>
