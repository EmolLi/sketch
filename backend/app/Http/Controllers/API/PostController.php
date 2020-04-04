<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Thread;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePost;
use App\Http\Requests\StoreReport;
use App\Http\Resources\PostResource;
use App\Http\Resources\ThreadProfileResource;
use App\Http\Resources\ThreadBriefResource;
use App\Http\Resources\PaginateResource;
use App\Sosadfun\Traits\PostObjectTraits;
use App\Sosadfun\Traits\ThreadObjectTraits;
use App\Events\NewPost;


class PostController extends Controller
{
    use PostObjectTraits;
    use ThreadObjectTraits;
    /**
    * Display a listing of the resource.
    *
    * @return \Illuminate\Http\Response
    */

    public function __construct()
    {
        $this->middleware('auth:api')->except('show','redirect');

    }

    /**
    * Store a newly created resource in storage.
    *
    * @param  \Illuminate\Http\Request  $request
    * @return \Illuminate\Http\Response
    */
    public function store($id, StorePost $form)
    {

        $thread = Thread::on('mysql::write')->find($id);
        if(!$thread||!auth('api')->user()){abort(404);}
        if(auth('api')->user()->no_posting){abort(403,'禁言中');}

        $post = $form->storePost($thread);

        event(new NewPost($post));

        $post = $this->postProfile($post->id);
        return response()->success(new PostResource($post));
    }

    /**
    * Display the specified resource.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function show($thread, $post)
    {
        $post = $this->postProfile($post);
        if(!$post){abort(404);}
        $thread = $this->findThread($thread);
        if(!$thread){abort(404);}
        if($thread->id!=$post->thread_id){abort(403);}

        return response()->success([
            'thread' => new ThreadBriefResource($thread),
            'post' => new PostResource($post),
        ]);
    }

    public function redirect($post)
    {
        $post = $this->postProfile($post);
        return response()->error([
            'post_id' => $post->id,
            'thread_id' => $post->thread_id,
            'url' => route('post.show',['post'=>$post->id, 'thread' => $post->thread_id]),
        ], 301);
    }


    /**
    * Update the specified resource in storage.
    *
    * @param  \Illuminate\Http\Request  $request
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function update($post, StorePost $form)
    {
        $post = Post::on('mysql::write')->find($post);
        $form->updatePost($post);
        $this->clearPost($post->id);
        $post = $this->postProfile($post->id);
        return response()->success(new PostResource($post));

    }

    /**
    * Remove the specified resource from storage.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function destroy($id)
    {
        $post = Post::on('mysql::write')->find($id);
        if(!$post){abort(404);}
        if($post->user_id!=auth('api')->id()&&!auth('api')->user()->isAdmin()){abort(403,'不能删除非自己的回帖');}

        if($post->type==='post'||$post->type==='comment'||auth('api')->user()->isAdmin()){
            $post->delete();
            return response()->success('deleted post'.$id);
        }
        abort(420,'什么都没做');
    }

    public function fold($post)
    {
        $post = Post::findOrFail($id);
        if(!$post){abort(404);}
        $thread=$post->thread;
        if(!$thread||!$post){abort(404);}
        if($thread->is_locked||$thread->user_id!=auth('api')->id()||auth('api')->user()->no_posting){abort(403);}

        if($post->fold_state>0){abort(409);}

        if($post->user->isAdmin()&&!$post->is_anonymous){abort(413);}

        $post->update(['fold_state'=>2]);

        return response()->success(new PostResource($post));
    }

    public function submit_report(StoreReport $form)
    {
        $thread = $this->findThread($form->thread_id);

        if($thread->channel()->type!="report"){ abort(409, '非举报楼'); }

        $user = auth('api')->user();
        if ((!$user->isAdmin()) &&
            ($thread->is_locked || ((!$thread->is_public)&&($thread->user_id != $user->id)) )) {
            abort(403, '本主题锁定或设为隐私，不能回帖');
        }
        if($user->no_posting){
            abort(497, '你被禁言中，无法回帖');
        }

        if(!$user->isAdmin()&&($user->level<4||$user->quiz_level<3)){
            abort(406, '你的等级或答题等级不足，暂时无法提交举报');
        }

        if($form->finished!="已完成"){
            abort(407, '已完成确认项不正确');
        }

        $post = $form->generateReport($thread);
        event(new NewPost($post));
        return response()->success(new PostResource($post));
    }
}
