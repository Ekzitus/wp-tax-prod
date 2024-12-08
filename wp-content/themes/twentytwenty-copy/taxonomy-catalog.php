<?php
get_header();

$current_category = get_queried_object();

$child_categories = get_terms([
    'taxonomy' => 'catalog',
    'child_of' => $current_category->term_id,
    'hide_empty' => true,
]);

$category_ids = array_merge(
        [$current_category->term_id],
        wp_list_pluck($child_categories, 'term_id')
);

$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

$args = [
    'post_type' => 'product',
    'posts_per_page' => -1,
    'tax_query' => [
        [
            'taxonomy' => 'catalog',
            'field' => 'term_id',
            'terms' => $category_ids,
            'operator' => 'IN',
        ],
    ],
];

$query = new WP_Query($args);

$attributes = [];

if ($query->have_posts()) {
    while ($query->have_posts()) {
        $query->the_post();
        $product_attributes = get_the_terms(get_the_ID(), 'attributes');
        if ($product_attributes) {
            foreach ($product_attributes as $attribute) {
                $attributes[$attribute->term_id] = $attribute;
            }
        }
    }
}
wp_reset_postdata();
?>

    <h1>Товары в категории: <?php single_term_title(); ?></h1>

    <form method="get" class="filter-form">
        <h3>Фильтр по атрибутам:</h3>
        <?php
        if ($attributes && !is_wp_error($attributes)) :
            foreach ($attributes as $attribute) : ?>
                <label>
                    <input type="checkbox" name="attributes[]" value="<?php echo esc_attr($attribute->slug); ?>"
                        <?php if (isset($_GET['attributes']) && in_array($attribute->slug, $_GET['attributes'])) echo 'checked'; ?>>
                    <?php echo esc_html($attribute->name); ?>
                </label><br>
            <?php endforeach;
        endif;
        ?>

        <button type="submit">Применить фильтр</button>
    </form>

<?php
$attributes = get_query_var('attributes');

$attributes_array = is_array($attributes) ? $attributes : [];

$args = [
    'post_type'      => 'product',
    'posts_per_page' => -1,
    'tax_query'      => [
        'relation' => 'AND',
        [
            'taxonomy' => 'catalog',
            'field'    => 'slug',
            'terms'    => get_queried_object()->slug,
        ],
    ],
];


if (!empty($attributes_array)) {
    $args['tax_query'][] = [
        'taxonomy' => 'attributes',
        'field'    => 'slug',
        'terms'    => $attributes_array,
        'operator' => 'AND',
    ];
}

$query = new WP_Query($args);

if ($query->have_posts()) : ?>
    <div class="products-list">
        <?php while ($query->have_posts()) : $query->the_post(); ?>
            <div class="product-item">
                <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                <?php if (has_post_thumbnail()) : ?>
                    <a href="<?php the_permalink(); ?>">
                        <?php the_post_thumbnail('medium'); ?>
                    </a>
                <?php endif; ?>
                <p><?php the_excerpt(); ?></p>
            </div>
        <?php endwhile; ?>
    </div>
<?php else : ?>
    <p>Нет товаров, соответствующих фильтру.</p>
<?php endif; ?>

<?php
wp_reset_postdata();
get_footer();
?>