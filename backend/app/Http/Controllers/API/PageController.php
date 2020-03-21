<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\ConstantObjects;
use App\Http\Resources\QuoteResource;
use App\Http\Resources\StatusResource;
use App\Http\Resources\ThreadBriefResource;
use App\Http\Resources\TagResource;
use App\Http\Resources\HomeworkBriefResource;
use App\Http\Resources\ChannelResource;
use App\Http\Resources\TitleResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\PostIndexResource;
use App\Http\Resources\AdministrationResource;
use App\Sosadfun\Traits\PageObjectTraits;
use App\Sosadfun\Traits\AdministrationTraits;
use App\Http\Resources\PaginateResource;
use Cache;
use Auth;

class PageController extends Controller
{
    use AdministrationTraits;
    use PageObjectTraits;

    public function home()
    {
        return response()->success([
            'quotes' => QuoteResource::collection($this->quotes()),
            'recent_recommendations' => PostIndexResource::collection($this->short_recommendations()),
            'homeworks' => HomeworkBriefResource::collection($this->pages_homeworks()),
            'channel_threads' => [
                [
                    'channel_id' => 1,
                    'threads' => ThreadBriefResource::collection($this->channel_threads(1)),
                ],[
                    'channel_id' => 2,
                    'threads' => ThreadBriefResource::collection($this->channel_threads(2)),
                ],
            ],
        ]);
    }

    public function allTags()
    {
        return response()->success([
            'tags' => TagResource::collection(ConstantObjects::allTags()),
        ]);
    }

    public function allChannels()
    {
        return response()->success([
            'channels' => ChannelResource::collection(ConstantObjects::allChannels()),
        ]);
    }

    public function allTitles()
    {
        return response()->success([
            'titles' => TitleResource::collection(ConstantObjects::allTitles()),
        ]);
    }

    public function system()
    {
        return response()->success([
            'system_variable' => ConstantObjects::system_variable(),
            'forbidden_words' => config('forbidden_words'),
            'selectors' => config('selectors'),
            'constants' => config('constants'),
            'preferences' => config('preferences'),
        ]);
    }

    // all administration records
    public function administration_records(Request $request)
    {

        $is_public = '';
        // only clear administration reminder when he checks his own admin records
        if (Auth::check()&&Auth::user()->isAdmin()) { $is_public = "include_private"; }

        $page = is_numeric($request->page)? $request->page:'1';

        $records = Cache::remember('adminrecords-p'.$page, 2, function () use($page, $is_public) {
            return $this->findAdminRecords(0, $page, $is_public, config('preference.index_per_page'));
        });

        return response()->success([
            'record' => AdministrationResource::collection($records),
            'paginate' => new PaginateResource($records)
        ]);
    }
}
