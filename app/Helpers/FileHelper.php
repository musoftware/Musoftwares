<?php

namespace App\Helpers;

use App\Models\File;
use App\Models\FileFolder;
use App\Models\Freelance\Client;
use App\Models\Freelance\Currency;
use App\Models\User;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Module\RoundnessModule;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Meneses\LaravelMpdf\LaravelMpdfWrapper;
use PDF;

class FileHelper
{

    public static function createClientRequests($user, $folder_name)
    {
        $folder = $user->fileFolders()->where('foldername', $folder_name)->first();

        if ($folder == null) {
            $new_folder = new FileFolder();
            $new_folder->user_id = $user->id;
            $new_folder->foldername = $folder_name;
            $new_folder->save();
            $folder = $new_folder;
        }
        return $folder;
    }


    public static function HasFile($id)
    {
        if (is_string($id) && !is_numeric($id)) return false;
        $file = \App\Models\File::find($id);
        return ($file != null);
    }

    public static function Image($id)
    {
        if (is_string($id) && !is_numeric($id)) return '';
        $file = \App\Models\File::find($id);
        return $file->image();
    }

    public static function parseId($id, $user_id)
    {
        if (Str::startsWith($id, 'file')) {
            return User::query()->find($user_id)->files()->find(str_replace('file', '', $id));
        } else {
            if (\Illuminate\Support\Facades\Auth::user()->hasRole('admin')) {
                if (Str::startsWith(str_replace('folder', '', $id), 'usr-')){
                    $n_user_id = str_replace('folderusr-', '', $id);
                    return User::query()->find($n_user_id)->fileFolders()->whereNull('folder_id')->get();
                }
            }
            return User::query()->find($user_id)->fileFolders()->find(str_replace('folder', '', $id));
        }
    }

    public static function getPath($path, $user_id)
    {
        $latest = null;
        foreach ($path as $id) {
            if (empty($id)) continue;
            $latest = FileHelper::parseId($id, $user_id);
        }
        return $latest;
    }

    public static function rename($id, $new_name)
    {
        $file_or_folder = static::parseId($id, request('user_id'));
        if ($file_or_folder != null) {
            if ($file_or_folder instanceof File) {
                $file_or_folder->original_filename = $new_name;
            } else {
                $file_or_folder->foldername = $new_name;
            }
            $file_or_folder->save();
            return true;
        } else {
            return false;
        }
    }

    public static function move($id, $folder_id, $user_id)
    {
        $file_or_folder = static::parseId($id, $user_id);
        if ($file_or_folder != null) {
            $file_or_folder->folder_id = $folder_id;
            $file_or_folder->save();
            return true;
        } else {
            return false;
        }
    }

    public static function delete($id, $user_id)
    {
        $file_or_folder = static::parseId($id, $user_id);
        if ($file_or_folder != null) {
            if ($file_or_folder instanceof File) {
                if (\Illuminate\Support\Facades\Storage::disk('uploaded_user_files')->exists($file_or_folder->filename)) {
                    \Illuminate\Support\Facades\Storage::disk('uploaded_user_files')->delete($file_or_folder->filename);
                }
                $file_or_folder->delete();
            } else {
                $file_or_folder->delete_folder();
            }
            return true;
        } else {
            return false;
        }
    }
}
