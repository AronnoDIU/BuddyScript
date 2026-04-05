<?php

/**
 * This file has been auto-generated
 * by the Symfony Routing Component.
 */

return [
    false, // $matchHost
    [ // $staticRoutes
        '/api/login_check' => [[['_route' => 'api_login_check'], null, ['POST' => 0], null, false, false, null]],
        '/api/auth/register' => [[['_route' => 'api_register', '_controller' => 'App\\Controller\\AuthController::register'], null, ['POST' => 0], null, false, false, null]],
        '/api/me' => [[['_route' => 'api_me', '_controller' => 'App\\Controller\\AuthController::me'], null, ['GET' => 0], null, false, false, null]],
        '/api/feed' => [[['_route' => 'api_feed', '_controller' => 'App\\Controller\\FeedController::feed'], null, ['GET' => 0], null, false, false, null]],
        '/api/posts' => [[['_route' => 'api_post_create', '_controller' => 'App\\Controller\\FeedController::createPost'], null, ['POST' => 0], null, false, false, null]],
    ],
    [ // $regexpList
        0 => '{^(?'
                .'|/_error/(\\d+)(?:\\.([^/]++))?(*:35)'
                .'|/api/(?'
                    .'|posts/([^/]++)/(?'
                        .'|comments(*:76)'
                        .'|likes(?'
                            .'|/toggle(*:98)'
                            .'|(*:105)'
                        .')'
                    .')'
                    .'|comments/([^/]++)/(?'
                        .'|replies(*:143)'
                        .'|likes(?'
                            .'|/toggle(*:166)'
                            .'|(*:174)'
                        .')'
                    .')'
                .')'
            .')/?$}sDu',
    ],
    [ // $dynamicRoutes
        35 => [[['_route' => '_preview_error', '_controller' => 'error_controller::preview', '_format' => 'html'], ['code', '_format'], null, null, false, true, null]],
        76 => [[['_route' => 'api_post_comment_create', '_controller' => 'App\\Controller\\FeedController::addComment'], ['id'], ['POST' => 0], null, false, false, null]],
        98 => [[['_route' => 'api_post_like_toggle', '_controller' => 'App\\Controller\\FeedController::togglePostLike'], ['id'], ['POST' => 0], null, false, false, null]],
        105 => [[['_route' => 'api_post_likes', '_controller' => 'App\\Controller\\FeedController::postLikes'], ['id'], ['GET' => 0], null, false, false, null]],
        143 => [[['_route' => 'api_comment_reply_create', '_controller' => 'App\\Controller\\FeedController::addReply'], ['id'], ['POST' => 0], null, false, false, null]],
        166 => [[['_route' => 'api_comment_like_toggle', '_controller' => 'App\\Controller\\FeedController::toggleCommentLike'], ['id'], ['POST' => 0], null, false, false, null]],
        174 => [
            [['_route' => 'api_comment_likes', '_controller' => 'App\\Controller\\FeedController::commentLikes'], ['id'], ['GET' => 0], null, false, false, null],
            [null, null, null, null, false, false, 0],
        ],
    ],
    null, // $checkCondition
];
