<?php

namespace App\Models\Operations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class File extends Model
{
    use HasFactory;


    public static function formatBytes($size, $precision = 2)
    {
        if ($size > 0) {
            $size = (int)$size;
            $base = log($size) / log(1024);
            $suffixes = array(' bytes', ' KB', ' MB', ' GB', ' TB');

            return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
        } else {
            return $size;
        }
    }

    public function realPath()
    {
        return '/uploaded_user_files/' . $this->filename;
    }

    public static function currentFolderFiles($folder_id, $filter = null)
    {
        if ($filter == null) {
            return Auth::user()->files()->where('folder_id', $folder_id)->get();
        } else {
            return Auth::user()->files()->where('filetype', $filter)->where('folder_id', $folder_id)->get();
        }
    }

    public function delete_hash()
    {
        return sha1(sha1($this->id . '---' . $this->filetype)
            . '---' . $this->created_at);
    }

    public function hash()
    {
        return md5(sha1($this->id . '---' . $this->filetype)
            . '---' . $this->created_at);

    }

    public function filesize()
    {
        return File::formatBytes($this->size, 2);
    }

    public function name_only()
    {
        return mb_substr($this->original_filename, 0, -1 * mb_strlen($this->extension()));
    }

    public function extension()
    {
        $arr = explode('.', $this->filename);
        return '.' . end($arr);
    }

    public function image()
    {
        if ($this->type() == 'audio') {
            return asset('images/audio.png');
        }
        if ($this->type() == 'document') {
            return asset('images/document.png');
        }
        if ($this->type() == 'video') {
            return asset('images/video.png');
        }
        if ($this->type() == 'image') {
            return $this->url;
        }
        if ($this->type() == 'json') {
            return asset('images/json.png');
        }
    }

    public static function get_assets()
    {
        $files = [];
        foreach (Auth::user()->files()->select('filename')->get()->pluck('filename') as $item) {
            $files[] = \Illuminate\Support\Facades\Storage::disk('uploaded_user_files')->url($item);
        }
        return $files;
    }


    public function parent_folder()
    {
        return $this->belongsTo(\App\Models\Operations\FileFolder::class, 'folder_id');
    }

    public function path()
    {
        if ($this->parent_folder != null) {
            return $this->parent_folder->path() . '/' . $this->original_filename;
        } else {
            return $this->original_filename;
        }
    }

    public function type_four_only()
    {
        $type = $this->type();
        if ($type == 'audio') return 'audio';
        if ($type == 'image') return 'image';
        if ($type == 'video') return 'video';
        return 'document';
    }

    public function type()
    {
        if (Str::endsWith(strtolower($this->original_filename), '.mp3')) {
            return 'audio';
        }
        if (
            Str::endsWith(strtolower($this->original_filename), '.gif') ||
            Str::endsWith(strtolower($this->original_filename), '.jpg') ||
            Str::endsWith(strtolower($this->original_filename), '.jpeg') ||
            Str::endsWith(strtolower($this->original_filename), '.png')
        ) {
            return 'image';
        }
        if (
            Str::endsWith(strtolower($this->original_filename), '.mp4') ||
            Str::endsWith(strtolower($this->original_filename), '.mov')
        ) {
            return 'video';
        }
        if (
            Str::endsWith(strtolower($this->original_filename), '.txt') ||
            Str::endsWith(strtolower($this->original_filename), '.csv') ||
            Str::endsWith(strtolower($this->original_filename), '.pdf')
        ) {
            return 'document';
        }
        if (Str::endsWith(strtolower($this->original_filename), '.json')) {
            return 'json';
        }
    }
}

