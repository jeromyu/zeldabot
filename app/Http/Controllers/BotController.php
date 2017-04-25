<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CurlService;
use App\Services\DataFormatService;
use App\Repositories\UserRepository;
use App\Repositories\LinkRepository;
use App\Repositories\TagRepository;
use Illuminate\Validation\ValidationException;

class BotController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        CurlService $curl_service,
        DataFormatService $format_service,
        UserRepository $user_repository,
        LinkRepository $link_repository,
        TagRepository $tag_repository
    )
    {
        $this->curl_service = $curl_service;
        $this->format_service = $format_service;
        $this->user_repository = $user_repository;
        $this->link_repository = $link_repository;
        $this->tag_repository = $tag_repository;
    }

    public function webhook(Request $request)
    {
        if ($request->input('token') == env('SLACK_APP_TOKEN')) {
            $text = $request->input('text');

            $data = json_encode([
                'text' => $text . ' @' . $request->input('user_name'),
            ]);

            $headers = [
                'Content-type' =>  'application/json',
            ];

            $this->curl_service->performAction(env('TELEGRAM_WEBHOOK_URL'), 'post', $data, $headers);
        }
    }

    public function addlink(Request $request)
    {
        try {
            $this->validate($request, [
                'text' => ['regex:@^<(https?|ftp):\/\/[^\s/$.?#].[^\s]*>((\ .*)+([a-zA-Z0-9]+))*(\ )*@'],
            ]);
        } catch (ValidationException $e) {
            dd($e->response->original);
        }

        $data = $request->all();
        $text = $this->format_service->escapeContent($data['text']);
        $command_entities = $this->format_service->getLinkAndTags($text);

        $headers = [
            'Content-type' => 'application/json',
        ];

        $response_data = json_encode([
            'attachments' => [
                [
                    'pretext' => $data['user_name'] . ' added a new link.',
                    'color' => '#36a64f',
                    'title' => $command_entities['link'],
                    'title_link' => $command_entities['link'],
                    'fields' => [
                        [
                            'title' => 'tags',
                            'value' => '#php #laravel',
                        ]
                    ],
                    'actions' => [
                        [
                            'name' =>  'favorite',
                            'text' =>  'Add to favorites',
                            'type' =>  'button',
                            'value' =>  '1',
                            'style' => 'primary'
                        ]
                    ]
                ]
            ]
        ]);
/*
        $user = $this->user_repository->firstOrCreate(['slack_user_id' => $data['user_id'], 'slack_username' => $data['user_name']]);

        $link_data = [
            'url' => $command_entities['link'],
            'user_id' => $user->id,
            'tags' => $this->tag_repository->massFirstOrCreate($command_entities['tags'])
        ];

        if ($this->link_repository->save((object) $link_data)) {
            $this->curl_service->performAction(env('TELEGRAM_WEBHOOK_URL'), 'post', $response_data, $headers);
        }
    }
    */
    $this->curl_service->performAction(env('TELEGRAM_WEBHOOK_URL'), 'post', $response_data, $headers);
    }
}

