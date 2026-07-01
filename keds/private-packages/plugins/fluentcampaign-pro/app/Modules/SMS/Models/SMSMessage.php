<?php

namespace FluentCampaign\App\Modules\SMS\Models;

use FluentCrm\App\Models\Model;
use FluentCrm\App\Models\Subscriber;

/**
 * @property int $id
 * @property int|null $campaign_id
 * @property string|null $sms_type
 * @property int|null $subscriber_id
 * @property string|null $mobile_number
 * @property int $click_counter
 * @property string|null $message_content
 * @property string $status
 * @property string|null $delivery_status
 * @property string|null $notes
 * @property string|null $provider_message_id
 * @property array|null $settings
 * @property string|null $scheduled_at
 * @property string|null $sent_at
 * @property string $created_at
 * @property string $updated_at
 */
class SMSMessage extends Model
{
    protected $table = 'fc_sms_messages';

    protected $fillable = [
        'campaign_id',
        'sms_type',
        'subscriber_id',
        'mobile_number',
        'click_counter',
        'message_content',
        'status',  // [ 'processing', 'pending', 'scheduled',
        'delivery_status',
        'notes',
        'provider_message_id',
        'settings',
        'scheduled_at',
        'sent_at'
    ];

    protected $casts = [
        'id' => 'integer',
        'campaign_id' => 'integer',
        'subscriber_id' => 'integer',
        'click_counter' => 'integer',
//        'settings' => 'array',
    ];

    protected $dates = [
        'scheduled_at',
        'sent_at',
        'created_at',
        'updated_at'
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    // Delivery status constants
    const DELIVERY_QUEUED = 'queued';
    const DELIVERY_SENT = 'sent';
    const DELIVERY_DELIVERED = 'delivered';
    const DELIVERY_FAILED = 'failed';
    const DELIVERY_UNDELIVERED = 'undelivered';

    // SMS type constants
    const TYPE_CAMPAIGN = 'campaign';
    const TYPE_AUTOMATION = 'automation';
    const TYPE_CUSTOM_SMS = 'custom_sms';
    const TYPE_BROADCAST = 'broadcast';

    /**
     * Relationship with Subscriber
     */
    public function subscriber()
    {
        return $this->belongsTo(Subscriber::class, 'subscriber_id');
    }

    /**
     * Relationship with SMS Campaign
     */
    public function campaign()
    {
        return $this->belongsTo(SMSCampaign::class, 'campaign_id');
    }

    /**
     * Scope for filtering by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by SMS type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('sms_type', $type);
    }

    /**
     * Scope for filtering by subscriber
     */
    public function scopeBySubscriber($query, $subscriberId)
    {
        return $query->where('subscriber_id', $subscriberId);
    }

    /**
     * Scope for filtering by campaign
     */
    public function scopeByCampaign($query, $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate = null)
    {
        $query->where('sent_at', '>=', $startDate);
        
        if ($endDate) {
            $query->where('sent_at', '<=', $endDate);
        }

        return $query;
    }

    /**
     * Scope for sent messages
     */
    public function scopeSent($query)
    {
        return $query->whereNotNull('sent_at');
    }

    /**
     * Scope for pending messages
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for failed messages
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Check if message is sent
     */
    public function isSent()
    {
        return $this->status === self::STATUS_SENT || $this->sent_at !== null;
    }

    /**
     * Check if message failed
     */
    public function isFailed()
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if message is pending
     */
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Mark message as sent
     */
    public function markAsSent($providerMessageId = null)
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'delivery_status' => self::DELIVERY_SENT,
            'sent_at' => current_time('mysql'),
            'provider_message_id' => $providerMessageId
        ]);

        return $this;
    }

    /**
     * Mark message as delivered
     */
    public function markAsDelivered()
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivery_status' => self::DELIVERY_DELIVERED
        ]);

        return $this;
    }

    /**
     * Mark message as failed
     */
    public function markAsFailed($notes = null)
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'delivery_status' => self::DELIVERY_FAILED,
            'notes' => $notes
        ]);

        return $this;
    }

    /**
     * Get formatted mobile number
     */
    public function getFormattedMobileNumberAttribute()
    {
        if (!$this->mobile_number) {
            return null;
        }

        // Add basic formatting - can be enhanced based on requirements
        $number = preg_replace('/[^0-9+]/', '', $this->mobile_number);
        
        if (!str_starts_with($number, '+')) {
            // Assume US number if no country code
            if (strlen($number) === 10) {
                $number = '+1' . $number;
            }
        }

        return $number;
    }

    /**
     * Get truncated message content
     */
    public function getShortMessageAttribute($length = 50)
    {
        if (!$this->message_content) {
            return null;
        }

        if (strlen($this->message_content) <= $length) {
            return $this->message_content;
        }

        return substr($this->message_content, 0, $length) . '...';
    }

    /**
     * Get message character count
     */
    public function getMessageLengthAttribute()
    {
        return strlen($this->message_content ?? '');
    }

    /**
     * Get estimated SMS segments
     */
    public function getEstimatedSegmentsAttribute()
    {
        $length = $this->getMessageLengthAttribute();
        
        if ($length <= 160) {
            return 1;
        }

        // For multipart SMS, each segment can contain 153 characters
        return ceil($length / 153);
    }

    /**
     * Static method to create SMS message
     */
    public static function createMessage($data)
    {
        return static::create([
            'campaign_id' => $data['campaign_id'] ?? null,
            'sms_type' => $data['sms_type'] ?? self::TYPE_CUSTOM_SMS,
            'subscriber_id' => $data['subscriber_id'] ?? null,
            'mobile_number' => $data['mobile_number'],
            'message_content' => $data['message_content'],
            'status' => $data['status'] ?? self::STATUS_PENDING,
            'delivery_status' => $data['delivery_status'] ?? self::DELIVERY_QUEUED,
            'settings' => $data['settings'] ?? [],
            'scheduled_at' => $data['scheduled_at'] ?? null
        ]);
    }

    /**
     * Get statistics for messages
     */
    public static function getStats($subscriberId = null)
    {
        $baseQuery = static::query();
        
        if ($subscriberId) {
            $baseQuery->where('subscriber_id', $subscriberId);
        }

        return [
            'total' => (clone $baseQuery)->count(),
            'sent' => (clone $baseQuery)->where('status', self::STATUS_SENT)->count(),
            'delivered' => (clone $baseQuery)->where('delivery_status', self::DELIVERY_DELIVERED)->count(),
            'failed' => (clone $baseQuery)->where('status', self::STATUS_FAILED)->count(),
            'pending' => (clone $baseQuery)->where('status', self::STATUS_PENDING)->count(),
            'by_type' => [
                'campaign' => (clone $baseQuery)->where('sms_type', self::TYPE_CAMPAIGN)->count(),
                'automation' => (clone $baseQuery)->where('sms_type', self::TYPE_AUTOMATION)->count(),
                'custom_sms' => (clone $baseQuery)->where('sms_type', self::TYPE_CUSTOM_SMS)->count()
            ]
        ];
    }
}
