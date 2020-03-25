<?php

namespace App\Http\Controllers\API;

use App\Models\Tag;
use App\Models\Thread;
use App\Models\Post;
use App\Models\Status;
use App\Models\User;
use App\Models\PublicNotice;
use App\Models\Administration;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaginateResource;
use DB;
use ConstantObjects;
use CacheUser;
use App\Sosadfun\Traits\AdminManageTraits;

// QUESTION: 为什么controllers/API 里有一个 adminController,
// controllers/ 里也有一个adminController 呢?

// TODO: merge to one
// content_not_public（内容不公开：如果存在本项就处理）
// content_is_public（内容公开：如果存在本项就处理）

class AdminController extends Controller
{
    use AdminManageTraits;

    public function __construct()
    {
        $this->middleware('admin');
    }

    public function management(Request $request){
        $this->validateManagementInput($request);
        $administration = null;
        if (($request->report_post_id && !$request->report_summary) ||
            (!$request->report_post_id && $request->report_summary)) {
                abort(422, '处理举报需同时填写report_post_id和report_post_summary');
            }

        if($request->report_post_id){
            $report_post = $this->update_report($request);
        }

        if(!$request->report_post_id||($request->report_post_id&&$request->report_summary==="approve")){
            // 如果并非举报，直接处理。如果是举报，且通过举报，处理被举报内容
            $administration = $this->content_N_user_management($request);
        }

        if($request->report_post_id&&$request->report_summary!="approve"){
            $administration = $this->report_management($request, $report_post);
        }
        return response()->success($administration ? null : new AdministrationResource($administration));
    }

    private function validateManagementInput(Request $request){
        $this->validate($request, [
            'content_id' => 'required|numeric',
            'content_type' => 'required|in:'.(implode(",",array_keys(config('content_types')))),
            'reason' => 'required|string',
            'administration_summary' => 'required|in:'.(implode(",",array_keys(config('administration_summary')))),
            'report_post_id' => 'string',
            'report_summary' => 'in:'.(implode(",",array_keys(config('report_case_summary')))),
            'record_not_public' => 'boolean',

            // 被管理内容的处理
            'content_fold' => 'boolean',
            'content_unfold' => 'boolean',
            'content_is_bianyuan' => 'boolean',
            'content_not_bianyuan' => 'boolean',
            'content_not_public' => 'boolean',
            'content_is_public' => 'boolean',
            'content_no_reply' => 'boolean',
            'content_allow_reply' => 'boolean',
            'content_lock' => 'boolean',
            'content_unlock' => 'boolean',
            'content_change_channel' => 'boolean',
            'content_change_channel_id' => 'numeric',
            'content_pull_up' => 'boolean',
            'content_pull_down' => 'boolean',
            'add_tag' => 'string',
            'remove_tag' => 'string',
            'add_tag_to_all_components' => 'string',
            'remove_tag_from_all_components' => 'string',
            'content_remove_anonymous' => 'boolean',
            'content_is_anonymous' => 'boolean',
            'majia' => 'string',
            'content_type_change' => 'in:'.(implode(",",array_keys(config('constants.post_types')))),
            'content_delete' => 'boolean',

            // 对被管理内容创建者的管理(以及如果被管理内容就是用户的时候，直接管理用户)
            'user_no_posting_days' => 'numeric',
            'user_allow_posting' => 'boolean',
            'user_no_logging_days' => 'numeric',
            'user_allow_logging' => 'boolean',
            'user_no_homework_days' => 'numeric',
            'user_allow_homework' => 'boolean',
            'user_reset_password' => 'boolean',
            'user_level_clear' => 'boolean',
            'user_invitation_clear' => 'boolean',
            'gift_title_id' => 'numeric',
            'remove_title_id' => 'numeric',
            'send_homework_invitation' => 'boolean',

            // 对举报帖的管理（仅当存在report_post_id的时候起作用）
            'report_post_fold' => 'boolean',
            'reporter_no_posting_days' => 'numeric',
            'reporter_no_logging_days' => 'numeric',
            'reporter_level_clear' => 'boolean',
            'reporter_invitation_clear' => 'boolean',

        ]);
    }

