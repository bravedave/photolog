<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

    // sys::dump( $this->data->files, null, false);
?>

<div id="<?= $_uid = strings::rand() ?>" class="carousel slide" data-ride="carousel">
    <ol class="carousel-indicators">
        <?php
        $active = 'active';
        $i = 0;
        foreach ($this->data->files as $file) {
            printf( '<li data-target="#%s" data-slide-to="%d" class="%s"></li>',
                $_uid,
                $i++,
                $active

            );
            $active = '';

        } ?>

    </ol>

    <div class="carousel-inner">
        <?php
        $active = 'active';
        reset( $this->data->files);
        foreach ($this->data->files as $file) {

            printf( '<div class="carousel-item %s">', $active);
            if ( 'image' == $file->type) {
                printf('<img src="%s" class="d-block w-100" alt="%s" />',
                    $file->url,
                    htmlspecialchars($file->description)

                );

                printf('<div class="carousel-caption d-none d-md-block"><h5>%s</h5></div>',
                    $file->description
                );

            }
            elseif ( 'video' == $file->type || 'pdf' == $file->type) {
                print '<div class="embed-responsive embed-responsive-16by9">';
                printf( '<iframe class="embed-responsive-item" src="%s" allowfullscreen></iframe>', $file->url);

                print '</div>';

            }

            print '</div>';
            $active = '';

        } ?>

    </div>

    <a class="carousel-control-prev" href="#<?= $_uid ?>" role="button" data-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="sr-only">Previous</span>

    </a>

    <a class="carousel-control-next" href="#<?= $_uid ?>" role="button" data-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="sr-only">Next</span>

    </a>

</div>

