<?php

use App\Http\Controllers\Api\VipUserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Store\AdminDecorationController;
use App\Http\Controllers\Admin\Store\AdminSpecialUIDsController;
use App\Http\Controllers\Admin\AdminDiamondController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\HobbiesController;
use App\Http\Controllers\Admin\AdminPhotoAlbumController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminGiftController;
use App\Http\Controllers\Admin\Rooms\AdminEmojisController;
use App\Http\Controllers\Admin\AdminLevelsController;
use App\Http\Controllers\Admin\Rooms\AdminRoomBackgrounds;
use App\Http\Controllers\Admin\AdminSupportChatController;
use App\Http\Controllers\Admin\AdminBannerController;
use App\Http\Controllers\Admin\Agents\AdminChargeAgentController;
use App\Http\Controllers\Admin\AdminVideoGiftController;
use App\Http\Controllers\Admin\AdminVideoGiftGenereController;
use App\Http\Controllers\Admin\AdminVipUserController;
use App\Http\Controllers\Admin\HostingAgency\AdminHostingAgencyContoller;
use App\Http\Controllers\Admin\HostingAgency\TargetsController;
use App\Http\Controllers\Admin\HostingAgency\VideoTargetsController;
use App\Http\Controllers\Admin\CategoryGiftsController;


use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\OldAuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DecorationController;
use App\Http\Controllers\Api\SpecialUIDsController;
use App\Http\Controllers\Api\DiamondController;
use App\Http\Controllers\Api\FollowAndFriendController;
use App\Http\Controllers\Api\GiftController;
use App\Http\Controllers\Api\Rooms\RoomController;
use App\Http\Controllers\Api\Rooms\RoomOwnerController;
use App\Http\Controllers\Api\Rooms\InsideRoomController;
use App\Http\Controllers\Api\GoldController;
use App\Http\Controllers\Api\Chat\ChatController;
use App\Http\Controllers\Api\Chat\SupportChatController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\Agents\ChargeAgentController;
use App\Http\Controllers\Api\Groups\GroupController;
use App\Http\Controllers\Api\Rooms\Video\VideoMembersController;
use App\Http\Controllers\Api\Rooms\Video\VideoRoomController;
use App\Http\Controllers\Api\VideoGiftController;
use App\Http\Controllers\Api\HostingAgency\HostingMembersController;
use App\Http\Controllers\Api\HostingAgency\HostingAgencyContoller;
use App\Http\Controllers\Api\Reels\ReelController;
use App\Http\Controllers\Api\SpinningWheelController;

use App\Http\Controllers\Api\Games\HotGames\GameHotController;
use App\Http\Controllers\Api\Games\leadercc\leaderccController;
use App\Http\Controllers\Api\Games\Rolling\GameController;


/***********************************************************************************************************************************************************/

/*********************** Auth Routes ******************************/


Route::get('/un-authorized', function () {
    return response(['message' => 'Unauthorized!'], 401);
})->name('login');

Route::post('/auth/join', [OldAuthController::class, 'auth'])->name('old-register')->middleware('guest');

Route::group(['prefix' => '/auth'], function () {
    Route::controller(AuthController::class)->group(function () {

        //UnAuthenticated ROUTES
        Route::post('/register', [AuthController::class, 'store'])->name('register')->middleware('guest');
        Route::post('/login', [AuthController::class, 'login'])->name('user-login')->middleware('guest');


        // Route::post('/register', 'store')->name('register')->middleware('guest');

        // Route::post('/login', 'login')->name('user-login')->middleware('guest');

        //Authenticated ROUTES
        Route::group(['middleware' => ['auth', 'ban','throttle:api']], function () {

            Route::post('/me', 'me')->middleware('auth')->name('auth.me');

            Route::post('/logout', 'logout')->middleware('auth')->name('auth.logout');

            Route::post('/change-password', 'changePassword')->middleware('auth')->name('auth.change-password');
        });
    });
});



/*********************** User Routes ******************************/
Route::group(['prefix' => '/user'], function () {

    Route::controller(UserController::class)->group(function () {
        Route::group(['middleware' => ['auth', 'ban','throttle:api']], function () {

            Route::get('/find-user', 'findUserByID')->name('users.find-user');

            Route::get('/{id}', 'show')->name('users.show');

            Route::post('/update', 'update')->name('users.update');

            Route::post('/add-photo-album', 'addPhotoToAlbum')->name('users.add-photo-album');

            Route::post('/delete-photo-album/{id}', 'deletePhotoFromAlbum')->name('users.delete-photo-album');

            Route::post('/add-hobby', 'addHobby')->name('users.add-hobby');

            Route::post('/edit-hobby/{id}', 'editHobby')->name('users.edit-hobby');

            Route::post('/delete-hobby/{id}', 'deleteHobby')->name('users.delete-hobby');

                        Route::post('/convert-diamond-to-gold', [UserController::class, 'convertDiamondToGold']);


            // Route::post('/getUserInfo', [GameHotController::class, 'getUserInfo']);

            // Route::post('/updateBalance', [GameHotController::class, 'updateBalance']);

            Route::get('/join-game', [GameController::class, 'joinGame']);
            Route::post('/place-bet', [GameController::class, 'placeBet']);
            Route::post('/end-round', [GameController::class, 'endRound']);
            Route::get('/roulette/random-number', [GameController::class, 'getRandomNumber']);

            Route::post('/game/getUserInfo', [leaderccController::class, 'getUserInfo']);
            Route::post('/game/updateCurrency', [leaderccController::class, 'updateCurrency']);

        });
    });
    // Route::post('/getUserInfo', [GameController::class, 'getUserInfo']);

});