    // 必填
    // content_id(被管理内容的id)
    // content_type(被管理内容的type，可为thread,post,user,quote,status)
    // report_post_id(如果是举报，必须输入对应的举报正文post_id)
    // report_summary='approve'/'disapprove'/'abuse'（如果是举报，输入举报性质判断，受理/暂不受理/滥用举报）
    // reason=(string)（举报具体理由原因，如“违规发文/断头车”等等）
    // administration_summary='punish'/'neutral'/'reward'（这个管理是什么性质，奖励还是惩罚）
    // ```
    // ### Optional 选填
    // ```
    // 被管理内容的处理：
    // content_fold（内容折叠：如果存在本项就处理）
    // content_unfold（内容取消折叠：如果存在本项就处理）
    // content_is_bianyuan（内容转边限：如果存在本项就处理）
    // content_not_bianyuan（内容标记为非边限：如果存在本项就处理）
    // content_not_public（内容不公开：如果存在本项就处理）
    // content_is_public（内容公开：如果存在本项就处理）
    // content_no_reply（内容不可回复：如果存在本项就处理）
    // content_allow_reply（内容可回复：如果存在本项就处理）
    // content_lock（内容锁定：如果存在本项就处理）
    // content_unlock（内容解除锁定：如果存在本项就处理）
    // content_change_channel（内容转移到其他的channel：如果存在本项就处理）
    // content_change_channel_id(int)(具体转移到channel的id，只在存在content_change_channel的情况起作用)
    // content_pull_up（内容上浮：如果存在本项就处理）
    // content_pull_down（内容下沉：如果存在本项就处理）
    // add_tag="string"（输入要添加的tag的id或者名称，本项存在则添加tag）
    // remove_tag="string"（输入要去除的tag的id或者名称，本项存在则去除tag）
    // add_tag_to_all_components="string"（输入要添加的tag的id或者名称，本项存在则对thread内的每一项component添加tag）
    // remove_tag_from_all_components="string"（输入要去除的tag的id或者名称，本项存在则对thread内的每一项component去除tag）
    // content_remove_anonymous（内容解除马甲：如果存在本项就处理）
    // content_is_anonymous（内容披上马甲：如果存在本项就处理）
    // majia(string)（马甲名称：必须存在content_is_anonymous的时候才起作用）
    // content_type_change（内容转换类型：内容必须是post）
    // content_type_change_to="string"('post','comment','report'...)（content_type_change存在时才起作用）
    // content_delete（内容删除：如果存在本项就处理）
    //
    // 对被管理内容创建者的管理(以及如果被管理内容就是用户的时候，直接管理用户)
    // user_no_posting_days(int)
    // user_allow_posting（用户解除禁言：如果存在本项就处理）
    // user_no_logging_days(int)
    // user_allow_logging（用户解除封禁：如果存在本项就处理）
    // user_no_homework_days(int)
    // user_allow_homework（用户解除作业禁令：如果存在本项就处理）
    // user_reset_password
    // user_level_clear（用户等级虚拟物清零：如果存在本项就处理）
    // user_invitation_clear（用户邀请码清零：如果存在本项就处理）
    // user_value_change[salt]（用户虚拟物数值变化：正加，负减，为0则不处理）
    // user_value_change[fish]
    // user_value_change[ham]
    // user_value_change[level]
    // user_value_change[token_limit]
    // gift_title_id(int)（赠送头衔id：本项存在且非0则赠送）
    // remove_title_id（去除头衔id：本项存在且非0则去除）
    // send_homework_invitation（发送作业邀请：如果存在本项就发放）
    // homework_invitation[homework_id]（作业邀请券指定作业场次，可为0）
    // homework_invitation[homework_level]（作业邀请券对应作业等级限制）
    // homework_invitation[homework_role]=作业角色(worker/critic/watcher)
    // homework_invitation[valid_days]（作业邀请券有效期，默认请填180）
    //
    // 对举报帖的管理（仅当存在report_post_id的时候起作用）
    // report_post_fold（举报帖折叠）
    // reporter_no_posting_days（举报人禁言x天）
    // reporter_no_logging_days（举报人封禁x天）
    // reporter_level_clear（举报人等级清零：本项存在时起效）
    // reporter_invitation_clear（举报人邀请码额度清零：本项存在时起效）
    // reporter_value_change（举报人虚拟物额度清零：本项存在时起效）
    // reporter_value_change[salt]
    // reporter_value_change[fish]
    // reporter_value_change[ham]
    // reporter_value_change[level]
    // reporter_value_change[token_limit]


