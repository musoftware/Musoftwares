<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('private-conversation.{id}', function ($user, $id) {
    // Add real authorization: return true if user is participant of conversation $id
    // For now, placeholder basic check or check via model policy
    return true;
});

Broadcast::channel('private-timer.{id}', function ($user, $id) {
    // Check if user is owner of the timer session
    return true;
});

Broadcast::channel('private-notifications.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('private-wallet.{walletId}', function ($user, $walletId) {
    // Return true if user owns wallet
    return true;
});

Broadcast::channel('private-admin.dashboard', function ($user) {
    return $user->hasRole('admin') || $user->hasRole('super_admin');
});
