<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
if (!class_exists('AC_Render')) :
    /**
     * AnyCommentRender helps to render comments on client side.
     */
    class AC_Render
    {
        /**
         * Default comment limit.
         */
        const LIMIT = 20;

        /**
         * Sort old.
         */
        const SORT_OLD = 'old';

        /**
         * Sort new.
         */
        const SORT_NEW = 'new';

        /**
         * AC_Render constructor.
         */
        public function __construct()
        {
            if (AC_GenericSettings::isEnabled()) {
                add_filter('comments_template', [$this, 'render_iframe']);

                add_action('wp_ajax_iframe_comments', [$this, 'iframe_comments']);
                add_action('wp_ajax_nopriv_iframe_comments', [$this, 'iframe_comments']);

                add_action('wp_ajax_render_comments', [$this, 'render_comments']);
                add_action('wp_ajax_nopriv_render_comments', [$this, 'render_comments']);

                add_action('wp_ajax_add_comment', [$this, 'add_comment']);
                add_action('wp_ajax_nopriv_add_comment', [$this, 'add_comment']);
            }
        }

        /**
         * Make custom template for comments.
         * @return string
         */
        public function render_iframe()
        {
            wp_enqueue_script(
                'anycomment-iframeResizer',
                'https://cdnjs.cloudflare.com/ajax/libs/iframe-resizer/3.6.1/iframeResizer.min.js',
                [],
                1.0
            );

            return ANY_COMMENT_ABSPATH . 'templates/iframe.php';
        }

        public function iframe_comments()
        {
            if (!wp_verify_nonce($_GET['nonce'], 'iframe_comments')) {
                wp_die();
            }

            include ANY_COMMENT_ABSPATH . 'templates/comments.php';
            die();
        }

        /**
         * Get comments.
         * @param null|int $postId Post ID to check comments for. Avoid then get_the_ID() will be used to get id.
         * @param int $limit Limit number of comments to load.
         * @param string $sort Sorting type. New or old. Default is new.
         * @return array|null NULL when there are no comments for post.
         */
        public function get_comments($postId = null, $limit = null, $sort = null)
        {
            if ($limit === null || empty($limit) || (int)$limit < self::LIMIT) {
                $limit = self::LIMIT;
            }

            if ($sort === null || ($sort !== self::SORT_NEW && $sort !== self::SORT_OLD)) {
                $sort = self::SORT_NEW;
            }

            $options = [
                'post_id' => $postId === null ? get_the_ID() : $postId,
                'parent' => 0,
                'comment_status' => 1,
                'number' => $limit,
                'orderby' => 'comment_ID',
                'order' => $sort === self::SORT_NEW ? 'DESC' : 'ASC'
            ];

            $comments = get_comments($options);

            return count($comments) > 0 ? $comments : null;
        }

        /**
         * Get parent child comments.
         * @param int $commentId Parent comment id.
         * @param null|int $postId Post ID to check comments for. Avoid then get_the_ID() will be used to get id.
         * @return array|null NULL when there are no comments for post.
         */
        public function get_child_comments($commentId, $postId = null)
        {
            if ($commentId === null) {
                return null;
            }

            $comments = get_comments(['parent' => $commentId, 'post_id' => $postId === null ? get_the_ID() : $postId]);

            return count($comments) > 0 ? $comments : null;
        }

        /**
         * Use to get freshest list of comment list.
         */
        public function render_comments()
        {
            check_ajax_referer('load-comments-nonce');

            $postId = sanitize_text_field($_POST['postId']);
            $limit = sanitize_text_field($_POST['limit']);
            $sort = sanitize_text_field($_POST['sort']);

            if (empty($postId)) {
                echo AnyComment()->json_error(__("No post ID specified", 'anycomment'));
                wp_die();
            }

            if (!get_post_status($postId)) {
                echo AnyComment()->json_error(sprintf(__("Unable to find post with ID #%s", 'anycomment'), $postId));
                wp_die();
            }

            do_action('anycomment_comments', $postId, $limit, $sort);
            wp_die();
        }

        /**
         * Add new comment.
         */
        public function add_comment()
        {
            check_ajax_referer('add-comment-nonce', 'nonce');

            $user = wp_get_current_user();

            if (!$user instanceof WP_User) {
                echo AnyComment()->json_error(__("Login to add a comment", "anycomment"));
                wp_die();
            }

            $comment_parent_id = sanitize_text_field($_POST['reply_to']);
            $comment = sanitize_text_field($_POST['comment']);
            $post_id = sanitize_text_field($_POST['post_id']);

            if (empty($comment) || empty($post_id)) {
                echo AnyComment()->json_error(__("Wrong params passed", "anycomment"));
                wp_die();
            }

            if (get_post($post_id) === null) {
                echo AnyComment()->json_error(__("No such post", "anycomment"));
                wp_die();
            }

            $args['comment_content'] = $comment;
            $args['comment_post_ID'] = $post_id;

            if (($comment = get_comment($comment_parent_id)) instanceof WP_Comment) {
                // Check that comment belongs to the current post
                if ($comment->comment_post_ID != $post_id) {
                    echo AnyComment()->json_error(__('Reply comment does not belong to the post', "anycomment"));
                    wp_die();
                }

                if ($comment->comment_parent > 0) {
                    echo AnyComment()->json_error(__('Reply comment can be max 2nd level', "anycomment"));
                    wp_die();
                }

                $args['comment_parent'] = $comment_parent_id;
            }

            $args['user_id'] = $user->ID;


            if (!empty($displayName = $user->display_name)) {
                $args['comment_author'] = trim($displayName);
            }

            // Email
            if (!empty($email = $user->user_email)) {
                $args['comment_author_email'] = $email;
            }

            $args['comment_approved'] = 1;


            if (!($new_comment_id = wp_insert_comment($args))) {
                echo AnyComment()->json_error(__("Failed to add comment. Please, try again.", "anycomment"));
                wp_die();
            }

            echo AnyComment()->json_success([
                'commentId' => $new_comment_id,
                'comment_count_text' => $this->get_comment_count($post_id)
            ]);
            wp_die();
        }

        /**
         * Get comment count.
         * @param int $post_id Post ID.
         * @return string
         */
        public function get_comment_count($post_id)
        {
            return sprintf(__('%s Comments', 'anycomment'), get_comments_number($post_id));
        }
    }
endif;