<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMessage;
use App\Models\User;
use App\Models\Message;
use App\Models\PublicNotice;
use App\Http\Resources\MessageResource;
use App\Http\Resources\MessageBodyResource;
use App\Http\Resources\PaginateResource;
use App\Http\Resources\PublicNoticeResource;
use CacheUser;
use ConstantObjects;
use App\Sosadfun\Traits\MessageObjectTraits;
use App\Http\Resources\AdministrationResource;
use App\Sosadfun\Traits\AdministrationTraits;

class MessageController extends Controller
{
    use MessageObjectTraits;
    use AdministrationTraits;

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function store(StoreMessage $form)
    {
        $message = $form->userSend();
        if(!$message){abort(495);}
        $message->load('message_body','poster','receiver');
        return response()->success([
            'message' => new MessageResource($message),
        ]);
    }

    public function groupmessage(StoreMessage $form)
    {
        $messages = $form->adminSend();
        if(!$messages){abort(495);}
        $messages->load('poster','receiver')->except('seen');
        $message = $messages[0];
        $message_body = $message->message_body;
        return response()->success([
            'messages' => MessageResource::collection($messages),
            'message_body' => new MessageBodyResource($message_body),
        ]);
    }

    public function publicnotice(StoreMessage $form)
    {
        if(!auth('api')->user()->isAdmin()){abort(403,'管理员才可以发公共消息');}
        $public_notice = $form->generatePublicNotice();
        if(!$public_notice){abort(495);}
        $this->refreshPulicNotices();
        $public_notice->load('author');
        return response()->success([
            'public_notice' => new PublicNoticeResource($public_notice),
        ]);
    }

    public function publicnotice_index()
    {
        $info = CacheUser::AInfo();
        $info->clear_column('public_notice_id');
        $public_notices = $this->findAllPulicNotices();

        return response()->success([
            'public_notices' => PublicNoticeResource::collection($public_notices),
        ]);
    }

    public function index($id, Request $request)
    {
        $user = CacheUser::user($id);
        if(!$user){abort(404);}

        if(auth('api')->id()!=$user->id&&!auth('api')->user()->isAdmin()){abort(403);}
        //访问的信箱需为登录用户的信箱或登录用户为管理员

        $chatWith = $request->chatWith ?? 0;
        $query = Message::with('poster.title', 'receiver.title', 'message_body');

        switch ($request->withStyle) {
            case 'sendbox': $query = $query->withPoster($user->id);
            break;
            case 'dialogue': $query = $query->withDialogue($user->id, $chatWith);
            break;
            default: $query = $query->withReceiver($user->id)->withRead($request->read);
            break;
        }
        $messages = $query->ordered($request->ordered)
        ->paginate(config('constants.messages_per_page'));
        if((request()->withStyle==='sendbox'
            || request()->withStyle==='dialogue')
            && (!auth('api')->user()->isAdmin())){
            $messages->except('seen');
        }
        return response()->success([
            'style' => $request->withStyle,
            'messages' => MessageResource::collection($messages),
            'paginate' => new PaginateResource($messages),
        ]);
    }

    public function user_administration_records($id, Request $request)
    {
        $user = auth('api')->user();
        if ($user->id != $id && !$user->isAdmin()) {
            abort(403, '只有管理可以看别人的管理记录');
        }
        $page = is_numeric($request->page)? $request->page:'1';
        if ($user->id == $id) {
            CacheUser::Ainfo()->clear_column('administration_reminders');
        }
        // 暂时让用户看自己的private admin record吧
        $records = $this->findAdminRecords($id, $page, "include_private", config('preference.index_per_page'));
        return response()->success([
            'record' => AdministrationResource::collection($records),
            'paginate' => new PaginateResource($records)
        ]);
    }
}
