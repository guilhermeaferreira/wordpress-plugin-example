<?php
/**
 * Default template for Guilherme Test Gallery Plugin
 */

$terms      = wp_get_post_terms($post->ID, Guilherme_Test_Plugin_Class::POST_PREFIX . 'gallery_type', array("fields" => "all"));
$image      = get_post_meta( $post->ID, Guilherme_Test_Plugin_Class::POST_PREFIX . 'image', true);
$visibility = get_post_meta( $post->ID, Guilherme_Test_Plugin_Class::POST_PREFIX . 'visibility', true);
$post       = get_post($post->ID);


switch ($visibility) {
    case 'private':
        if (get_current_user_id() != $post->post_author) {
            wp_redirect( home_url() );
        }
}

get_header(); ?>

    <style>
        #category-list {
            display: inline;
            list-style: none;
        }

        #category-list li {
            display: inline;
        }

        #category-list li + li:before {
            content: ", ";
        }
    </style>

    <div id="primary" class="content-area" style="width: 100%; text-align: center;">
        <main id="main" class="content site-main" role="main">
            <article id="post-<?php the_ID(); ?>">

                <header class="entry-header">
                    <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
                </header><!-- .entry-header -->

                <div class="entry-content">
                    <div>
                        <img src="<?php echo $image['url'] ?>"/>
                    </div>
                    <div>
                        <ul id="category-list">
                        <?php foreach ($terms as $term): ?>
                            <li> <?php echo $term->name; ?> </li>
                        <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </article>
        </main>
    </div>

<?php get_footer(); ?>