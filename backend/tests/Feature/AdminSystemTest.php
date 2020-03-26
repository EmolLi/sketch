<?php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\UserInfo;
use App\Models\User;
use APP\Models\Checkin;
use App\Models\Thread;
use App\Models\Post;

use Carbon;
use Cache;

class AdminSystemTest extends TestCase
{
    public $userA;
    public $userB;
    public $admin;
    public $thread;
    public $chapterPost;
    public $chapterInfo;
    public $post;

    // TODO: add more test cases for manage and search record
    protected function setUp()
    {
        parent::setUp();
        $this->userA = factory('App\Models\User')->create();
        $this->userB = factory('App\Models\User')->create();
        $this->admin = factory('App\Models\User')->create(['role' => 'admin']);

        // create thread
        $this->thread = factory('App\Models\Thread')->create([
            'channel_id' => 1,
            'user_id' => $this->userA->id,
            'is_bianyuan' => false,
        ]);
        $this->chapterPost = factory('App\Models\Post')->create([
            'thread_id' => $this->thread->id,
            'user_id' => $this->thread->user_id,
            'type' => 'chapter',
        ]);
        $this->chapterInfo = factory('App\Models\PostInfo')->create([
            'post_id' => $this->chapterPost->id,
        ]);
        $this->post = factory('App\Models\Post')->create([
            'thread_id' => $this->thread->id,
            'user_id' => $this->userB->id,
            'type' => 'post',
        ]);
    }

    /** @test */
    public function anyone_can_view_public_adminrecords(){
        $this->get('api/administration_records')
            ->assertStatus(200)
            ->assertJsonStructure([
                "code",
                "data" => [
                    "record" => [
                        "*" => [
                            "type",
                            "id",
                            "attributes" => [
                                "user_id",
                                "task",
                                "reason",
                                "record",
                                "created_at",
                                "deleted_at",
                                "administratee_id",
                                "administratable_type",
                                "administratable_id",
                                "is_public",
                                "summary",
                                "report_post_id",
                            ]
                        ]
                    ],
                    "paginate" => [
                        "total",
                        "count",
                        "per_page",
                        "current_page",
                        "total_pages"
                    ]
                ]
            ]);
    }


    /** @test */
    public function fold_a_post(){
        $manageCommonData = [
            'content_id' => $this->post->id,
            'content_type' => 'post',
            'reason' => 'test',
            'administration_summary' => 'punish',
        ];

        $this->actingAs($this->userA, 'api');
        $manageFoldData = array_merge($manageCommonData, [ 'content_fold' => true ]);
        $this->json('POST', 'api/admin/management', $manageFoldData)
            ->assertStatus(403);   // not admin

        $userInfo = $this->userB->info()->first();
        $userBUnreadReminders = $userInfo->unread_reminders;
        $userBAdminReminders = $userInfo->administration_reminders;


        $this->actingAs($this->admin, 'api');
        $manageFoldData = array_merge($manageCommonData, [ 'content_fold' => true ]);
        $response = $this->json('POST', 'api/admin/management', $manageFoldData)
                ->assertStatus(200);
        $attributes =  $response->decodeResponseJson()['data']['attributes'];
        $this->assertEquals($this->admin->id, $attributes['user_id']);
        $this->assertEquals('折叠|', $attributes['task']);
        $this->assertEquals($this->userB->id, $attributes['administratee_id']);
        $this->assertEquals('post', $attributes['administratable_type']);
        $this->assertEquals($this->post->id, $attributes['is_public']);
        $this->assertEquals(true, $attributes['administratee_id']);
        $this->assertEquals(0, $attributes['report_post_id']);

        // post get folded
        $post = Post::find($this->post->id);
        $this->assertEquals(1, $post->fold_state);
        // userB should be notified;
        $userInfo = UserInfo::find($this->userB->id);
        $this->assertEquals($userBAdminReminders + 1,
            $userInfo->administration_reminders);
        $this->assertEquals($userBUnreadReminders + 1,
            $userInfo->unread_reminders);

        // now fold the post again, as we has already fold the post, we should get an error
        // $manageFoldData = array_merge($manageCommonData, [ 'content_fold' => true ]));
        $request = $this->json('POST', 'api/admin/management', $manageFoldData)
                ->assertStatus(420);    // '没有可实施的操作'

        // now unfold the post
        $manageUnfoldData = array_merge($manageCommonData, [ 'content_unfold' => true ]);
        $request = $this->json('POST', 'api/admin/management', $manageUnfoldData)
                ->assertStatus(200);
        // post get unfolded
        $post = Post::find($this->post->id);
        $this->assertEquals(0, $post->fold_state);
    }

