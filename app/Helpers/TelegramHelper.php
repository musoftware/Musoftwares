<?php

namespace App\Helpers;

use App\Models\TelegramSavedMessage;
use App\Models\TelegramMessageFolder;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TelegramHelper
{
    public static function parseId($id, $user_id)
    {
        if (Str::startsWith($id, 'message')) {
            return User::query()->find($user_id)->telegramSavedMessages()->find(str_replace('message', '', $id));
        } else {
            if (Auth::user()->hasRole('admin')) {
                if (Str::startsWith(str_replace('folder', '', $id), 'usr-')) {
                    $n_user_id = str_replace('folderusr-', '', $id);
                    return User::query()->find($n_user_id)->telegramMessageFolders()->whereNull('parent_folder_id')->get();
                }
            }
            return User::query()->find($user_id)->telegramMessageFolders()->find(str_replace('folder', '', $id));
        }
    }

    public static function getPath($path, $user_id)
    {
        $latest = null;
        foreach ($path as $id) {
            if (empty($id)) continue;
            $latest = TelegramHelper::parseId($id, $user_id);
        }
        return $latest;
    }

    public static function rename($id, $new_name)
    {
        $message_or_folder = static::parseId($id, request('user_id'));
        if ($message_or_folder != null) {
            if ($message_or_folder instanceof TelegramSavedMessage) {
                $message_or_folder->title = $new_name;
            } else {
                $message_or_folder->folder_name = $new_name;
            }
            $message_or_folder->save();
            return true;
        } else {
            return false;
        }
    }

    public static function move($id, $folder_id, $user_id)
    {
        $message_or_folder = static::parseId($id, $user_id);
        if ($message_or_folder != null) {
            $message_or_folder->folder_id = $folder_id;
            $message_or_folder->save();
            return true;
        } else {
            return false;
        }
    }

    public static function delete($id, $user_id)
    {
        $message_or_folder = static::parseId($id, $user_id);
        if ($message_or_folder != null) {
            if ($message_or_folder instanceof TelegramSavedMessage) {
                $message_or_folder->delete();
            } else {
                // Delete folder and all its contents recursively
                static::deleteFolderRecursive($message_or_folder);
            }
            return true;
        } else {
            return false;
        }
    }

    private static function deleteFolderRecursive($folder)
    {
        // Delete all messages in this folder
        $folder->messages()->delete();

        // Delete all subfolders recursively
        foreach ($folder->subFolders as $subFolder) {
            static::deleteFolderRecursive($subFolder);
        }

        // Delete the folder itself
        $folder->delete();
    }
}
