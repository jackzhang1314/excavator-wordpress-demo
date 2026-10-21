<?php
/** GTranslate owns the options, native control and translation behavior. */
if (!shortcode_exists('gtranslate')) {
    return;
}
?>
<div class="site-language notranslate" translate="no">
    <span class="site-language-label">Language</span>
    <div class="site-language-control">
        <span class="site-language-slot"></span>
        <svg class="site-language-icon" aria-hidden="true" focusable="false" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="9"/><ellipse cx="12" cy="12" rx="4" ry="9"/><path d="M3 12h18"/></svg>
        <svg class="site-language-arrow" aria-hidden="true" focusable="false" width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5"><path d="m2 4 4 4 4-4"/></svg>
    </div>
</div>
