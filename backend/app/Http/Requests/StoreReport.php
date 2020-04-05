<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use DB;
use StringProcess;
use App\Models\Thread;
use App\Models\Post;
use App\Models\PostInfo;
use Carbon;
use Cache;
use App\Sosadfun\Traits\GeneratePostDataTraits;
use App\Sosadfun\Traits\FindModelTrait;

class StoreReport extends FormRequest
{
    use GeneratePostDataTraits;
    use FindModelTrait;

    /**
    * Determine if the user is authorized to make this request.
    *
    * @return bool
    */
    public function authorize()
    {
        return true;
    }

    /**
    * Get the validation rules that apply to the request.
    *
    * @return array
    */
    public function rules()
    {
        return [
            'report_kind' =>  'required|string|max:100',
            'body' => 'required|string|min:10|max:20000',
            'reviewee_id' => 'numeric|min:0',
            'reviewee_type' => 'nullable|string|min:0|max:10',
            'thread_id' => 'required|numeric|min:0',
        ];
    }

    public function generateReport($thread){
        $post_data = $this->generatePostData($thread);
        $post_data['type'] = 'case';
        $post_data['body'] = "违禁类型：".$this->report_kind."\n违禁理由：".$post_data['body'];
        $post_data['brief'] = StringProcess::trimtext($post_data['body'], 45);

        $info_data = $this->only('reviewee_id', 'reviewee_type');

        $referer = $this->validateReferer($info_data, $thread);

        if($referer) {
            $brief = "举报违禁".$this->generateRecord($referer, $info_data['reviewee_type']);
            $post_data['body'] = $brief."\n".$post_data['body'];
            $post_data['brief'] = StringProcess::trimtext($post_data['body'], 45);
        } else {
            $info_data['reviewee_id'] = null;
            $info_data['reviewee_type'] = null;
        }

        $info_data['abstract'] = StringProcess::trimtext($post_data['body'],150);

        $post = DB::transaction(function()use($post_data, $info_data){
            $post = Post::create($post_data);
            $info_data['post_id']=$post->id;
            $info = PostInfo::create($info_data);
            return $post;
        });
        return $post;
    }

    public function validateReferer($info_data, $thread){
        // 如果填写的书籍id就是本帖的id，归零
        if($info_data['reviewee_type']==='thread'&&$info_data['reviewee_id']===$thread->id){
            return ;
        }
        if($info_data['reviewee_type']&&$info_data['reviewee_id']>0){
            $recent_reports = DB::table('post_infos')
            ->join('posts','posts.id','=','post_infos.post_id')
            ->where('post_infos.reviewee_type',$info_data['reviewee_type'])
            ->where('post_infos.reviewee_id', $info_data['reviewee_id'])
            ->where('posts.type', 'case')
            ->where('posts.created_at','>',Carbon::now()->subDays(config('constants.report_interval_days')))
            ->count();
            if($recent_reports>0){
                abort(409,config('constants.report_interval_days').'天内已有人举报过相同内容，请直接点评补充举报详情');
            }
            $model = $this->findModel(
                $info_data['reviewee_type'],
                $info_data['reviewee_id'],
                array_keys(config('constants.admin_content_types'))
            );
            if($model){
                return $model;
            }
        }
        return ;
    }

    public function generateRecord($model, $model_type='')
    {
        switch ($model_type) {
            case 'thread':
            return "主题：《".$model->title."》".$model->brief;
            break;
            case 'post':
            return "回帖：".$model->brief;
            break;
            case 'status':
            return "动态：".$model->brief;
            case 'quote':
            return "题头：".$model->body;
            case 'user':
            return "用户：".$model->name;
            break;
        }
    }

}
