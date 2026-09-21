<?php
/**
 * WP Lab Theme — index template.
 *
 * @package lab-theme
 */

get_header();
?>
<main id="content">
	<h1><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h1>
	<p class="wp-lab-smoke-marker">WP Lab smoke marker</p>
	<?php
	if ( have_posts() ) :
		while ( have_posts() ) :
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
				<div class="entry"><?php the_excerpt(); ?></div>
			</article>
			<?php
		endwhile;
	else :
		?>
		<p><?php esc_html_e( 'No content yet.', 'lab-theme' ); ?></p>
		<?php
	endif;
	?>
</main>
<?php
get_footer();