/********************* Decorations Routes **************************/
Route::group(['prefix' => '/decorations'], function () {
    Route::controller(DecorationController::class)->group(function () {
        Route::group(['middleware' => ['auth', 'ban','throttle:api']], function () {

            Route::get('/', 'getDecorationsByType')->name('decorations.get-by-type');

            Route::get('/all', 'index')->name('decorations.all');

            Route::get('/{id}', 'show')->name('decorations.show');

            Route::get('/user/{id}', 'getUserDecorations')->name('decorations.get-user-decorations');

            Route::post('/purchase/{id}', 'purchase')->name('decorations.purchase');

            Route::post('/set-default/{id}', 'setAsDefault')->name('decorations.set-as-default');

            Route::post('/unset-default/{id}', 'unsetDefault')->name('decorations.unset-as-default');
        });
    });
});

/*********************** Specia UIDs Routes ******************************/
Route::group(['prefix' => '/special-uids'], function () {
    Route::controller(SpecialUIDsController::class)->group(function () {
        Route::group(['middleware' => ['auth', 'ban','throttle:api']], function () {

            Route::get('/all', 'index')->name('special-uids.all');

            Route::get('/allSelects', 'indexSelects')->name('special-uids-selects.all');

            Route::post('/storeSelects', 'storeUidFromSelects')->name('special-uids.select');

            Route::post('/purchase/{id}', 'update')->name('special-uids.purchase');
        });
    });
});

/*********************** Diamond Routes ******************************/
Route::group(['prefix' => '/diamond'], function () {
    Route::controller(DiamondController::class)->group(function () {
        Route::group(['middleware' => ['auth', 'ban','throttle:api']], function () {

            Route::get('/all', 'index')->name('diamond.all');

            Route::get('/{id}', 'show')->name('diamond.show-user');

            Route::post('/purchase/{id}', 'purchase')->name('diamond.purchase');
        });
    });
});


/********************* Follow and Friends Routes **************************/
Route::group(['prefix' => '/follows-friends'], function () {
    Route::controller(FollowAndFriendController::class)->group(function () {
        Route::group(['middleware' => ['auth', 'ban','throttle:api']], function () {

            Route::post('/follow/{id}', 'follow')->name('follows-friends.follow');

            Route::post('/un-follow/{id}', 'unfollow')->name('follows-friends.unfollow');

            Route::get('/followers', 'getFollowers')->name('follows-friends.followers');

            Route::get('/followings', 'getFollowings')->name('follows-friends.followings');

            Route::get('/friends', 'getFriends')->name('follows-friends.friends');
        });
    });
});

/********************* Gifts Routes **************************/
Route::group(['prefix' => '/gifts'], function () {
    Route::controller(GiftController::class)->group(function () {
        Route::group(['middleware' => ['auth', 'ban','throttle:api']], function () {
            Route::post('/store', 'store');

            Route::get('/all', 'index')->name('gifts.index');

            Route::get('/user/{id}', 'getUserGifts')->name('gifts.get-user-gifts');

            Route::post('/daily', [GiftController::class, 'dailyGift']);




        });
    });
});

/********************* Room Routes **************************/
Route::group(['prefix' => '/room'], function () {
    Route::controller(RoomController::class)->group(function () {
        Route::group(['middleware' => ['auth', 'ban','throttle:api']], function () {

            Route::get('/all', 'index')->name('room.all');

            Route::get('/news', 'indexNew')->name('room.news');

            Route::post('/store', 'store')->name('room.store');

            Route::post('/update/{id}', 'update')->name('room.update');

            Route::post('/join/{id}', 'join')->name('room.join')->middleware('throttle:1,0.01667');

            Route::post('/leave', 'leave')->name('room.leave');

            Route::post('/delete/{id}', 'deleteRoom')->name('room.delete');

            Route::get('/all-contrs', 'getAllRoomsContributions')->name('room.store');
        });
    });
});

/********************* user vip Routes **************************/

Route::group(['prefix' => '/user-vip'], function () {

    Route::controller(AdminDiamondController::class)->group(function () {
        Route::group(['middleware' => ['auth:api']], function () {
          Route::post('/store-vip', [VipUserController::class, 'store'])->name('store-vip');

        });
    });
});

