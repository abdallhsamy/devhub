<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleRelationshipResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            'author' => [
                'data' => new AuthorResource($this->author),
            ],
            'comments' => (new ArticleCommentsRelationshipResource($this->comments))->additional(['article_json' => $this]),
            'hubs'     => (new ArticleHubsRelationshipResource($this->hubs))->additional(['article_json' => $this]),
        ];
    }

    public function with($request)
    {
        return [
            'links' => [
                'self' => route('articles.index'),
            ],
        ];
    }
}
