<?php
namespace App\Http\Middleware;
use Closure;
use Auth;
use Cache;
use Carbon;
use App\Models\OnlineStatus;
use App\Models\HistoricalUsersActivity;
use CacheUser;
class LogUserActivity
{
    /**
    * Handle an incoming request.
    *
    * @param  \Illuminate\Http\Request  $request
    * @param  \Closure  $next
    * @return mixed
    */
    public function handle($request, Closure $next)
    {
        if(Auth::check()) {
            if(!Cache::has('usr-on-' . Auth::id())){//假如距离上次cache的时间已经超过了默认时间
                $online_status = OnlineStatus::updateOrCreate([
                    'user_id' => Auth::id(),
                ],[
                    'online_at' => Carbon::now(),
                ]);
                // $user_activity = HistoricalUsersActivity::create([
                //     'user_id' =>Auth::id(),
                //     'ip' => request()->ip(),
                // ]);
                $expiresAt = Carbon::now()->addMinutes(config('constants.online_interval'));
                Cache::put('usr-on-' . Auth::id(), true, $expiresAt);
            }
        }
        return $next($request);
    }
}