    /** @test */
    public function owner_and_admin_can_view_useradminrecord(){
        $this->actingAs($this->userA, 'api');
        $this->get('api/user/'.$this->userB->id.'/administration_records')
            ->assertStatus(403);    // 只有管理可以看别人的管理记录

        $this->actingAs($this->userB, 'api');
        $this->get('api/user/'.$this->userB->id.'/administration_records')
            ->assertStatus(200);

        $this->actingAs($this->admin, 'api');
        $this->get('api/user/'.$this->userB->id.'/administration_records')
            ->assertStatus(200)
            ->assertJsonStructure([
                "code",
                "data" => [
                    "record" => [
                        "*" => [
                            "type",
                            "id",
                            "attributes" => [
                                "user_id",
                                "task",
                                "reason",
                                "record",
                                "created_at",
                                "deleted_at",
                                "administratee_id",
                                "administratable_type",
                                "administratable_id",
                                "is_public",
                                "summary",
                                "report_post_id",
                            ]
                        ]
                    ],
                    "paginate" => [
                        "total",
                        "count",
                        "per_page",
                        "current_page",
                        "total_pages"
                    ]
                ]
            ]);

    }

    /** @test */
    public function admin_search_record(){
        $userAEmail = $this->userA->email;
        $name_type = 'email';
        $query = 'api/admin/searchrecords?name_type=email&name='.$userAEmail;
        $this->actingAs($this->userA, 'api');
        $this->get($query)
            ->assertStatus(403);

        $this->actingAs($this->admin, 'api');
        $this->get($query)
            ->assertStatus(200)
            ->assertJsonStructure([
                "code",
                "data" => [
                    "name",
                    "users" => [
                        "data" => [
                            "*" => [
                                "type",
                                "id",
                                "attributes" => [
                                    "name",
                                    "acivated",
                                    "level",
                                    "title_id",
                                    "role",
                                    "quiz_level",
                                    "no_logging",
                                    "no_posting",
                                    "no_ads",
                                    "no_homework",
                                    "email",
                                    "created_at"
                                ],
                                "title",
                                "info" => [
                                    "type",
                                    "id",
                                    "attributes" => [
                                        "brief_intro",
                                        "salt",
                                        "fish",
                                        "ham",
                                        "follower_count",
                                        "following_count",
                                        "qiandao_max",
                                        "qiandao_continued",
                                        "qiandao_all",
                                        "qiandao_last",
                                        "qiandao_at",
                                        "register_at",
                                        "invitor_id",
                                        "token_limit",
                                        "donation_level",
                                        "qiandao_reward_limit"
                                    ]
                                ],
                                "intro",
                                "email_modifications",
                                "password_resets",
                                "registration_applications",
                                "donations",
                                "user_sessions",
                                "user_logins"
                            ]
                        ],
                        "paginate" => [
                            "total",
                            "count",
                            "per_page",
                            "current_page",
                            "total_pages"
                        ]
                    ],
                    "email_modification_records" => [
                        "data",
                        "paginate" => [
                            "total",
                            "count",
                            "per_page",
                            "current_page",
                            "total_pages"
                        ]
                    ],
                    "donation_records" => [
                        "data",
                        "paginate" => [
                            "total",
                            "count",
                            "per_page",
                            "current_page",
                            "total_pages"
                        ]
                    ],
                    "application_records" => [
                        "data",
                        "paginate" => [
                            "total",
                            "count",
                            "per_page",
                            "current_page",
                            "total_pages"
                        ]
                    ],
                    "black_list_emails" => [
                        "data",
                        "paginate" => [
                            "total",
                            "count",
                            "per_page",
                            "current_page",
                            "total_pages"
                        ]
                    ],
                ]]);
    }


}
