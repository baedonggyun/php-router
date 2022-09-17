<?php
Route::add('/member', static function(){
    Route::action('MemberController@getMember');
});