/********************* Room Owner Routes **************************/
Route::group(['prefix' => '/room-owner'], function () {
    Route::controller(RoomOwnerController::class)->group(function () {
        Route::group(['middleware' => ['auth', 'ban','throttle:api']], function () {

            Route::post('/change-chair-status/{id}', 'changeChairStatus')->name('room-owner.change-chair-status');

            Route::post('/change-all-chairs-status/{id}', 'changeAllChairsStatus')->name('room-owner.change-all-chairs-status');

            Route::post('/block-user/{id}', 'kickUser')->name('room-owner.block-user');

            Route::post('/unblock-user/{id}', 'unBlockUser')->name('room-owner.unblock-user');

            Route::post('/kick-user-of-chair/{id}', 'kickUserOfChair')->name('room-owner.kick-user');

            Route::post('/invite-user-to-mic/{id}', 'inviteUserToMic')->name('room-owner.invite-user-to-mic'); //Room ID

            Route::post('/accept-decline-mic-invite/{id}', 'acceptOrDeclineMicInvite')->name('room-owner.accept-decline-mic-invite'); //Invite ID

            Route::post('/assign-moderator/{id}', 'makeModerator')->name('room-owner.make-moderator'); //Room ID

            Route::post('/remove-moderator/{id}', 'removeModerator')->name('room-owner.remove-moderator'); //Moderator ID

            Route::post('/change-chair-carizma/{id}', 'changeCarizmaCounterStatus')->name('room-owner.change-chair-carizma'); //Chair ID

            Route::post('/reset-carizma/{id}', 'resetCarizmaCounter')->name('room-owner.reset-carizma'); //Chair ID

            Route::post('/change-chairs-carizma/{id}', 'changeCarizmaCounterStatusForAllChairs')->name('room-owner.change-chairs-carizma'); //Room ID

            Route::post('/reset-carizma-for-all/{id}', 'resetCarizmaCounterForAllChairs')->name('room-owner.reset-carizma-for-all'); //Room ID

        });
    });
});

/********************* INSIDE Room Routes **************************/
Route::group(['prefix' => '/inside-room'], function () {

    Route::controller(InsideRoomController::class)->group(function () {
        Route::group(['middleware' => ['auth', 'ban','throttle:api']], function () {


            Route::post('/up-to-chair/{id}', 'upToChair')->name('room-inside.up-to-chair')->middleware('throttle:1,0.05');

            Route::post('/leave-chair', 'leaveChair')->name('room-inside.leave-chair');

            Route::post('/send-chat-message/{id}', 'sendChatMessage')->name('room-inside.send-chat-message');

            Route::post('/mute-chair/{id}', 'muteChairByUser')->name('room-inside.mute-chair')->middleware('throttle:1,0.01667');

            Route::get('/get-members/{id}', 'getRoomMembers')->name('room-inside.get-members');

            Route::get('/get-moderators/{id}', 'getRoomModerators')->name('room-inside.get-moderators');

            Route::get('/get-info/{id}', 'getRoomInfo')->name('room-inside.get-info');

            Route::get('/get-blocks/{id}', 'getRoomBlocks')->name('room-inside.get-blocks');

            Route::post('/send-gift/{id}', 'sendGift')->name('room-inside.send-gift')->middleware('throttle:1,0.016667'); //Room ID

            Route::post('/send-gift-to-all/{id}', 'sendGiftToAll')->name('room-inside.send-gift-to-all')->middleware('throttle:1,0.016667');; //Room ID

            Route::get('/get-contributions/{id}', 'getRoomContributions')->name('room-inside.get-contributions');

            Route::get('/get-emojis', 'getEmojis')->name('room-inside.get-emojis');

            Route::post('/send-emoji/{cid}/{eid}', 'sendEmoji')->name('room-inside.send-emoji');

            Route::get('/chair-carizma-details/{id}', 'getChairCarizmaDetails')->name('room-inside.get-chair-carizma-details');

            Route::get('/get-rooms-backgrounds', 'getAllRoomBackgrounds')->name('room-inside.get-rooms-backgrounds');

            Route::post('/gift-multi/{id}', [InsideRoomController::class, 'sendGiftToMultiPerson']);


        });
    });
});

/********************* Gold Routes **************************/
Route::group(['prefix' => '/gold'], function () {
    Route::controller(GoldController::class)->group(function () {
        Route::group(['middleware' => ['auth', 'ban','throttle:api']], function () {

            Route::get('/balance', 'getGoldBalance')->name('gold.get-balance');

            Route::post('/convert-gold', 'convertGoldToDiamond')->name('gold.convert');

        });
    });
});

