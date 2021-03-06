<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WebUtilitiesService;
use App\Services\DataFormatService;
use App\Repositories\UserRepository;
use App\Repositories\LinkRepository;
use App\Repositories\TagRepository;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class BotController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        WebUtilitiesService $web_service,
        DataFormatService $format_service,
        UserRepository $user_repository,
        LinkRepository $link_repository,
        TagRepository $tag_repository
    )
    {
        $this->web_service = $web_service;
        $this->format_service = $format_service;
        $this->user_repository = $user_repository;
        $this->link_repository = $link_repository;
        $this->tag_repository = $tag_repository;
    }

    public function webhook(Request $request)
    {
        if ($request->input('token') == env('SLACK_APP_TOKEN')) {
            $text = $request->input('text');

            $data = [
                'response_type' => 'ephemeral',
                'text' => $text . ' @' . $request->input('user_name'),
            ];

            $headers = [
                'Content-type' =>  'application/json',
            ];

            //$this->web_service->performCurlAction(env('SLACK_WEBHOOK_URL'), 'post', $data, $headers);

            return response()->json($data, 200);
        }
    }

    public function addlink(Request $request)
    {
        try {
            $this->validate($request, [
                'text' => ['regex:@^<(https?|ftp):\/\/[^\s/$.?#].[^\s]*>((\ .*)+([a-zA-Z0-9]+))*(\ )*@'],
            ]);
            if (!$request->get('text')) {
                return response()->json([
                    'response_type' => 'ephemeral',
                    'text' => 'Oops! The link is required.'
                ], 200);
            }
        } catch (ValidationException $e) {
            return response()->json([
                'response_type' => 'ephemeral',
                'text' => 'Oops! ' . implode(". ", $e->response->original['text'])
            ], 200);
        }

        $data = $request->all();
        $auto_added_tags = false;
        $user = $this->user_repository->firstOrCreate(['slack_user_id' => $data['user_id'], 'slack_username' => $data['user_name']]);

        $text = $this->format_service->escapeContent($data['text']);
        $command_entities = $this->format_service->getLinkAndTags($text);

        if ($found_link = $this->link_repository->findByColumns(['url' => $command_entities['link']])->first()) {
            if ($user->favorites->contains('id', $found_link->id)) {
                $message = 'Oops! The link is already exists in your favorite list.';
            } else {
                $this->user_repository->addFavorite($user->id, $found_link->id);
                $message = 'Oops! The link is already added. But it \'s now in your favorites.';
            }

            return response()->json([
                'response_type' => 'ephemeral',
                'text' => $message
            ], 200);
        }

        if (empty($command_entities['tags'])) {
            $html = $this->web_service->scrapWeb($command_entities['link']);
            $key_words = $this->format_service->getHtmlH1Text($html);

            $tags = $this->format_service->getTags($key_words);
            $auto_added_tags = true;
        } else {
            $tags = $command_entities['tags'];
        }

/*
        $tags = array_map(function($element){
            return '#' . $element;
        }, $command_entities['tags']);
        */

        $headers = [
            'Content-type' => 'application/json',
        ];

        $link_data = [
            'url' => $command_entities['link'],
            'user_id' => $user->id,
            'tags' => $auto_added_tags ? $this->tag_repository->getTagsInGroup($tags) : $this->tag_repository->massFirstOrCreate($tags)
        ];

        if ($link = $this->link_repository->save((object) $link_data)) {
            //$this->web_service->performCurlAction(env('SLACK_WEBHOOK_URL'), 'post', json_encode($response_data), $headers);
            $this->user_repository->addFavorite($user->id, $link->id);
        }

        $linked_tags = $this->tag_repository->getTagsInIdsGroup($link_data['tags']);

        $response_data = [
            'response_type' => 'in_channel',
            'attachments' => [
                [
                    'pretext' => $data['user_name'] . ' added a new link.',
                    'color' => '#36a64f',
                    'title' => $command_entities['link'],
                    'title_link' => $command_entities['link'],
                    'callback_id' => 'link_added',
                    'fields' => [
                        [
                            'title' => 'tags',
                            'value' => implode(' ', $linked_tags),
                        ]
                    ],
                    'actions' => [
                        [
                            'name' =>  'favorite',
                            'text' =>  '★ Add to favorites',
                            'type' =>  'button',
                            'value' =>  $link->id,
                            'style' => 'primary'
                        ]
                    ]
                ]
            ]
        ];

        $this->web_service->performCurlAction(env('SLACK_WEBHOOK_URL'), 'post', json_encode($response_data), $headers);

        //return response()->json($response_data, 200);
    }

    public function myLinks(Request $request)
    {
        $data = $request->all();

        $user_links = $this->user_repository->findByColumns(['slack_user_id' => $data['user_id']])->first()->links->sortByDesc('updated_at');

        $attachments = [];

        foreach ($user_links as $link) {
                $attachments[] = [
                    //'pretext' => $data['user_name'] . '\'s links:',
                    'color' => '#1a5dc9',
                    'fields' => [
                        [
                            'title' => 'Added: ' . $link->date,
                            'value' => $link->url,
                            'short' => true
                        ],
                        [
                            'title' => 'Tags:',
                            'value' => implode(' ', $link->tags()->pluck('name')->toArray())
                        ]
                    ]
                    /*
                    'actions' => [
                        [
                            'name' =>  'next',
                            'text' =>  'Next page >>',
                            'type' =>  'button',
                            'value' =>  '1',
                            'style' => 'primary'
                        ]
                    ]
                    */
                ];
        }

        $headers = [
            'Content-type' => 'application/json',
        ];

        $response_data = [
            'response_type' => 'ephemeral',
            'text' => 'Your links:',
            'attachments' => $attachments
        ];

        //$this->web_service->performCurlAction(env('SLACK_WEBHOOK_URL'), 'post', $response_data, $headers);

        return response()->json($response_data, 200);
    }

    public function favoriteButtonAction(Request $request)
    {
        $data = json_decode($request->get('payload'));
        $user = $this->user_repository->findByColumns(['slack_user_id' =>  $data->user->id])->first();
        try {
            $this->user_repository->addFavorite($user->id, $data->actions[0]->value);

            return response()->json([
                'response_type' => 'ephemeral',
                'text' => 'Great! The link has been added to favorites.'
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'response_type' => 'ephemeral',
                'text' => 'Hey! The link is already added to favorites. See favorite list..'
            ], 200);
        }
    }

    public function favorites(Request $request)
    {
        $data = $request->all();

        $favorites = $this->user_repository->findByColumns(['slack_user_id' => $data['user_id']])->first()->favorites()->orderBy('favorites.updated_at', 'desc')->get();

        $attachments = [];

        foreach ($favorites as $link) {
            //dd([$link, $link->pivot, $link->pivot->created_at, $link->pivot_created_at]);
                $added_at = $link->pivot->created_at != null ? date_format($link->pivot->created_at, 'jS F Y') : null;
                
                $attachments[] = [
                    //'pretext' => $data['user_name'] . '\'s links:',
                    'color' => '#1a5dc9',
                    'fields' => [
                        [
                            'title' => 'Added: ' . $added_at,
                            'value' => $link->url,
                            'short' => true
                        ],
                        [
                            'title' => 'Tags:',
                            'value' => implode(' ', $link->tags()->pluck('name')->toArray())
                        ]
                    ]
                ];
        }

        $headers = [
            'Content-type' => 'application/json',
        ];

        $response_data = [
            'response_type' => 'ephemeral',
            'text' => 'Your favorites:',
            'attachments' => $attachments
        ];

        //$this->web_service->performCurlAction(env('SLACK_WEBHOOK_URL'), 'post', $response_data, $headers);

        return response()->json($response_data, 200);
    }

    public function all(Request $request)
    {
        $data = $request->all();
/**/
        $links = $this->link_repository->all()->sortByDesc('updated_at');
        if (trim($data['text'], ' ')) {
            $text = $this->format_service->escapeContent($data['text']);
            $tag_words = $this->format_service->getTags($text);
            $tags_found = $this->tag_repository->getTagsInGroup($tag_words);

            if ($tags_found->count()) {
                $links = \App\Models\Link::whereHas('tags', function($query) use ($tags_found){
                    $query->whereIn('id', $tags_found);
                })->get();
            } else {
                $response_data = [
                    'response_type' => 'ephemeral',
                    'text' => 'Oops! There is not links with the specified tag(s).',
                ];

                return response()->json($response_data, 200);
            }
        }
/**/

        $attachments = [];

        foreach ($links as $link) {
                $attachments[] = [
                    //'pretext' => $data['user_name'] . '\'s links:',
                    'callback_id' => 'link_added',
                    'color' => '#1a5dc9',
                    'fields' => [
                        [
                            'title' => 'Added: ' . $link->date,
                            'value' => $link->url,
                            'short' => true
                        ],
                        [
                            'title' => 'Tags:',
                            'value' => implode(' ', $link->tags()->pluck('name')->toArray())
                        ]
                    ],
                    'actions' => [
                        [
                            'name' =>  'favorite',
                            'text' =>  '★ Add to favorites',
                            'type' =>  'button',
                            'value' =>  $link->id,
                            'style' => 'primary'
                        ]
                    ]
                    /*
                    'actions' => [
                        [
                            'name' =>  'next',
                            'text' =>  'Next page >>',
                            'type' =>  'button',
                            'value' =>  '1',
                            'style' => 'primary'
                        ]
                    ]
                    */
                ];
        }

        $headers = [
            'Content-type' => 'application/json',
        ];

        $response_data = [
            'response_type' => 'ephemeral',
            'text' => 'All links:',
            'attachments' => $attachments
        ];

        //$this->web_service->performCurlAction(env('SLACK_WEBHOOK_URL'), 'post', $response_data, $headers);

        return response()->json($response_data, 200);
    }

    public function addPreferences(Request $request)
    {
        $attachments = [];
        $data = $request->all();
        $user = $this->user_repository->firstOrCreate(['slack_user_id' => $data['user_id'], 'slack_username' => $data['user_name']]);

        $text = $this->format_service->escapeContent($data['text']);
        $tag_words = $this->format_service->getTags($text);
        $tag_ids = [];

        if (empty($tag_words)) {
            return response()->json([
                'response_type' => 'ephemeral',
                'text' => 'Oops! Type at least one tag.'
            ], 200);
        }

        $tag_ids = $this->tag_repository->massFirstOrCreate($tag_words);
        $user_tags = $user->preferences->pluck('id')->toArray();
        $filtered_tag_ids = array_unique(array_merge($tag_ids, $user_tags));

        $this->user_repository->syncTags($user->id, $filtered_tag_ids);

        $user->load('preferences');
        $prefs = $user->preferences->pluck('name')->transform(function($item, $key){
            return '#' . $item;
        });

        $attachments[] = [
            'color' => '#36a64f',
            'fields' => [
                [
                    'title' => 'Preferences:',
                    'value' => implode(' - ', $prefs->toArray())
                ]
            ]
        ];

        return response()->json([
            'response_type' => 'ephemeral',
            'text' => 'Fine! Your preferences has been updated.',
            'attachments' => $attachments
        ], 200);
    }

    public function getRecommendations(Request $request)
    {
        $attachments = [];
        $data = $request->all();
        $user = $this->user_repository->firstOrCreate(['slack_user_id' => $data['user_id'], 'slack_username' => $data['user_name']]);

        $recommendations = $this->link_repository->recommendations($user->id);

        foreach ($recommendations as $link) {
                $attachments[] = [
                    //'pretext' => 'Recommendations based on your preferences:',
                    'color' => '#1a5dc9',
                    'callback_id' => 'link_added',
                    'fields' => [
                        [
                            'title' => 'Added: ' . $link->date,
                            'value' => $link->url,
                            'short' => true
                        ],
                        [
                            'title' => 'Tags:',
                            'value' => implode(' ', $link->tags()->pluck('name')->toArray())
                        ]
                    ],
                    'actions' => [
                        [
                            'name' =>  'favorite',
                            'text' =>  '★ Add to favorites',
                            'type' =>  'button',
                            'value' =>  $link->id,
                            'style' => 'primary'
                        ]
                    ]
                ];
        }

        $headers = [
            'Content-type' => 'application/json',
        ];

        $response_data = [
            'response_type' => 'ephemeral',
            'text' => 'Recommendations based on your preferences:',
            'attachments' => $attachments
        ];

        //$this->web_service->performCurlAction(env('SLACK_WEBHOOK_URL'), 'post', $response_data, $headers);

        return response()->json($response_data, 200);
    }
}

