<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Quote;
use Auth;
use Carbon;
use CacheUser;
use Cache;

class QuoteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->only('create', 'store', 'mine');
        $this->middleware('admin')->only('review_index', 'review');
    }

    public function store(Request $request)
    {
        $last_quote = Quote::where('user_id', Auth::id())->orderBy('created_at','desc')->first();

        if($last_quote&&$last_quote->created_at>Carbon::now()->subDay(1)){
            return redirect()->back()->with('warning','一人一天只能提交一次题头');
        }

        $this->validate($request, [
            'body' => 'required|string|min:1|max:80|unique:quotes',
        ]);
        $data = [];
        $data['body'] = request('body');
        $data['user_id'] = auth()->id();
        $data['notsad'] = request('notsad')? 1:0;
        if (request('is_anonymous')){
            $this->validate($request, [
                'majia' => 'required|string|max:10',
            ]);
            $data['is_anonymous'] = true;
            $data['majia'] = request('majia');
        }

        if($last_quote&&strcmp($last_quote->body, $data['body']) === 0){
            return back()->with('warning','请不要重复提交题头！');

        }

        $quote = Quote::create($data);

        return redirect()->route('quote.show', $quote->id)->with('success','成功提交题头');
    }

    public function create(){
        return view('quotes.create');
    }
    public function show(Quote $quote){
        $rewards = \App\Models\Reward::with('author')
        ->withType('quote')
        ->withId($quote->id)
        ->orderBy('created_at','desc')
        ->take(10)
        ->get();
        $user = Auth::check()? CacheUser::Auser():'';
        $info = Auth::check()? CacheUser::Ainfo():'';
        return view('quotes.show', compact('user','info','quote','rewards'));
    }
    public function index(Request $request){
        $page = is_numeric($request->page)? $request->page:'1';
        $quotes = Cache::remember('quotes.P'.$page, 10, function () {
            return \App\Models\Quote::with('author')
            ->where('approved',1)
            ->orderBy('created_at','desc')
            ->paginate(config('preference.quotes_per_page'));
        });
        return view('quotes.index', compact('quotes'))->with('show_quote_tab','all');
    }
    public function mine(){
        $quotes = \App\Models\Quote::with('author')
        ->where('user_id',Auth::id())
        ->orderBy('created_at','desc')
        ->paginate(config('preference.quotes_per_page'));
        return view('quotes.index', compact('quotes'))->with('show_quote_tab','mine');
    }

    public function review_index(Request $request)
    {
        $state = $request->withReviewState?? 'all';
        $quotes = \App\Models\Quote::with('author','reviewer')
        ->withReviewState($state)
        ->orderBy('created_at', 'desc')
        ->paginate(config('preference.quotes_per_page'))
        ->appends(['withReviewState'=>$state]);

        return view('quotes.review_index', compact('quotes'))->with('quote_review_tab', $state);
    }

    public function review(Quote $quote, Request $request)
    {
        $attitude = $request->attitude;
        switch ($attitude):
            case "approve"://通过题头
            if(!$quote->approved){
                $quote->approved = 1;
                $quote->reviewed = 1;
                $quote->reviewer_id = Auth::id();
                $quote->save();
            }
            break;
            case "disapprove"://不通过题头(已经通过了的，不允许通过；或没有评价过的，不允许通过)
            if((!$quote->reviewed)||($quote->approved)){
                $quote->approved = 0;
                $quote->reviewed = 1;
                $quote->reviewer_id = Auth::id();
                $quote->save();
            }
            break;
            default:
            echo "应该奖励什么呢？一个bug呀……";
        endswitch;
        return [
            'success' => "成功审核题头",
            'quote' => $quote,
        ];
    }

}