    public function searchrecords(Request $request){
        $this->validate($request, [
            'name' => 'nullable|string|min:1|max:191',
            'name_type' => 'required|in:'.(
                implode(",", array_keys(config('administration_searchrecord_nametype')))),
        ]);

        $name = $request->name;
        $users = [];
        $email_modification_records = [];
        $password_reset_records = [];
        $donation_records = [];
        $application_records = [];
        $black_list_emails = [];
        $quotes = [];
        $user_logins = [];
        $posts = [];

        if($request->name_type == 'user_id'){
            $users = User::with('emailmodifications',
                'passwordresets', 'registrationapplications.reviewer',
                'donations', 'info', 'usersessions', 'userlogins')
            ->where('id',$request->name)
            ->paginate(config('preference.records_per_part'))
            ->appends($request->only('page','name','name_type'));
        }

        if($request->name_type == 'is_forbidden'){
            // QUESTION: 如果这里,我先select user_infos where is_forbidden = 1
            // 再join user,有可能会快一点嘛? (也许query builder自己就会优化query?)
            $users = User::with('emailmodifications', 'passwordresets',
                'registrationapplications.reviewer', 'donations', 'info',
                'usersessions', 'userlogins')
            ->join('user_infos','users.id','=','user_infos.user_id')
            ->where('user_infos.is_forbidden', 1)
            ->paginate(config('preference.records_per_part'))
            ->appends($request->only('page','name','name_type'));
        }

        if($request->name_type && $request->name_type=='username'){
            $users = User::with('emailmodifications', 'passwordresets',
                'registrationapplications.reviewer', 'donations', 'info',
                'usersessions', 'userlogins')
            ->nameLike($name)
            ->paginate(config('preference.records_per_part'))
            ->appends($request->only('page','name','name_type'));
        }

        if($request->name_type && $request->name_type=='email'){
            $users = User::with('emailmodifications', 'passwordresets',
                'registrationapplications.reviewer', 'donations', 'info',
                'usersessions', 'userlogins')
            ->emailLike($name)
            ->paginate(config('preference.records_per_part'))
            ->appends($request->only('page','name','name_type'));

            $email_modification_records = \App\Models\HistoricalEmailModification::with('user.info')
            ->emailLike($name)
            ->paginate(config('preference.records_per_part'))
            ->appends($request->only('page','name','name_type'));

            $donation_records = \App\Models\DonationRecord::with('user.info')
            ->emailLike($name)
            ->paginate(config('preference.records_per_part'))
            ->appends($request->only('page','name','name_type'));

            $application_records = \App\Models\RegistrationApplication::with('user.info','reviewer','owner')
            ->emailLike($name)
            ->paginate(config('preference.records_per_part'))
            ->appends($request->only('page','name','name_type'));

            $black_list_emails = \App\Models\FirewallEmail::emailLike($name)
            ->paginate(config('preference.records_per_part'))
            ->appends($request->only('page','name','name_type'));
        }

        if($request->name_type && $request->name_type=='ip_address'){
            $users = User::with('emailmodifications', 'passwordresets',
            'registrationapplications.reviewer', 'donations', 'info',
            'usersessions', 'userlogins')
            ->creationIPLike($name)
            ->paginate(config('preference.records_per_part'))
            ->appends($request->only('page','name','name_type'));

            $email_modification_records = \App\Models\HistoricalEmailModification::with('user.info')
            ->creationIPLike($name)
            ->paginate(config('preference.records_per_part'))
            ->appends($request->only('page','name','name_type'));

            $application_records = \App\Models\RegistrationApplication::with('user.info','reviewer','owner')
            ->creationIPLike($name)
            ->paginate(config('preference.records_per_part'))
            ->appends($request->only('page','name','name_type'));

            $user_logins = \App\Models\HistoricalUserLogin::with('user.info')
            ->creationIPLike($name)
            ->paginate(config('preference.records_per_part'))
            ->appends($request->only('page','name','name_type'));
        }

        if($request->name_type && $request->name_type=='latest_created_user'){
            $users = User::with('emailmodifications', 'passwordresets',
            'registrationapplications.reviewer', 'donations', 'info',
            'usersessions', 'userlogins')
            ->latest()
            ->paginate(config('preference.records_per_part'))
            ->appends($request->only('page','name','name_type'));
        }

        if($request->name_type && $request->name_type=='latest_invited_user'){
            $users = User::whereHas('info', function ($query){
                $query->where('invitor_id', '>', 0);
            })
            ->with('emailmodifications', 'passwordresets', 'registrationapplications.reviewer',
            'donations', 'info', 'usersessions', 'userlogins')
            ->latest()
            ->paginate(config('preference.records_per_part'))
            ->appends($request->only('page','name','name_type'));
        }

        if($request->name_type && $request->name_type=='latest_email_modification'){
            $email_modification_records = \App\Models\HistoricalEmailModification::with('user.info')
            ->latest()
            ->paginate(config('preference.records_per_part'))
            ->appends($request->only('page','name','name_type'));
        }

        if($request->name_type && $request->name_type=='latest_password_reset'){
            $password_reset_records = \App\Models\HistoricalPasswordReset::with('user.info')
            ->latest()
            ->paginate(config('preference.records_per_part'))
            ->appends($request->only('page','name','name_type'));
        }

        if($request->name_type && $request->name_type=='max_suspicious_sessions'){
            $users = User::with('emailmodifications', 'passwordresets',
            'registrationapplications.reviewer', 'donations', 'info', 'usersessions',
            'userlogins')
            ->join('historical_user_sessions','users.id','=','historical_user_sessions.user_id')
            ->orderby('historical_user_sessions.mobile_count', 'desc')
            ->select('users.*')
            ->paginate(config('preference.records_per_part'))
            ->appends($request->only('page','name','name_type'));
        }

        if($request->name_type && $request->name_type=='active_suspicious_sessions'){
            $users = User::with('emailmodifications', 'passwordresets',
            'registrationapplications.reviewer', 'donations', 'info', 'usersessions', 'userlogins')
            ->join('historical_user_sessions','users.id','=','historical_user_sessions.user_id')
            ->where('historical_user_sessions.created_at','>', Carbon::now()->subDay(1))
            ->where('users.no_logging',0)
            ->orderby('historical_user_sessions.mobile_count', 'desc')
            ->select('users.*')
            ->paginate(config('preference.records_per_part'))
            ->appends($request->only('page','name','name_type'));
        }

        if($request->name_type && $request->name_type=='application_essay_like'){
            $application_records = \App\Models\RegistrationApplication::with('user.info','reviewer','owner')
            ->essayLike($name)
            ->paginate(config('preference.records_per_part'))
            ->appends($request->only('page','name','name_type'));
        }

        if($request->name_type && $request->name_type=='application_record_id'){
            $application_records = \App\Models\RegistrationApplication::with('user.info','reviewer','owner')
            ->where('id',$name)
            ->paginate(config('preference.records_per_part'))
            ->appends($request->only('page','name','name_type'));
        }

        if($request->name_type && $request->name_type=='quote_like' && $request->name){
            $quotes = \App\Models\Quote::with('author','reviewer','admin_reviews.author')
            ->bodyLike($name)
            ->paginate(config('preference.records_per_part'))
            ->appends($request->only('page','name','name_type'));
        }

        if($request->name_type && $request->name_type=='newest_long_post'){
            $posts = \App\Models\Post::with('simpleThread')
            ->where('type','post')
            ->where('len','=',3)
            ->latest()
            ->paginate(config('preference.records_per_part'))
            ->appends($request->only('page','name','name_type'));
        }
        return response()->success([
            'users' => UserResource::collection($users),
            'name' => $name,
            'email_modification_records' =>
                HistoricalEmailModificationResource::collection($email_modification_records),
            'donation_records' =>
                DonationRecordResource::collection($donation_records),
            'application_records' =>
                RegistrationApplicationResource::collection($application_records),
            'black_list_emails' =>
                FirewallEmail::collection($black_list_emails),
            'password_reset_records' =>
                HistoricalPasswordResetResource::collection($password_reset_records),
            'quotes' => QuoteResource::collection($quotes),
            'user_logins' => UserLoginResource::collection($user_logins),
            'posts' => PostBriefResource::collection($posts),   // FIXME:不完全确定前端需要多少,先用brief吧,不行再改
        ])
    }

}
