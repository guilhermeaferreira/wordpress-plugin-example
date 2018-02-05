<?php
/* Template Name: PageGuilhermeGalleryTest */

// user needs to be logged to use that page
if (get_current_user_id() === 0) {
    wp_redirect( home_url() );
}

// Plugin directory
require_once ABSPATH . 'wp-content/plugins/guilherme-test-plugin/guilherme-test-plugin.php';

$terms = get_terms( Guilherme_Test_Plugin_Class::POST_PREFIX . 'gallery_type', [
    'hide_empty' => false,
] );

// check if the form was submitted
if (isset($_POST['submit'])) {

    if (empty($_POST['title'])) {
        $errors = TRUE;
        $errMgs[] = 'Title is required!';
    }

    if (empty($_POST['terms'])) {
        $errors = TRUE;
        $errMgs[] = 'Select at least one category!';
    }

    if (empty($_FILES['image']['name'])) {
        $errors = TRUE;
        $errMgs[] = 'One image must be selected!';
    }

    if (empty($_POST['visibility'])) {
        $errors = TRUE;
        $errMgs[] = 'Visibility is required!';
    }

    // validate the image size
    $image = $_FILES['image'];
    Guilherme_Test_Plugin_Class::check_image_size($image);

    // if we DO NOT have errors, save!
    if (!$errors) {
        $post = array(
            'post_title' => wp_strip_all_tags($_POST['title']),
            'post_status' => 'publish',
            'post_type' => Guilherme_Test_Plugin_Class::POST_PREFIX . 'gallery'
        );
        $post_id = wp_insert_post($post); // save the post

        if (is_int($post_id)) { // if the post was saved, add the extra items
            Guilherme_Test_Plugin_Class::save_image($image, $post_id);
            Guilherme_Test_Plugin_Class::save_visibility($_POST['visibility'], $post_id);
            wp_set_object_terms($post_id, $_POST['terms'], Guilherme_Test_Plugin_Class::POST_PREFIX . 'gallery_type');
        }
    }
}

get_header();

?>

<div class="wrap">
	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

            <div class="col-md-5">
                    <?php if ($errors): ?>
                    <div>Errors: <br/><br/></div>
                    <?php foreach ($errMgs as $err): ?>
                    <div class="alert alert-danger"><?php echo $err; ?></div>
                    <?php endforeach; ?>

                    <div class="clearfix"></div>
                <?php endif; ?>
                <form role="form" method="POST" enctype="multipart/form-data">
                    <?php wp_nonce_field( 'guilherme-test-gallery' ); ?>

                    <h3 style="text-align: center;">Gallery Form</h3>
                    <div class="form-group">
                        <label>Image Name:</label>
                        <input type="text" class="form-control" id="title" name="title" placeholder="type the image name..." required>
                    </div>
                    <div class="form-group">
                        <label>Gallery Type:</label>
                        <?php foreach ($terms as $term): ?>
                            <input type="checkbox" name="terms[]" value="<?php echo $term->name ?>"/> <?php echo $term->name ?>
                        <?php endforeach; ?>
                    </div>
                    <div class="form-group">
                        <label>Visibility of the image:</label>
                        <select name="visibility" id="visibility">
                            <option> -- select -- </option>
                            <option value="public">Public</option>
                            <option value="private">Private</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Image:</label>
                        <input type="file" id="image" name="image" value="" size="25" />
                    </div>
                    <button type="submit" id="submit" name="submit" class="btn btn-primary pull-right">Send</button>
                </form>
            </div>

		</main><!-- #main -->
	</div><!-- #primary -->
</div><!-- .wrap -->

<?php get_footer();
