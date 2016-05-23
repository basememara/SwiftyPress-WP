<?php
    $post_id = 0;
    if (is_numeric($_GET['postid'])) {
        $post_id = (int) $_GET['postid'];
        $page_theme = $_GET['theme'];
    } else {
        die ('Please specify a valid post ID!');
    }
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            body {
                background-color: <?php echo $page_theme == "light" ? "#f5f5f5" : "#000" ?>;
            }
            
            #wrapper {
                width: 98%;
                max-width: 1100px;
                margin: 15px auto;
            }

            #columns {
                -webkit-column-count: 2;
                -webkit-column-gap: 10px;
                -webkit-column-fill: auto;
                -moz-column-count: 2;
                -moz-column-gap: 10px;
                -moz-column-fill: auto;
                column-count: 2;
                column-gap: 15px;
                column-fill: auto;
            }

            .pin {
                display: inline-block;
                background: #FEFEFE;
                border: 2px solid #FAFAFA;
                box-shadow: 0 1px 2px rgba(34, 25, 25, 0.4);
                margin: 0 2px 15px;
                -webkit-column-break-inside: avoid;
                -moz-column-break-inside: avoid;
                column-break-inside: avoid;
                padding: 15px;
                padding-bottom: 5px;
                background: -webkit-linear-gradient(45deg, #FFF, #F9F9F9);
                border-radius: 5px;

                -webkit-transition: all .2s ease;
                -moz-transition: all .2s ease;
                -o-transition: all .2s ease;
                transition: all .2s ease;
            }

            .pin a.thumbnail {
                width: 100%;
                padding-bottom: 15px;
                margin-bottom: 5px;
                text-decoration: none;
            }

            .pin .thumbnail img {
                width: 100%;
            }

            .pin a.content {
                font: bold 14px/18px Arial, sans-serif;
                color: #333;
                margin: 0;
                text-decoration: none;
            }

            @media (min-width: 960px) {
                #columns {
                    -webkit-column-count: 4;
                    -moz-column-count: 4;
                    column-count: 4;
                }
            }

            @media (min-width: 1100px) {
                #columns {
                    -webkit-column-count: 5;
                    -moz-column-count: 5;
                    column-count: 5;
                }
            }
        </style>
    </head>
    <body>
        <div id="wrapper">
            <div id="columns">
            <?php
                if (class_exists('Jetpack_RelatedPosts')
                    && method_exists('Jetpack_RelatedPosts', 'init_raw')) {
                    $related = Jetpack_RelatedPosts::init_raw()
                        ->get_for_post_id(
                            $post_id,
                            array('size' => 6)
                        );

                    if ($related) {
                        foreach ($related as $result) {
                            $related_post = get_post($result['id']);
                            $url = get_permalink($related_post->ID);
                            $title = $related_post->post_title;
                            $image = wp_get_attachment_url(get_post_thumbnail_id($related_post->ID));
                            $image = Jetpack_PostImages::get_image(
                                $related_post->ID,
                                350
                            );
                            ?>
                              <div class="pin">
                                  <a href="<?php echo $url ?>" class="thumbnail">
                                    <img src="<?php echo $image['src'] ?>" alt="<?php echo $title ?>">
                                  </a>
                                  <a href="<?php echo $url ?>" class="content">
                                      <?php echo $title ?>
                                  </a>
                              </div>
                            <?php
                        }
                    }
                }
            ?>
            </div>
        </div>
    </body>
</html>