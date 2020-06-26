<?php

	get_header();
?>
<section id="content" role="main">
<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
<header class="header">
<h1 class="entry-title"><?php the_title(); ?></h1>
</header>
<section class="entry-content">
<?php the_content(); ?>
</section>
</article>
<?php endwhile; endif; ?>
</section>
<?php

	get_sidebar();

	get_footer();
