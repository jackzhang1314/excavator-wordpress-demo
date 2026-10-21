<?php
/** Approved charcoal footer; native menus and canonical shared company values. */
$products = Starter\Theme\footer_menu('footer_products');
$company = Starter\Theme\footer_menu('footer_explore');
$support = Starter\Theme\footer_menu('footer_support');
$legal = Starter\Theme\footer_menu('footer_legal');
$social = Starter\Theme\footer_social_links();
$about = trim(Starter\Theme\get_setting('footer_about'));
$email = sanitize_email(Starter\Theme\get_setting('contact_email'));
$phone = trim(Starter\Theme\get_setting('contact_phone'));
$phone_link = preg_replace('/[^0-9+]/', '', $phone);
$address = trim(Starter\Theme\get_setting('contact_address'));
$hours = trim(Starter\Theme\get_setting('contact_hours'));
?>
<footer class="site-footer eq-footer"><div class="shell">
<div class="eq-footer-grid">
    <div class="footer-identity"><div class="footer-brand"><?php if (has_custom_logo()) : the_custom_logo(); else : ?><a href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a><?php endif; ?></div>
    <?php if ($about !== '') : ?><p class="footer-about"><?php echo esc_html($about); ?></p><?php endif; ?>
    <?php if ($social) : ?><ul class="footer-social" aria-label="Social channels"><?php foreach ($social as $channel) : ?><li><a href="<?php echo esc_url($channel['url']); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr($channel['label'] . ' (opens in a new tab)'); ?>"><?php if ($channel['icon']) echo wp_get_attachment_image($channel['icon'], 'thumbnail', false, ['alt' => '', 'loading' => 'lazy']); else echo Starter\Theme\footer_social_icon($channel['platform']); ?></a></li><?php endforeach; ?></ul><?php endif; ?>
    </div>
    <?php foreach ([['Equipment', $products], ['Company', $company], ['Support', $support]] as [$heading, $menu]) : if ($menu === '') continue; ?><nav class="footer-navigation" aria-label="<?php echo esc_attr('Footer ' . $heading); ?>"><h2><?php echo esc_html($heading); ?></h2><?php echo $menu; ?></nav><?php endforeach; ?>
    <?php if (shortcode_exists('gtranslate')) : ?><div class="eq-footer-language" hidden><h2>Language</h2><button class="eq-language-jump" type="button" aria-label="Change language"><span aria-hidden="true">◎</span> <span class="eq-current-language">EN</span> <span aria-hidden="true">⌄</span></button></div><?php endif; ?>
</div>
<?php if ($email !== '' || $phone !== '' || $address !== '' || $hours !== '') : ?><address class="eq-footer-contacts">
<?php if ($email !== '') : ?><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a><?php endif; ?>
<?php if ($phone !== '') : ?><a href="tel:<?php echo esc_attr($phone_link); ?>"><?php echo esc_html($phone); ?></a><?php endif; ?>
<?php if ($address !== '') : ?><span><?php echo nl2br(esc_html($address)); ?></span><?php endif; ?>
<?php if ($hours !== '') : ?><span><?php echo esc_html($hours); ?></span><?php endif; ?></address><?php endif; ?>
<div class="footer-bottom"><small>© <?php echo esc_html(wp_date('Y')); ?> <?php bloginfo('name'); ?>. All rights reserved.</small><?php if ($legal !== '') : ?><nav aria-label="Legal links"><?php echo $legal; ?></nav><?php endif; ?></div>
</div></footer>