/********************* Chat Routes **************************/
Route::group(['prefix' => '/chat'], function () {
    Route::controller(ChatController::class)->group(function () {
        Route::group(['middleware' => ['auth', 'ban','throttle:api']], function () {

            Route::get('/all', 'getAllConversations')->name('chat.all');

            Route::get('/support', 'getSupportConversations')->name('chat.support');

            Route::get('/show/{id}', 'getConversation')->name('chat.show'); //User ID

            Route::post('/send-message/{id}', 'sendMessage')->name('chat.send-message'); //Reciever ID

            Route::post('/remove-for-me/{id}', 'removeForMe')->name('chat.remove-for-me'); //Message ID

            Route::post('/remove-for-all/{id}', 'removeForAll')->name('chat.remove-for-all'); //Message ID

            Route::post('/delete-conversation/{id}', 'deleteConversation')->name('chat.delete-conversation'); //Conversation ID

            Route::post('/block-user-messages/{id}', 'blockUserMessages')->name('chat.block-user-messages'); //user to be blocked id

        });
    });
});


/********************* Support Chat Routes **************************/
Route::group(['prefix' => '/support-chat'], function () {
    Route::controller(SupportChatController::class)->group(function () {
        Route::group(['middleware' => ['auth', 'ban','throttle:api']], function () {

            Route::get('/get-support-conversation', 'getConversation')->name('chat.show');

            Route::post('/send-message', 'sendMessage')->name('chat.send-message');

        });
    });
});

/********************* banners Routes **************************/
Route::group(['prefix' => '/banners'], function () {
    Route::controller(BannerController::class)->group(function () {
        Route::group(['middleware' => ['auth', 'ban','throttle:api']], function () {

            Route::get('/all', 'index')->name('banners.index');

        });
    });
});

/********************* banners Routes **************************/
Route::group(['prefix' => '/charge-agents'], function () {
    Route::controller(ChargeAgentController::class)->group(function () {
        Route::group(['middleware' => ['auth', 'ban','throttle:api']], function () {
            Route::get('/all', 'index')->name('charge-agents.index');

            Route::post('/transfer/{id}', 'transfer')->name('charge-agents.transfer'); //User to transfer to ID

            Route::get('/get-admin-history', 'getAdminHistory')->name('charge-agents.get-admin-history');

            Route::get('/get-users-history', 'getUsersHistory')->name('charge-agents.get-users-history');

            Route::get('/get-user-agent', 'getUserAgent')->name('charge-agents.get-users-history');

        });
    });
});

/********************* Groups Routes **************************/
Route::group(['prefix' => '/groups'], function () {
    Route::controller(GroupController::class)->group(function () {
        Route::group(['middleware' => ['auth', 'ban','throttle:api']], function () {

            Route::post('/store', 'store')->name('groups.store');

            Route::post('/update/{id}', 'update')->name('groups.update');

            Route::delete('/delete/{id}', 'destroy')->name('groups.destroy');

            Route::get('/all', 'index')->name('groups.index');

            Route::get('/show/{id}', 'show')->name('groups.show');

            Route::get('/members/{id}', 'getGroupMembers')->name('groups.index');

            Route::get('/search', 'search')->name('groups.search');

            Route::post('/kick/{id}', 'kick')->name('groups.kick');

            Route::post('/join/{id}', 'join')->name('groups.join');

            Route::post('/leave', 'leave')->name('groups.leave');
        });
    });
});

/********************* Video Room Routes **************************/
Route::group(['prefix' => '/video-rooms'], function () {
    Route::controller(VideoRoomController::class)->group(function () {
        Route::group(['middleware' => ['auth', 'ban','throttle:api']], function () {

            Route::post('/store', 'store')->name('video-rooms.store');

            Route::delete('/delete', 'destroy')->name('video-rooms.destroy');

            Route::get('/all', 'index')->name('video-rooms.index');

            Route::get('/show/{id}', 'show')->name('video-rooms.show');

            Route::get('/next/{id}', 'next')->name('video-rooms.next');

            Route::get('/previous/{id}', 'previous')->name('video-rooms.previous');

        });
    });
});

/******************* Video Room Members Routes ********************/
Route::group(['prefix' => '/video-rooms-members'], function () {
    Route::controller(VideoMembersController::class)->group(function () {
        Route::group(['middleware' => ['auth', 'ban','throttle:api']], function () {

            Route::post('/join/{id}', 'join')->name('video-rooms-members.store');

            Route::post('/leave', 'leave')->name('video-rooms-members.destroy');

        });
    });
});

/******************* Video Room gifts Routes ********************/
Route::group(['prefix' => '/video-room-gifts'], function () {
    Route::controller(VideoGiftController::class)->group(function () {
        Route::group(['middleware' => ['auth', 'ban','throttle:api']], function () {

            Route::get('/all', 'index')->name('video-room-gifts.index');

            Route::post('/send/{id}', 'sendGift')->name('video-room-gifts.send');

        });
    });
});

