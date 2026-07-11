<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class FileFolder extends Model
{
    use HasFactory, SoftDeletes;

    public function files()
    {
        return $this->hasMany(File::class, 'folder_id');
    }

    public function fileFolders()
    {
        return $this->hasMany(FileFolder::class, 'folder_id');
    }

    public function parent_folder()
    {
        return $this->belongsTo(FileFolder::class, 'folder_id');
    }

    public function delete_folder()
    {
        foreach ($this->fileFolders as $folder) {
            if ($folder != null) {
                $folder->delete_folder();
            }
        }
        foreach ($this->files as $file) {
            if ($file != null) {
                if (Storage::disk('uploaded_user_files')->exists($file->filename)) {
                    Storage::disk('uploaded_user_files')->delete($file->filename);
                }
                $file->delete();
            }
        }
        $this->delete();
    }

    public function path()
    {
        if ($this->parent_folder != null) {
            return $this->parent_folder->path().'/'.$this->foldername;
        } else {
            return $this->foldername;
        }
    }

    public function init_zip($zip)
    {
        foreach ($this->files as $file) {
            if (Storage::disk('uploaded_user_files')->exists($file->filename)) {
                $zip->addFromString($file->path(), Storage::disk('uploaded_user_files')->get($file->filename));
            }
        }
        foreach ($this->fileFolders as $folder) {
            $folder->init_zip($zip);
        }
    }
}
