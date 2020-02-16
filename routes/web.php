<?php

Auth::routes();

//Route::get('setlocale/{locale}', function ($locale) {
//    if (in_array($locale, \Config::get('app.locales'))) {
//        Session::put('locale', $locale);
//    }
//    // Carbon\Carbon::setLocale(config('app.locale'));
//    return redirect()->back();
//});

Route::get('/', 'HomeController@postsApiRoute')->name('home');
Route::get('/top/week', 'HomeController@postsApiRoute')->name('top.week');
Route::get('/top/month', 'HomeController@postsApiRoute')->name('top.month');
Route::get('/all', 'HomeController@postsApiRoute')->name('all');

Route::group(['middleware' => ['auth']], function () {
    Route::get('/favorite', 'HomeController@postsApiRoute')->name('favorite');
    Route::get('tracker', 'Auth\NotificationController@index')->name('tracker');
    Route::get('tracker/remove/all', 'Auth\NotificationController@deleteAll')->name('delete-all-trackers');

    // Conversations
    Route::get('/conversations', 'ConversationController@index')->name('conversations');
    Route::get('/conversations/{user}', 'ConversationController@show')
//        ->middleware('can:talkTo,user')
        ->name('conversations.show');
    Route::post('/conversations/{user}', 'ConversationController@store')
        ->middleware('can:talkTo,user')
        ->name('conversations.show');

    Route::prefix('saved')->group(function (){
        Route::get('/posts', 'Auth\FavoriteController@indexPosts')->name('saved-posts');


        Route::get('/comments', 'Auth\FavoriteController@indexComments')->name('saved-comments');
    });

    Route::prefix('@{username}/settings')->group(function () {
        Route::get('profile', 'Auth\UserSettingsController@index')->name('profile-settings');
        Route::post('profile', 'Auth\UserSettingsController@update');
        Route::post('avatar', 'Auth\UserSettingsController@update_avatar');
    });
});

Route::prefix('api')->group(function () {
    /*
     * Posts Api
     */
    Route::get('posts/top/day', 'Api\PostController@posts');
    Route::get('posts/top/week', 'Api\PostController@posts');
    Route::get('posts/top/month', 'Api\PostController@posts');
    Route::get('posts/all', 'Api\PostController@all');
    Route::get('posts/favorite', 'Api\PostController@favorite');
    Route::get('posts/{id}', 'Api\PostController@show');

    /*
     * Favorite Api
     */

    Route::prefix('saved')->group(function () {
        Route::get('posts', 'Api\SavedController@allPosts');
        Route::get('comments', 'Api\SavedController@allComments');
    });

    /*
     * Hubs Api
     */
    Route::get('hubs/search', 'Api\HubController@search');
    Route::get('hubs/{id}/top/day', 'Api\PostHubController@posts');
    Route::get('hubs/{id}/top/week', 'Api\PostHubController@posts');
    Route::get('hubs/{id}/top/month', 'Api\PostHubController@posts');
    Route::get('hubs/{id}/all', 'Api\PostHubController@all');

    Route::get('hubs', 'Api\HubController@hubs')->name('hubs-list-api');

    /*
     * Users Api
     */
    Route::get('users/all', 'Api\UserController@users');

    /*
     * Profile Api
     */
    Route::get('/users/{id}/posts', 'Api\UserController@posts');

});

Route::prefix('post')->group(function () {
    Route::get('/add', 'PostController@create')->name('create_post');
    Route::get('/{id}', 'PostController@show')->name('show');
    Route::post('/{post}', 'PostController@updateViews');
    Route::post('/favorite/{id}', 'PostController@addFavorite');
});

Route::prefix('hubs')->group(function () {
    Route::get('/', 'HubController@index')->name('hubs-list');
    Route::get('/{id}', 'HubController@show');
    Route::get('/{id}/top/week', 'HubController@show');
    Route::get('/{id}/top/month', 'HubController@show');
    Route::get('/{id}/all', 'HubController@show');
    Route::post('/follow/{id}', 'HubController@follow');
});

Route::get('search-result', 'SearchController@index')->name('search-result');
Route::post('search-result', 'SearchController@index');

Route::prefix('users')->group(function () {
    Route::get('/', 'UserController@userList')->name('users-list');
    Route::post('{profileId}/follow', 'ProfileController@followUser')->name('user.follow');
    Route::post('{profileId}/un_follow', 'ProfileController@unFollowUser')->name('user.un_follow');
    Route::get('@{username}', 'UserController@show')->name('user_profile');
});

Route::prefix('comment')->group(function () {
    Route::post('new-comment', 'CommentController@newComment')->name('new-comment');
});

Route::resource('posts', 'PostController');

Route::get('about_us', function () {
    return view('pages.about_us');
});

// FUTURE
Route::post('upvote', 'PostController@vote');

Route::get('query', 'HomeController@indexTest');


Route::get('test', 'Auth\NotificationController@test');