/********************* Hosting Agency Routes **************************/
Route::group(['prefix' => '/hosting-agency'], function () {
    Route::controller(HostingAgencyContoller::class)->group(function () {
        Route::group(['middleware' => ['auth', 'ban','throttle:api']], function () {

            Route::get('/join-requests/{id}', 'getJoinRequests')->name('groups.join-requests');

            Route::get('/my-agency', 'myAgency')->name('groups.my-agency');

            Route::get('/members/{id}', 'members')->name('groups.members-diamond');

            Route::get('/members-diamond/{id}', 'membersDiamondsPerformance')->name('groups.members-diamond');

            Route::get('/members-video-diamond/{id}', 'membersVideoDiamondsPerformance')->name('groups.members-video-diamond');

            Route::get('/members-hours/{id}', 'membersHoursPerformance')->name('groups.members-hours');

            Route::get('/members-video-hours/{id}', 'membersVideoHoursPerformance')->name('groups.members-video-hours');

            Route::get('/members-targets/{id}', 'membersTargets')->name('groups.members-targets');

            Route::get('/members-video-targets/{id}', 'membersVideoTargets')->name('groups.members-video-targets');

            Route::get('/owner-salary/{id}', 'getOwnerSalary')->name('groups.owner-salary');

            Route::post('/update/{id}', 'update')->name('groups.update');

            Route::delete('/kick/{id}', 'kick')->name('groups.kick'); //Member ID

            Route::post('/accept-or-decline/{id}', 'acceptOrDeclineMember')->name('groups.accept-or-decline');

            Route::post('/store', 'store')->name('groups.store');

        });

    });

    Route::controller(HostingMembersController::class)->group(function () {
        Route::group(['middleware' => ['auth', 'ban','throttle:api']], function () {

            Route::get('/search', 'search')->name('groups.search');

            Route::delete('/leave', 'leave')->name('groups.leave');

            Route::post('/join/{id}', 'joinRequest')->name('groups.join');

        });

    });

});

/******************* Reels Routes ********************/
Route::group(['prefix' => '/reels'], function () {
    Route::controller(ReelController::class)->group(function () {
        Route::group(['middleware' => ['auth', 'ban','throttle:api']], function () {

            Route::get('/user-reels/{id}', 'getUserReels')->name('reels.user-reels');

            Route::get('/all', 'index')->name('reels.index');

            Route::get('/likes/{id}', 'likes')->name('reels.likes');

            Route::get('/comments/{id}', 'comments')->name('reels.comments');

            Route::post('/store', 'store')->name('reels.store');

            Route::post('/update/{id}', 'update')->name('reels.update');

            Route::delete('/delete/{id}', 'destroy')->name('reels.delete');

            Route::post('/like/{id}', 'like')->name('reels.like');

            Route::post('/comment/{id}', 'comment')->name('reels.comment');

            Route::delete('/delete-comment/{id}', 'destroyComment')->name('reels.destroy-comment');

        });
    });
});

/******************* Spinnig Wheel Routes ********************/
Route::group(['prefix' => '/spinning-wheel'], function () {
    Route::controller(SpinningWheelController::class)->group(function () {
        Route::group(['middleware' => ['auth', 'ban','throttle:api']], function () {

            Route::get('/user-spinnings', 'getUserSpinnigs')->name('spinning-wheel.user-spinnings');

            Route::post('/play', 'store')->name('spinning-wheel.store');

            Route::put('/played/{sid}', 'played')->name('spinning-wheel.played');

        });
    });
});
/***********
 ********************** Mobile App Routes ENDS ***************************
 **********/

/**************************: )****************************************/
/*********************************************************************/
/***************************:(****************************************/
/*********************************************************************/

/***********
 ************************ Dashboard Routes ******************************
 **********/
/*********************** Auth Routes ******************************/
/* Super Admin */


Route::post('/super-admin/auth', [AdminAuthController::class, 'login'])->name('super-admin.login')->middleware('guest');
Route::post('/super-admin/me', [AdminAuthController::class, 'me'])->middleware('auth:admin')->name('admin.me');
Route::post('/super-admin/logout', [AdminAuthController::class, 'logout'])->middleware('auth:admin')->name('admin.logout');

