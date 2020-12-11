<?php

namespace App\Http\Controllers;

use App\Http\Resources\HubResource;
use App\Http\Resources\HubsResource;
use App\Models\Hub;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HubController extends Controller
{
    /**
     * @var string
     */
    private string $content = 'hub_show';

    public function postsApiRoute(int $id)
    {
        switch (\request()->path()) {
            case 'hubs/'.$id:
                return '/api/hubs/'.$id.'/top/day';
            case 'hubs/'.$id.'/top/week':
                return '/api/hubs/'.$id.'/top/week';
            case 'hubs/'.$id.'/top/month':
                return '/api/hubs/'.$id.'/top/month';
            case 'hubs/'.$id.'/all':
                return '/api/hubs/'.$id.'/all';
            default:
                abort(404);
        }
    }

    public function show(int $id)
    {
        $hub = new HubResource(Hub::withCount('followers')->findOrFail($id));

        return view(
            'pages.hubs.show',
            [
                'hub'         => $hub,
                'posts_count' => $hub->posts->count(),
                'url'         => $this->postsApiRoute($id),
                'content'     => $this->content,
            ]
        );
    }

    public function index()
    {
        $top_hubs = new HubsResource(Hub::orderBy('rating', 'DESC')->take(5)->get());
        $top_followed_hubs = new HubsResource(
            Hub::withCount('followers')->orderBy(
                'followers_count',
                'desc'
            )->take(5)->get()
        );

        return view(
            'pages.hubs.hubs',
            [
                'top_hubs'          => $top_hubs,
                'top_followed_hubs' => $top_followed_hubs,
            ]
        );
    }

    /**
     * @param Request $request
     *
     * @throws \Exception
     *
     * @return JsonResponse
     */
    public function follow(Request $request): JsonResponse
    {
        $userId = Auth::user();
        $hub = Hub::findOrFail($request->get('id'));
        if (isset($hub) && ! $hub->isFollowedBy($userId)) {
            $userId->follow($hub);

            return response()->json(['success' => 'success'], 200);
        }
        if ($hub->isFollowedBy($userId)) {
            $userId->unfollow($hub);

            return response()->json(['delete' => 'delete'], 200);
        }

        return response()->json(['error' => 'error'], 401);
    }
}