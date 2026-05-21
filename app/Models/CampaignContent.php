<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'language',
        'email_subject',
        'email_body',
        'whatsapp_message',
    ];

    /**
     * Get the campaign that owns this content
     */
    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Get the email subject
     */
    public function getSubject()
    {
        return $this->email_subject;
    }

    /**
     * Get the email body
     */
    public function getBody()
    {
        return $this->email_body;
    }

    /**
     * Get the WhatsApp message
     */
    public function getWhatsAppMessage()
    {
        return $this->whatsapp_message;
    }

    /**
     * Check if this content has email content
     */
    public function hasEmailContent()
    {
        return !empty($this->email_subject) && !empty($this->email_body);
    }

    /**
     * Check if this content has WhatsApp content
     */
    public function hasWhatsAppContent()
    {
        return !empty($this->whatsapp_message);
    }
}