Route::group(['prefix' => '/super-admin'], function () {

    /*********************** Decotation Routes ******************************/
    Route::group(['prefix' => '/decorations'], function () {

        Route::controller(AdminDecorationController::class)->group(function () {

            Route::group(['middleware' => ['auth:admin']], function () {
                Route::get('/all', 'index')->name('decoration.index');

                Route::get('/sort', 'getDecorationsByType')->name('decoration.get-decorations-by-type');

                Route::get('/{id}', 'show')->name('decoration.show');

                Route::post('/store', 'store')->name('decoration.strore');

                Route::post('/update/{id}', 'update')->name('decoration.update');

                Route::post('/delete/{id}', 'destroy')->name('decoration.delete');

                Route::post('/give-to-user/{id}', 'giveToUser')->name('give-to-user');

                Route::post('/remove-from-user/{id}', 'removeFromUser')->name('remove-from-user');

                Route::get('/get-user/{id}', 'getUserDecoration')->name('get-user-decoration');
            });
        });
    });



    /********************** Special UIDs Routes ***************************/
    Route::group(['prefix' => '/special-uids'], function () {

        Route::controller(AdminSpecialUIDsController::class)->group(function () {

            Route::group(['middleware' => ['auth:admin']], function () {
                Route::get('/all', 'index')->name('special-uids.index');

                Route::get('/{id}', 'show')->name('special-uids.show');

                Route::post('/store', 'store')->name('special-uids.strore');

                Route::post('/update/{id}', 'update')->name('special-uids.update');

                Route::post('/delete/{id}', 'destroy')->name('special-uids.delete');

                Route::post('/give-to-user/{id}', 'giveUidToUser')->name('special-uids.give-to-user');

                Route::post('/remove-from-user/{id}', 'removeUidFromUser')->name('special-uids.remove-from-user');
            });
        });
    });

    /********************** VIP ***************************/
    Route::group(['prefix' => '/vip'], function () {

        Route::controller(AdminVipUserController::class)->group(function () {
            Route::group(['middleware' => ['auth:admin']], function () {
              Route::get('/all', [AdminVipUserController::class, 'index'])->name('vipUsers.index');
              Route::post('/create-vip', [AdminVipUserController::class, 'store'])->name('vipUsers.store');
              Route::get('/{id}', [AdminVipUserController::class, 'show'])->name('vipUsers.show');
              Route::post('/update/{id}', [AdminVipUserController::class, 'update'])->name('vipUsers.update');
              Route::delete('/delete/{id}', [AdminVipUserController::class, 'destroy'])->name('vipUsers.destroy');
            });
        });
    });
    /********************** Diamond Routes ***************************/
    Route::group(['prefix' => '/diamond'], function () {

        Route::controller(AdminDiamondController::class)->group(function () {
            Route::group(['middleware' => ['auth:admin']], function () {
                Route::get('/all', 'index')->name('diamond.index');

                Route::get('/{id}', 'show')->name('diamond.show');

                Route::post('/store', 'store')->name('diamond.strore');

                Route::post('/update/{id}', 'update')->name('diamond.update');

                Route::post('/delete/{id}', 'destroy')->name('diamond.delete');

                Route::post('/give-to-user', 'chargeUser')->name('diamond.give-to-user');

                Route::post('/remove-from-user', 'rollbackUser')->name('diamond.remove-from-user');
            });
        });
    });

    /********************** Users Routes ***************************/
    Route::group(['prefix' => '/users'], function () {

        Route::controller(AdminUserController::class)->group(function () {

            Route::group(['middleware' => ['auth:admin']], function () {
                Route::get('/search', 'search')->name('user.search');

                Route::get('/all', 'index')->name('user.index');

                Route::get('/all-members', 'indexWithoutRelation')->name('user.index');


                Route::get('/{id}', 'show')->name('user.show');

                Route::post('/update/{id}', 'update')->name('user.update');

                Route::post('/delete/{id}', 'destroy')->name('user.delete');

                Route::post('/block/{id}', 'block')->name('user.block-user');

                Route::post('/unblock/{id}', 'unblock')->name('user.unblock-user');

                Route::post('/insert-supporter/{id}', 'insertSupporter')->name('user.insertSupporter');

                Route::post('/delete-supporter/{id}', 'deleteSupporter')->name('user.deleteSupporter');

                Route::post('/insert-superadmin/{id}', 'insertSuperAdmin')->name('user.insertsuperadmin');

                Route::post('/delete-superadmin/{id}', 'deleteSuperAdmin')->name('user.deletesuperadmin');

                Route::post('/insert-admin/{id}', 'insertAdmin')->name('user.insertadmin');

                Route::post('/delete-admin/{id}', 'deleteAdmin')->name('user.deleteadmin');
            });
        });
    });

    /********************** Hobbies Routes ***************************/
    Route::group(['prefix' => '/hobbies'], function () {

        Route::controller(HobbiesController::class)->group(function () {

            Route::group(['middleware' => ['auth:admin']], function () {
                Route::get('/all', 'index')->name('hobbies.index');

                Route::get('/user/{id}', 'show')->name('hobbies-user.show');

                Route::post('/update/{id}', 'update')->name('hobbies.update');

                Route::post('/delete/{id}', 'destroy')->name('hobbies.delete');
            });
        });
    });


    /********************** Photo Album Routes ***************************/
    Route::group(['prefix' => '/albums'], function () {

        Route::controller(AdminPhotoAlbumController::class)->group(function () {

            Route::group(['middleware' => ['auth:admin']], function () {
                Route::get('/all', 'index')->name('Photo-Album.index');

                Route::get('/user/{id}', 'show')->name('Photo-Album-user.show');

                Route::post('/delete/{id}', 'destroy')->name('Photo-Album.delete');
            });
        });
    });


    /********************** Category Gifts Routes ***************************/


    Route::group(['prefix' => '/categorygifts'], function () {

        Route::get('/all', [CategoryGiftsController::class, 'index']);

        Route::group(['middleware' => ['auth:admin']], function () {

        // Route::get('/all', [CategoryGiftsController::class, 'index']);

        Route::post('/store', [CategoryGiftsController::class, 'store']);

        Route::get('show/{id}', [CategoryGiftsController::class, 'show']);

        Route::post('/update/{id}', [CategoryGiftsController::class, 'update']);

        Route::post('/delete/{id}', [CategoryGiftsController::class, 'destroy'])->name('admin-categorygifts.delete');

    });
});
    /********************** Gifts Routes ***************************/
    Route::group(['prefix' => '/gifts'], function () {

        Route::controller(AdminGiftController::class)->group(function () {

            Route::group(['middleware' => ['auth:admin']], function () {

                Route::get('/all', 'index')->name('admin-gifts.index');

                Route::get('show/{id}', 'show')->name('admin-gifts.show');

                Route::post('/store', 'store')->name('admin-gifts.store');

                Route::post('/update/{id}', 'update')->name('admin-gifts.update');

                Route::post('/delete/{id}', 'destroy')->name('gifts.delete');


                Route::get('/dailygifts', [GiftController::class, 'indexDailyGift']);

                Route::get('/dailygifts/{id}', [GiftController::class, 'showDailyGift']);

                Route::post('/dailygifts/store', [GiftController::class, 'storeDailyGift']);

                Route::post('/dailygifts/update/{id}', [GiftController::class, 'updateDailyGift']);

                Route::delete('/dailygifts/delet/{id}', [GiftController::class, 'destroyDailyGift']);
            });
        });
    });

    /********************** Emojis Routes ***************************/
    Route::group(['prefix' => '/emojis'], function () {

        Route::controller(AdminEmojisController::class)->group(function () {
            Route::group(['middleware' => ['auth:admin']], function () {
                Route::get('/all', 'index')->name('emojis.index');

                Route::get('/{id}', 'show')->name('emojis.show');

                Route::post('/store', 'store')->name('emojis.store');

                Route::post('/update/{id}', 'update')->name('emojis.update');

                Route::post('/delete/{id}', 'destroy')->name('emojis.delete');
            });
        });
    });

    /********************** Levels Routes ***************************/
    Route::group(['prefix' => '/levels'], function () {

        Route::controller(AdminLevelsController::class)->group(function () {
            Route::group(['middleware' => ['auth:admin']], function () {

                Route::get('/all', 'index')->name('levels.index');

                Route::get('/{id}', 'show')->name('levels.show');

                Route::post('/update/{id}', 'update')->name('levels.update');

                Route::post('/change-user-level', 'changeUserLevel')->name('levels.change-user-level');

            });
        });
    });

    /****************** Room Backgorunds Routes ***********************/
    Route::group(['prefix' => '/room-backgrounds'], function () {

        Route::controller(AdminRoomBackgrounds::class)->group(function () {
            Route::group(['middleware' => ['auth:admin']], function () {

                Route::get('/all', 'index')->name('room-backgrounds.index');

                Route::get('/all-rooms', 'indexRooms')->name('rooms.index');


                Route::get('/{id}', 'show')->name('room-backgrounds.show');

                Route::post('/store', 'store')->name('room-backgrounds.store');

                Route::post('/update/{id}', 'update')->name('room-backgrounds.update');

                Route::post('/update-room/{id}', 'updateRoom')->name('rooms.update');


                Route::post('/delete/{id}', 'destroy')->name('room-backgrounds.destroy');

            });
        });
    });


    /****************** Support Chat Routes ***********************/
    Route::group(['prefix' => '/support-chat'], function () {

        Route::controller(AdminSupportChatController::class)->group(function () {
            Route::group(['middleware' => ['auth:admin']], function () {

                Route::post('/send-message/{id}', 'sendMessage')->name('support-chat.send-message'); //User ID

                Route::get('/get-all-conversations', 'getAllConversations')->name('support-chat.get-all-conversations');

                Route::get('/get-conversation/{id}', 'getConversation')->name('support-chat.get-all-conversations'); //User ID

                Route::post('/delete-message/{id}', 'deleteMessage')->name('support-chat.delete-message'); //message ID

                Route::post('/delete-conversation/{id}', 'deleteConversation')->name('support-chat.delete-conversation'); //Conversation ID

            });
        });
    });


    /****************** Banners Routes ***********************/
    Route::group(['prefix' => '/banners'], function () {

        Route::controller(AdminBannerController::class)->group(function () {
            Route::group(['middleware' => ['auth:admin']], function () {

                Route::get('/all', 'index')->name('banners.index');

                Route::get('/active', 'activeBanners')->name('banners.active');

                Route::post('/store', 'store')->name('banners.store');

                Route::get('/show/{id}', 'show')->name('banners.show');

                Route::post('/update/{id}', 'update')->name('banners.update');

                Route::delete('/delete/{id}', 'destroy')->name('banners.destroy');

            });
        });
    });

    /****************** charge agents Routes ***********************/
    Route::group(['prefix' => '/charge-agents'], function () {

        Route::controller(AdminChargeAgentController::class)->group(function () {
            Route::group(['middleware' => ['auth:admin']], function () {

                Route::get('/all', 'index')->name('charge-agents.index');

                Route::get('/index', 'index')->name('charge-agents.index');

                Route::post('/store', 'store')->name('charge-agents.store');

                Route::get('/show/{id}', 'show')->name('charge-agents.show');

                Route::post('/update-balance/{id}', 'updateBalance')->name('charge-agents.update-balance');

                Route::delete('/delete/{id}', 'destroy')->name('charge-agents.destroy');

                Route::get('/get-admin-history/{id}', 'getAdminHistory')->name('charge-agents.get-admin-history');


                Route::get('/get-users-history/{id}', 'getUsersHistory')->name('charge-agents.get-users-history');


            });
        });
    });


        /************* video-gifts-generes Routes ******************/
        Route::group(['prefix' => '/video-gifts-generes'], function () {

            Route::controller(AdminVideoGiftGenereController::class)->group(function () {
                Route::group(['middleware' => ['auth:admin']], function () {

                    Route::get('/all', 'index')->name('video-gifts-generes.index');

                    Route::post('/store', 'store')->name('video-gifts-generes.store');

                    Route::get('/show/{id}', 'show')->name('video-gifts-generes.show');

                    Route::post('/update/{id}', 'update')->name('video-gifts-generes.update');

                    Route::delete('/delete/{id}', 'destroy')->name('video-gifts-generes.destroy');

                });
            });
        });


        /************* video-gifts Routes ******************/
        Route::group(['prefix' => '/video-gifts'], function () {

            Route::controller(AdminVideoGiftController::class)->group(function () {
                Route::group(['middleware' => ['auth:admin']], function () {

                    Route::get('/all', 'index')->name('video-gifts.index');

                    Route::get('/normal', 'normalGifts')->name('video-gifts.normal');

                    Route::post('/store', 'store')->name('video-gifts.store');

                    Route::get('/show/{id}', 'show')->name('video-gifts.show');

                    Route::post('/update/{id}', 'update')->name('video-gifts.update');

                    Route::delete('/delete/{id}', 'destroy')->name('video-gifts.destroy');

                });
            });
        });

    /**************** Hosting Agencies Targets Routes ****************/
    Route::group(['prefix' => '/hosting-agency-targets'], function () {

        Route::controller(TargetsController::class)->group(function () {
            Route::group(['middleware' => ['auth:admin']], function () {

                Route::get('/all', 'index')->name('hosting-agency-targets.index');

                Route::post('/store', 'store')->name('hosting-agency-targets.store');

                Route::get('/show/{id}', 'show')->name('hosting-agency-targets.show');

                Route::put('/update/{id}', 'update')->name('hosting-agency-targets.update');

            });
        });
    });

    /************** Hosting Agencies Video Targets Routes **************/
    Route::group(['prefix' => '/hosting-agency-video-targets'], function () {

        Route::controller(VideoTargetsController::class)->group(function () {
            Route::group(['middleware' => ['auth:admin']], function () {

                Route::get('/all', 'index')->name('hosting-agency-video-targets.index');

                Route::post('/store', 'store')->name('hosting-agency-video-targets.store');

                Route::get('/show/{id}', 'show')->name('hosting-agency-video-targets.show');

                Route::put('/update/{id}', 'update')->name('hosting-agency-video-targets.update');

            });
        });
    });

    /**************** Hosting Agencies Routes *****************/
    Route::group(['prefix' => '/hosting-agency'], function () {

        Route::controller(AdminHostingAgencyContoller::class)->group(function () {
            Route::group(['middleware' => ['auth:admin']], function () {

                Route::get('/all', 'index')->name('hosting-agency.index');

                Route::post('/store', 'store')->name('hosting-agency.store');

                Route::get('/show/{id}', 'show')->name('hosting-agency.show');

                Route::post('/update/{id}', 'update')->name('hosting-agency.update');

                Route::delete('/delete/{id}', 'destroy')->name('hosting-agency.delete');

            });
        });
    });


});

// use App\Http\Controllers\TestController;
// Route::post('/test', [TestController::class, 'testing']);

// Route::get('/test', [InsideRoomController::class, 'levelTargetUp']);
