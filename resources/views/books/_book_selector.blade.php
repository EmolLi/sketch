<!-- 进入筛选模式 -->
<div class="selector">
    <div class="dropdown">
        <span class="button-group">
            <button type="button" class="btn btn-default btn-lg dropdown-toggle dropdown-menu-narrow" data-toggle="dropdown">原创性<span class="caret"></span></button>
            <ul class="dropdown-menu">
                <li><a class="" href="{{ route('books.index', array_merge(['inChannel' => 1], request()->only('withTag','excludeTag','withBianyuan','ordered'))) }}">原创小说</a></li>
                <li><a class="" href="{{ route('books.index', array_merge(['inChannel' => 2], request()->only('withTag','excludeTag','withBianyuan','ordered'))) }}">同人小说</a></li>
            </ul>
        </span>

        <span class="button-group">
            <button type="button" class="btn btn-default btn-lg dropdown-toggle dropdown-menu-narrow" data-toggle="dropdown">篇幅<span class="caret"></span></button>
            <ul class="dropdown-menu">
                @foreach($tags['book_length_tags'] as $tag)
                <li><a class="" href="{{ route('books.index', array_merge(['withTag' => StringProcess::mergeWithTag($tag->id, request()->withTag)], request()->only('excludeTag','withBianyuan','ordered','inChannel'))) }}">{{ $tag->tag_name }}</a></li>
                @endforeach
            </ul>
        </span>

        <span class="button-group">
            <button type="button" class="btn btn-default btn-lg dropdown-toggle dropdown-menu-narrow" data-toggle="dropdown">进度<span class="caret"></span></button>
            <ul class="dropdown-menu">
                @foreach($tags['book_status_tags'] as $tag)
                <li><a class="" href="{{ route('books.index', array_merge(['withTag' => StringProcess::mergeWithTag($tag->id, request()->withTag)], request()->only('excludeTag','withBianyuan','ordered','inChannel'))) }}">{{ $tag->tag_name }}</a></li>
                @endforeach
            </ul>
        </span>

        <span class="button-group">
            <button type="button" class="btn btn-default btn-lg dropdown-toggle dropdown-menu-narrow" data-toggle="dropdown">性向<span class="caret"></span></button>
            <ul class="dropdown-menu">
                @foreach($tags['sexual_orientation_tags'] as $tag)
                <li><a class="" href="{{ route('books.index', array_merge(['withTag' => StringProcess::mergeWithTag($tag->id, request()->withTag)], request()->only('excludeTag','withBianyuan','ordered','inChannel'))) }}">{{ $tag->tag_name }}</a></li>
                @endforeach
            </ul>
        </span>

        <span class="button-group">
            <button type="button" class="btn btn-default btn-lg dropdown-toggle dropdown-menu-narrow" data-toggle="dropdown">编推<span class="caret"></span></button>
            <ul class="dropdown-menu">
                @foreach($tags['editor_tags'] as $tag)
                <li><a class="" href="{{ route('books.index', array_merge(['withTag' => StringProcess::mergeWithTag($tag->id, request()->withTag)], request()->only('excludeTag','withBianyuan','ordered','inChannel'))) }}">{{ $tag->tag_name }}</a></li>
                @endforeach
            </ul>
        </span>

        <span class="button-group">
            <button type="button" class="btn btn-default btn-lg dropdown-toggle dropdown-menu-narrow" data-toggle="dropdown">排序<span class="caret"></span></button>
            <ul class="dropdown-menu">
                @foreach(config('selectors.book_filter.ordered') as $ordered => $explanation)
                    <li><a class="" href="{{ route('books.index', array_merge(['ordered' => $ordered], request()->only('withTag','excludeTag','withBianyuan','inChannel'))) }}">{{$explanation}}</a></li>
                @endforeach
            </ul>
        </span>
            <a href="{{ route('all.tags') }}" class="btn btn-default btn-lg sosad-button-control">标签列表</a>
        <span>

        </span>
    </div>
</div>
