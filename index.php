<?php get_header(); ?>

<main style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
    <h1><?php the_title(); ?></h1>
    <div>
        <?php
        if ( have_posts() ) :
            while ( have_posts() ) : the_post();
                the_content();
            endwhile;
        endif;
        ?>
    </div>
</main>

<?php get_footer(); ?>