<?php

namespace App\Models;

use App\Services\LeadDataCleaningService;

trait CleansLeadData
{
    /**
     * Boot the trait
     */
    protected static function bootCleansLeadData()
    {
        static::saving(function ($model) {
            $model->cleanAttributes();
        });
    }

    /**
     * Clean model attributes before saving
     */
    protected function cleanAttributes()
    {
        $attributes = $this->getAttributes();
        
        // Clean name
        if (isset($attributes['name'])) {
            $this->attributes['name'] = LeadDataCleaningService::cleanName($attributes['name']);
        }
        
        // Clean email
        if (isset($attributes['email'])) {
            $this->attributes['email'] = LeadDataCleaningService::cleanEmail($attributes['email']);
        }
        
        // Clean phone
        if (isset($attributes['phone'])) {
            $this->attributes['phone'] = LeadDataCleaningService::cleanPhone($attributes['phone']);
        }
        
        // Clean company
        if (isset($attributes['company'])) {
            $this->attributes['company'] = LeadDataCleaningService::cleanCompany($attributes['company']);
        }
        
        // Clean message
        if (isset($attributes['message'])) {
            $this->attributes['message'] = LeadDataCleaningService::cleanMessage($attributes['message']);
        }
    }

    /**
     * Scope to get leads with valid contact info
     */
    public function scopeWithValidContactInfo($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('email')
              ->where('email', '!=', '')
              ->orWhereNotNull('phone')
              ->where('phone', '!=', '');
        });
    }

    /**
     * Scope to get leads with phone only
     */
    public function scopePhoneOnly($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('email')
              ->orWhere('email', '=', '');
        })->whereNotNull('phone')
          ->where('phone', '!=', '');
    }

    /**
     * Scope to get leads with email only
     */
    public function scopeEmailOnly($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('phone')
              ->orWhere('phone', '=', '');
        })->whereNotNull('email')
          ->where('email', '!=', '');
    }

    /**
     * Check if lead has valid contact info
     */
    public function hasValidContactInfo(): bool
    {
        return (!empty($this->email) && filter_var($this->email, FILTER_VALIDATE_EMAIL)) ||
               (!empty($this->phone) && strlen($this->phone) >= 9);
    }

    /**
     * Get primary contact method
     */
    public function getPrimaryContactMethod(): string
    {
        if (!empty($this->email) && filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            return 'email';
        }
        
        if (!empty($this->phone) && strlen($this->phone) >= 9) {
            return 'phone';
        }
        
        return 'none';
    }

    /**
     * Get display contact info
     */
    public function getDisplayContactInfo(): string
    {
        if (!empty($this->email) && filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            return $this->email;
        }
        
        if (!empty($this->phone) && strlen($this->phone) >= 9) {
            return '+' . $this->phone;
        }
        
        return 'No contact info';
    }
}
