<?php
/**
 * $title = Title of module
 * $description = Description of module
 * $categories = Categories list
 * $category = id of selected category
 * $keywords = search keywords
 * $query = object of WP_Query
 */
?>
<script type="text/javascript">
	jQuery(function ($) {
		$('#cfct-loop-searchable-search-form-select').change(function () {
			location.href = location.pathname+'?category='+$(this).val();
		});
	});
</script>
<?php if (!empty($title)): ?>
	<h1><?php echo $title; ?></h1>
<?php endif; ?>

<?php if (!empty($description)): ?>
	<h2><?php echo $description; ?></h2>
<?php endif; ?>
<div class="cfct-loop-searchable-search-form">
	<form action="" method="get">
        <?php
            echo wp_dropdown_categories(
				array(
					 'id' => 'cfct-loop-searchable-search-form-selec',
					 'selected' => $category,
				     'hide_empty' => 0,
					 'echo' => false,
				     'hide_if_empty' => false,
				     'taxonomy' => 'category',
				     'name' => 'category',
				     'orderby' => 'name',
					 'class' => 'category',
				     'hierarchical' => true,
				     'show_option_none' => __('Select Category')
				)
			);
        ?>		<input class="submit" type="submit" value="Search">
		<input class="keywords" type="text" name="keywords" value="<?php echo $keywords; ?>"  />
	</form>
</div>

<?php if ($wp_query->have_posts()): ?>
	<?php while ($wp_query->have_posts()): ?>
		<?php $wp_query->the_post(); ?>
		<div <?php post_class('entry entry-full clearfix'); ?>>
			<div class="entry-header">
				<h2><a href="<?php the_permalink() ?>"><?php the_title(); ?></a></h2>
			</div>
			<div class="entry-content">
					<?php the_excerpt(); ?>
			</div>
            <?php
                if (count(get_post_custom_values('_attachment'))) {
                    $file = WP_CONTENT_DIR.'/attachments/'.array_shift(get_post_custom_values('_attachment'));
                } elseif (count(get_post_custom_values('file'))) {
                    $file = array_shift(get_post_custom_values('file'));
                }
            ?>
            <?php if ($file): ?>
            <div class="attachments">
                <div class="file-view">
                    <a href="http://docs.google.com/viewer?url=<?php echo $file; ?>" target="_blank">
                        <img src="<?php echo get_stylesheet_directory_uri(); ?>/images/ico-google.jpg" /><span>&nbsp;View document in browser</span>
                    </a>
                </div>
                <div class="file-download">
                    <a href="<?php echo $file; ?>" target="_blank">
                        <img src="<?php echo get_stylesheet_directory_uri(); ?>/images/ico-pdf.png" /><span>&nbsp;Download PDF</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>
		</div>
	<?php endwhile; ?>
<?php else: ?>
	<h2>Sorry, but we not found any post</h2>
<?php endif; ?>

