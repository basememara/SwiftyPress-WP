<?php
    $post_id = 0;
    if (is_numeric($_GET['postid'])) {
        $post_id = (int) $_GET['postid'];
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
    <head>
        <style>
            /**
             * 12.3 Comments
             */
            .comments-area {
                background-color: #fff;
                border-top: 1px solid #eaeaea;
                border-top: 1px solid rgba(51, 51, 51, 0.1);
                padding: 7.6923%;
            }

            .comments-area > :last-child {
                margin-bottom: 0;
            }

            .comment-list + .comment-respond {
                border-top: 1px solid #eaeaea;
                border-top: 1px solid rgba(51, 51, 51, 0.1);
            }

            .comment-list + .comment-respond,
            .comment-navigation + .comment-respond {
                padding-top: 1.6em;
            }

            .comments-title,
            .comment-reply-title {
                font-family: "Noto Serif", serif;
                font-size: 18px;
                font-size: 1.8rem;
                line-height: 1.3333;
            }

            .comments-title {
                margin-bottom: 1.3333em;
            }

            .comment-list {
                list-style: none;
                margin: 0;
            }

            .comment-list article,
            .comment-list .pingback,
            .comment-list .trackback {
                border-top: 1px solid #eaeaea;
                border-top: 1px solid rgba(51, 51, 51, 0.1);
                padding: 1.6em 0;
            }

            .comment-list .children {
                list-style: none;
                margin: 0;
            }

            .comment-list .children > li {
                padding-left: 0.8em;
            }

            .comment-author {
                color: #707070;
                color: rgba(51, 51, 51, 0.7);
                margin-bottom: 0.4em;
            }

            .comment-author a:hover {
                border-bottom: 1px solid #707070;
                border-bottom: 1px solid rgba(51, 51, 51, 0.7);
            }

            .comment-author .avatar {
                float: left;
                height: 24px;
                margin-right: 0.8em;
                width: 24px;
            }

            .bypostauthor > article .fn:after {
                content: "\f304";
                position: relative;
                top: 5px;
                left: 3px;
            }

            .comment-metadata,
            .pingback .edit-link {
                color: #707070;
                color: rgba(51, 51, 51, 0.7);
                font-family: "Noto Sans", sans-serif;
                font-size: 12px;
                font-size: 1.2rem;
                line-height: 1.5;
            }

            .comment-metadata a,
            .pingback .edit-link a {
                color: #707070;
                color: rgba(51, 51, 51, 0.7);
            }

            .comment-metadata a:hover,
            .pingback .edit-link a:hover {
                border-bottom: 1px solid #333;
            }

            .comment-metadata a:hover,
            .comment-metadata a:focus,
            .pingback .edit-link a:hover,
            .pingback .edit-link a:focus {
                color: #333;
            }

            .comment-metadata {
                margin-bottom: 1.6em;
            }

            .comment-metadata .edit-link {
                margin-left: 1em;
            }

            .pingback .edit-link {
                margin-left: 1em;
            }

            .pingback .edit-link:before {
                top: 5px;
            }

            .comment-content ul,
            .comment-content ol {
                margin: 0 0 1.6em 1.3333em;
            }

            .comment-content li > ul,
            .comment-content li > ol {
                margin-bottom: 0;
            }

            .comment-content > :last-child {
                margin-bottom: 0;
            }

            .comment-list .reply {
                font-size: 12px;
                font-size: 1.2rem;
            }

            .comment-list .reply a {
                border: 1px solid #eaeaea;
                border: 1px solid rgba(51, 51, 51, 0.1);
                color: #707070;
                color: rgba(51, 51, 51, 0.7);
                display: inline-block;
                font-family: "Noto Sans", sans-serif;
                font-weight: 700;
                line-height: 1;
                margin-top: 2em;
                padding: 0.4167em 0.8333em;
                text-transform: uppercase;
            }

            .comment-list .reply a:hover,
            .comment-list .reply a:focus {
                border-color: #333;
                color: #333;
                outline: 0;
            }

            .comment-form {
                padding-top: 1.6em;
            }

            .comment-form label {
                color: #707070;
                color: rgba(51, 51, 51, 0.7);
                font-family: "Noto Sans", sans-serif;
                font-size: 12px;
                font-size: 1.2rem;
                font-weight: 700;
                display: block;
                letter-spacing: 0.04em;
                line-height: 1.5;
                text-transform: uppercase;
            }

            .comment-form input[type="text"],
            .comment-form input[type="email"],
            .comment-form input[type="url"],
            .comment-form input[type="submit"] {
                width: 100%;
            }

            .comment-notes,
            .comment-awaiting-moderation,
            .logged-in-as,
            .form-allowed-tags {
                color: #707070;
                color: rgba(51, 51, 51, 0.7);
                font-family: "Noto Sans", sans-serif;
                font-size: 12px;
                font-size: 1.2rem;
                line-height: 1.5;
                margin-bottom: 2em;
            }

            .logged-in-as a:hover {
                border-bottom: 1px solid #333;
            }

            .no-comments {
                border-top: 1px solid #eaeaea;
                border-top: 1px solid rgba(51, 51, 51, 0.1);
                color: #707070;
                color: rgba(51, 51, 51, 0.7);
                font-family: "Noto Sans", sans-serif;
                font-weight: 700;
                padding-top: 1.6em;
            }

            .comment-navigation + .no-comments {
                border-top: 0;
            }

            .form-allowed-tags code {
                font-family: Inconsolata, monospace;
            }

            .form-submit {
                margin-bottom: 0;
            }

            .required {
                color: #c0392b;
            }

            .comment-reply-title small {
                font-size: 100%;
            }

            .comment-reply-title small a {
                border: 0;
                float: right;
                height: 32px;
                overflow: visible;
                width: 50px;
            }
        </style>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
        <style>
            body {
                padding: 15px;
            }
            
            #reply-title {
                margin: 0;
                cursor: pointer;
            }
            
            #reply-title:after { 
                content: '+';
            }
            
            textarea#comment {
                width: 100%;
            }
            
            .comment-form {
                display: none;
            }
        </style>
    </head>
    <body>
        <?php
            comment_form(array(), $post_id)
        ?>

        <hr />
        
        <ol class="commentlist">
            <?php
                //Gather comments for a specific page/post 
                $comments = get_comments(array(
                    'post_id' => $post_id,
                    'status' => 'approve' //Change this to the type of comments to be displayed
                ));

                //Display the list of comments
                wp_list_comments(array(
                    'type' => 'comment',
                    'reverse_top_level' => false //Show the latest comments at the top of the list
                ), $comments);
            ?>
        </ol>
        
        <script src="/wp-includes/js/jquery/jquery.js"></script>
        <script>
            document.getElementById('commentform').removeAttribute('novalidate');
            document.getElementById('commentform').style.display = 'none';
            document.getElementById('reply-title').addEventListener('click', toggleForm);
            
            function toggleForm() {
                var el = document.getElementById('commentform');
                el.style.display = el.style.display != 'none' ? 'none' : 'block';
            }

			if (jQuery('.commentlist li').length == 0) {
				toggleForm()
			}
        </script>
    </body>
</html>