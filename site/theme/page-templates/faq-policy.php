<?php
/**
 * Template Name: FAQs & Policies
 * Template Post Type: page
 * Template Content Source: ACF
 */
get_header(); ?><main id="main" class="eq-page eq-policy"><?php while (have_posts()) : the_post();
$raw = get_field('faq_sections'); $topics = [];
foreach (is_array($raw) ? $raw : [] as $topic) {
 if (!is_array($topic) || trim((string) ($topic['title'] ?? '')) === '') continue;
 $questions = array_values(array_filter(is_array($topic['questions'] ?? null) ? $topic['questions'] : [], static fn ($row): bool => is_array($row) && trim((string) ($row['question'] ?? '')) !== '' && trim((string) ($row['answer'] ?? '')) !== ''));
 if ($questions) $topics[] = ['title' => $topic['title'], 'intro' => trim((string) ($topic['intro'] ?? '')), 'rows' => array_map(static fn (array $row): array => ['label' => $row['question'], 'value' => $row['answer']], $questions)];
}
$body = (string) get_field('policy_body', false, true, true);
$has_body = Starter\Theme\has_rich_content($body);
$indexed = $has_body ? Starter\Theme\rich_heading_index($body, 'policy-guide') : ['content' => '', 'items' => []];
Starter\Theme\eq_business_hero('policy', 'dark', false); ?>
<div class="eq-shell eq-policy-breadcrumbs"><?php Starter\Theme\breadcrumbs(); ?></div>
<?php if ($topics) : ?><section class="eq-section"><div class="eq-shell eq-faq-layout" data-faq-topics>
<nav class="eq-topic-nav" aria-label="Question topics"><?php foreach ($topics as $index => $topic) : ?><a href="#faq-topic-<?php echo $index; ?>"><?php echo esc_html($topic['title']); ?><span aria-hidden="true">›</span></a><?php endforeach; ?></nav>
<div class="eq-faq-topics"><?php foreach ($topics as $index => $topic) : ?><section id="faq-topic-<?php echo $index; ?>" data-faq-topic><h2><?php echo esc_html($topic['title']); ?></h2><?php if ($topic['intro']) : ?><p class="eq-faq-intro"><?php echo esc_html($topic['intro']); ?></p><?php endif; ?><?php Starter\Theme\component_faq(['rows' => $topic['rows'], 'open_first' => true]); ?></section><?php endforeach; ?></div>
<?php if (Starter\Theme\eq_text('policy_support_title') || Starter\Theme\eq_text('policy_support_intro') || Starter\Theme\eq_image('policy_support_image')) : ?><aside class="eq-policy-support"><?php if ($title = Starter\Theme\eq_text('policy_support_title')) : ?><h2><?php echo esc_html($title); ?></h2><?php endif; ?><?php if ($intro = Starter\Theme\eq_text('policy_support_intro')) : ?><p><?php echo esc_html($intro); ?></p><?php endif; ?><?php Starter\Theme\eq_photo(Starter\Theme\eq_image('policy_support_image')); ?><a class="button" href="<?php echo esc_url(Starter\Theme\contact_url()); ?>">Contact us <span aria-hidden="true">→</span></a></aside><?php endif; ?>
</div></section><?php endif; ?>
<?php if ($has_body) : ?><section class="eq-section eq-policy-reading"><div class="eq-shell">
<?php Starter\Theme\eq_section_head(Starter\Theme\eq_text('policy_reading_title'), Starter\Theme\eq_text('policy_reading_intro')); ?>
<div class="eq-policy-reading-grid">
<?php if ($indexed['items']) : ?><nav class="eq-topic-nav" aria-label="Policy contents"><?php foreach ($indexed['items'] as $item) : ?><a href="#<?php echo esc_attr($item['id']); ?>"><?php echo esc_html($item['text']); ?></a><?php endforeach; ?></nav><?php endif; ?>
<article class="prose eq-prose"><?php echo $indexed['content']; // Canonical ACF HTML with unique reading anchors. ?></article>
<?php Starter\Theme\eq_photo(Starter\Theme\eq_image('policy_reading_image')); ?>
</div></div></section><?php endif; ?>
<?php Starter\Theme\eq_business_close('policy'); endwhile; ?></main><?php get_footer(); ?>
