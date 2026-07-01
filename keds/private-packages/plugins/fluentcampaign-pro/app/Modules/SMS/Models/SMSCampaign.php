<?php

namespace FluentCampaign\App\Modules\SMS\Models;

use FluentCrm\App\Models\Label;
use FluentCrm\App\Models\Meta;
use FluentCrm\App\Models\Model;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Models\TermRelation;
use FluentCrm\App\Services\ContactsQuery;
use FluentCrm\Framework\Support\Arr;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string $type
 * @property string $title
 * @property string $slug
 * @property string $status
 * @property string $message_content
 * @property string|null $sender_number
 * @property int $recipients_count
 * @property int $sent_count
 * @property int $failed_count
 * @property int|null $delay
 * @property string|null $scheduled_at
 * @property array|null $settings
 * @property int|null $created_by
 * @property string $created_at
 * @property string $updated_at
 */
class SMSCampaign extends Model
{
    protected $table = 'fc_sms_campaigns';

    protected $fillable = [
        'parent_id',
        'type', // [ 'campaign', 'custom-sms', 'automation' ..]
        'title',
        'slug',
        'status',  // [ 'draft', 'pending-scheduled', 'processing', 
        'message_content',
        'sender_number',
        'recipients_count',
        'sent_count',
        'failed_count',
        'delay',
        'scheduled_at',
        'settings',
        'created_by'
    ];

    protected $casts = [
        'id' => 'integer',
        'parent_id' => 'integer',
        'recipients_count' => 'integer',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
        'delay' => 'integer',
        'created_by' => 'integer'
    ];

    protected $dates = [
        'scheduled_at',
        'created_at',
        'updated_at'
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_ACTIVE = 'active';
    const STATUS_SENDING = 'sending';
    const STATUS_SENT = 'sent';
    const STATUS_PAUSED = 'paused';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_FAILED = 'failed';

    // Type constants
    const TYPE_SMS = 'sms';

    protected static $type = 'campaign';

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->status = $model->status ?: 'draft';
            $model->type = $model->type ?: self::$type;
            $model->slug = $model->slug ?: sanitize_title($model->title, '', 'preview');
            $model->created_by = $model->created_by ?: get_current_user_id();
            $model->settings = $model->settings ?: [
                'subscribers' => [
                    [
                        'list' => 'all',
                        'tag'  => 'all'
                    ]
                ],
                'excludedSubscribers' => [
                    [
                        'list' => null,
                        'tag'  => null
                    ]
                ],
                'sending_filter'    => 'list_tag',
                'dynamic_segment'  => [
                    'id'   => '',
                    'slug' => ''
                ],
                'advanced_filters' => [
                    []
                ],
                'sending_type'     => 'instant',
                'is_transactional' => 'no'
            ];
        });
    }

    /**
     * Relationship with SMS Messages
     */
    public function messages()
    {
        return $this->hasMany(SMSMessage::class, 'campaign_id');
    }

    /**
     * Scope for filtering by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for active campaigns
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate = null)
    {
        $query->where('created_at', '>=', $startDate);
        
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        return $query;
    }

    public function setSettingsAttribute($settings)
    {
        $this->attributes['settings'] = \maybe_serialize($settings);
    }

    public function getSettingsAttribute($settings)
    {
        return \maybe_unserialize($settings);
    }

    /**
     * Check if campaign is draft
     */
    public function isDraft()
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if campaign is active
     */
    public function isActive()
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if campaign is scheduled
     */
    public function isScheduled()
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    /**
     * Check if campaign is sending
     */
    public function isSending()
    {
        return $this->status === self::STATUS_SENDING;
    }

    /**
     * Check if campaign is sent
     */
    public function isSent()
    {
        return $this->status === self::STATUS_SENT;
    }

    /**
     * Check if campaign is paused
     */
    public function isPaused()
    {
        return $this->status === self::STATUS_PAUSED;
    }

    /**
     * Check if campaign is cancelled
     */
    public function isCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if campaign failed
     */
    public function isFailed()
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Mark campaign as active
     */
    public function markAsActive()
    {
        $this->update(['status' => self::STATUS_ACTIVE]);
        return $this;
    }

    /**
     * Mark campaign as sending
     */
    public function markAsSending()
    {
        $this->update(['status' => self::STATUS_SENDING]);
        return $this;
    }

    /**
     * Mark campaign as sent
     */
    public function markAsSent()
    {
        $this->update(['status' => self::STATUS_SENT]);
        return $this;
    }

    /**
     * Mark campaign as paused
     */
    public function markAsPaused()
    {
        $this->update(['status' => self::STATUS_PAUSED]);
        return $this;
    }

    /**
     * Mark campaign as cancelled
     */
    public function markAsCancelled()
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
        return $this;
    }

    /**
     * Mark campaign as failed
     */
    public function markAsFailed()
    {
        $this->update(['status' => self::STATUS_FAILED]);
        return $this;
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRateAttribute()
    {
        if ($this->sent_count === 0) {
            return 0;
        }

        $successCount = $this->sent_count - $this->failed_count;
        return round(($successCount / $this->sent_count) * 100, 2);
    }

    /**
     * Get failure rate percentage
     */
    public function getFailureRateAttribute()
    {
        if ($this->sent_count === 0) {
            return 0;
        }

        return round(($this->failed_count / $this->sent_count) * 100, 2);
    }

    /**
     * Get progress percentage
     */
    public function getProgressAttribute()
    {
        if ($this->recipients_count === 0) {
            return 0;
        }

        return round(($this->sent_count / $this->recipients_count) * 100, 2);
    }

    /**
     * Get remaining recipients count
     */
    public function getRemainingCountAttribute()
    {
        return max(0, $this->recipients_count - $this->sent_count);
    }

    /**
     * Get truncated message content
     */
    public function getShortMessageAttribute($length = 100)
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
     * Get estimated SMS segments per message
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
     * Get total estimated segments for all recipients
     */
    public function getTotalEstimatedSegmentsAttribute()
    {
        return $this->getEstimatedSegmentsAttribute() * $this->recipients_count;
    }

    /**
     * Increment sent count
     */
    public function incrementSentCount($amount = 1)
    {
        $this->increment('sent_count', $amount);
        return $this;
    }

    /**
     * Increment failed count
     */
    public function incrementFailedCount($amount = 1)
    {
        $this->increment('failed_count', $amount);
        return $this;
    }

    /**
     * Update recipients count
     */
    public function updateRecipientsCount($count)
    {
        $this->update(['recipients_count' => $count]);
        return $this;
    }

    /**
     * Generate unique slug
     */
    public function generateSlug($title)
    {
        $slug = sanitize_title($title);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->where('id', '!=', $this->id ?? 0)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Static method to create SMS campaign
     */
    public static function createCampaign($data)
    {
        $campaign = new static();
        
        $slug = $campaign->generateSlug($data['title']);

        return static::create([
            'parent_id' => $data['parent_id'] ?? null,
            'type' => $data['type'] ?? self::TYPE_SMS,
            'title' => $data['title'],
            'slug' => $slug,
            'status' => $data['status'] ?? self::STATUS_DRAFT,
            'message_content' => $data['message_content'],
            'sender_number' => $data['sender_number'] ?? null,
            'recipients_count' => $data['recipients_count'] ?? 0,
            'delay' => $data['delay'] ?? 0,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'settings' => $data['settings'] ?? [],
            'created_by' => $data['created_by'] ?? get_current_user_id()
        ]);
    }

    /**
     * Get campaign statistics
     */
    public function getStatistics()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status,
            'recipients_count' => $this->recipients_count,
            'sent_count' => $this->sent_count,
            'failed_count' => $this->failed_count,
            'success_rate' => $this->getSuccessRateAttribute(),
            'failure_rate' => $this->getFailureRateAttribute(),
            'progress' => $this->getProgressAttribute(),
            'remaining_count' => $this->getRemainingCountAttribute(),
            'message_length' => $this->getMessageLengthAttribute(),
            'estimated_segments' => $this->getEstimatedSegmentsAttribute(),
            'total_estimated_segments' => $this->getTotalEstimatedSegmentsAttribute(),
            'created_at' => $this->created_at,
            'scheduled_at' => $this->scheduled_at
        ];
    }

    /**
     * Get overview statistics for all campaigns
     */
    public static function getOverviewStats()
    {
        return [
            'total_campaigns' => static::count(),
            'draft_campaigns' => static::where('status', self::STATUS_DRAFT)->count(),
            'active_campaigns' => static::where('status', self::STATUS_ACTIVE)->count(),
            'sent_campaigns' => static::where('status', self::STATUS_SENT)->count(),
            'total_recipients' => static::sum('recipients_count'),
            'total_sent' => static::sum('sent_count'),
            'total_failed' => static::sum('failed_count')
        ];
    }

    public function getSubscriberIdsCountBySegmentSettings($settings, $status = 'subscribed')
    {
        $model = $this->getSubscribersModel($settings);
        if ($model) {
            return $model->count();
        }

        return 0;
    }

    public function getSubscribersModel($settings = false)
    {
        if (!$settings) {
            $settings = $this->settings;
        }

        $filterType = Arr::get($settings, 'sending_filter', 'list_tag');

        if ($filterType == 'list_tag') {
            $subscriberModel = $this->getSubscribeIdsByListModel($settings['subscribers'], 'sms_subscribed');
            if ($excludeItems = Arr::get($settings, 'excludedSubscribers')) {
                $formattedExcludedItems = [];
                foreach ($excludeItems as $item) {
                    if (empty($item['list']) && empty($item['tag'])) {
                        continue;
                    }
                    $formattedExcludedItems[] = $item;
                }

                if ($formattedExcludedItems) {
                    $excludedModel = $this->getSubscribeIdsByListModel($excludeItems, 'sms_subscribed');
                    $excludedModel->select('id');
                    $subscriberModel->whereNotIn('id', $excludedModel->getQuery());
                }
            }

            return $subscriberModel;
        }

        if ($filterType == 'dynamic_segment') {
            $segmentSettings = Arr::get($settings, 'dynamic_segment', []);
            $segmentSettings['offset'] = 0;
            $segmentSettings['limit'] = false;

            /**
             * Filter the dynamic segment details based on the segment slug.
             *
             * This filter allows you to modify the details of a dynamic segment.
             *
             * @param array  The details of the dynamic segment.
             * @param int $segmentSettings ['id']      The ID of the segment.
             * @param array {
             *     Additional context for the segment.
             *
             * @type bool  Whether to include the model in the context.
             * }
             *
             * @return array Modified segment details.
             * @since 2.5.93
             *
             */
            $segmentDetails = apply_filters('fluentcrm_dynamic_segment_' . $segmentSettings['slug'], [], $segmentSettings['id'], [
                'model' => true
            ]);

            if (!empty($segmentDetails['model'])) {
                $model = $segmentDetails['model'];
                $model->where('sms_status', 'sms_subscribed');
                return $model;
            }

            return null;
        }

        if ($filterType == 'advanced_filters') {
            $query = new ContactsQuery([
                'with'               => [],
                'filter_type'        => 'advanced',
                'contact_status'     => 'subscribed',
                'filters_groups_raw' => $settings['advanced_filters']
            ]);

            return $query->getModel();
        }

        return null;
    }

    public function getSubscribeIdsByListModel($items, $status = 'sms_subscribed', $limit = false, $offset = 0)
    {

        $query = Subscriber::where('sms_status', $status);

        $queryGroups = [];

        $willSkip = false;

        $hasListFilter = false;
        $tagIds = [];
        foreach ($items as $item) {
            $listId = $item['list'];
            $tagId = $item['tag'];
            if (!$listId || !$tagId) {
                continue;
            }

            if ($listId == 'all' && $tagId == 'all') {
                $willSkip = true;
            } else if ($listId == 'all') {
                $queryGroups[] = ['tag_id' => $tagId];
                $tagIds[] = $tagId;
            } else if ($tagId == 'all') {
                $hasListFilter = true;
                $queryGroups[] = ['list_id' => $listId];
            } else {
                $hasListFilter = true;
                $tagIds[] = $tagId;
                $queryGroups[] = [
                    'list_id' => $listId,
                    'tag_id'  => $tagId
                ];
            }
        }

        if (!$willSkip && !$hasListFilter && $tagIds) {
            $query->filterByTags($tagIds);
        } else if (!$willSkip && $queryGroups) {
            $query->where(function ($innerQuery) use ($queryGroups) {
                $type = 'where';
                foreach ($queryGroups as $queryGroup) {
                    $innerQuery->{$type}(function ($q) use ($queryGroup, $innerQuery) {
                        foreach ($queryGroup as $type => $id) {
                            if ($type == 'tag_id') {
                                $q->whereIn('id', function ($query) use ($id) {
                                    return $this->getSubQueryForListOrTagFilter($query, [$id], 'tags', 'FluentCrm\App\Models\Tag');
                                });
                            } else if ($type == 'list_id') {
                                $q->whereIn('id', function ($query) use ($id) {
                                    return $this->getSubQueryForListOrTagFilter($query, [$id], 'lists', 'FluentCrm\App\Models\Lists');
                                });
                            }
                        }
                    });

                    $type = 'orWhere';
                }
            });
        }

        if ($limit) {
            $query->limit($limit)->offset($offset);
        }

        return $query;
    }

    private function getSubQueryForListOrTagFilter($query, $ids, $table, $objectType)
    {
        $prefix = 'fc_';

        return $query->from($prefix . $table)
                     ->join(
                         $prefix . 'subscriber_pivot',
                         $prefix . 'subscriber_pivot.object_id',
                         '=',
                         $prefix . $table . '.id'
                     )
                     ->where($prefix . 'subscriber_pivot.object_type', $objectType)
                     ->whereIn($prefix . $table . '.id', $ids)
                     ->groupBy($prefix . 'subscriber_pivot.subscriber_id')
                     ->select($prefix . 'subscriber_pivot.subscriber_id');
    }

    /**
     * Add one or more subscribers to the SMS campaign
     * @param array $subscriberIds
     * @param array $smsArgs extra sms_message args
     * @param bool $isModel if the $subscriberIds is collection or not
     * @return array
     */
    public function subscribe($subscriberIds, $smsArgs = [], $isModel = false)
    {
        $updateIds = [];

        if ($isModel) {
            $subscribers = $subscriberIds;
        } else {
            $subscribers = \FluentCrm\App\Models\Subscriber::whereIn('id', $subscriberIds)->get();
        }

        $validStatuses = ['sms_subscribed'];

        foreach ($subscribers as $subscriber) {
            if (!in_array($subscriber->sms_status, $validStatuses)) {
                continue; // We don't want to send SMS to non-subscribed members
            }

            // Skip if subscriber doesn't have a mobile number
            if (empty($subscriber->phone)) {
                continue;
            }

            $time = fluentCrmTimestamp();
            $smsMessage = [
                'campaign_id'     => $this->id,
                'status'          => $this->status,
                'subscriber_id'   => $subscriber->id,
                'mobile_number'   => $subscriber->phone,
                'message_content' => $this->message_content,
                'created_at'      => $time,
                'updated_at'      => $time
            ];

            if ($smsArgs) {
                $smsMessage = wp_parse_args($smsArgs, $smsMessage);
            }

            $inserted = SMSMessage::create($smsMessage);
            $updateIds[] = $inserted->id;
        }

        $messageCount = $this->getMessageCount();
        if ($messageCount != $this->recipients_count) {
            $this->recipients_count = $messageCount;
            $this->save();
        }

        return $updateIds;
    }

    /**
     * Remove one or more subscribers from the SMS campaign
     * @param array $subscriberIds
     * @return bool
     */
    public function unsubscribe($subscriberIds)
    {
        $result = $this->messages()->whereIn('subscriber_id', $subscriberIds)->delete();

        $this->recipients_count = $this->messages()->count();
        $this->save();

        return $result;
    }

    /**
     * Get the total count of SMS messages for this campaign
     * @return int
     */
    public function getMessageCount()
    {
        return $this->messages()->count();
    }

    public function deleteCampaignData()
    {
        SMSMessage::where('campaign_id', $this->id)->delete();

        Meta::where('object_id', $this->id)
            ->where('object_type', 'FluentCampaign\App\Modules\SMS\Models\SMSCampaign')
            ->delete();

        return $this;
    }

    public function labelsTerm()
    {
        return $this->belongsToMany(
            Label::class,
            'fc_term_relations',
            'object_id',
            'term_id'
        )->wherePivot('object_type', __CLASS__);
    }

    public function getFormattedLabels()
    {
        $labels = $this->labels();
        return $labels->map(function ($label) {
            return [
                'id'    => $label->id,
                'slug'  => $label->slug,
                'title' => $label->title,
                'color' => $label->settings['color'] ?? ''
            ];
        });
    }

    public function labels()
    {
        $labelIds = TermRelation::where('object_id', $this->id)
            ->where('object_type', __CLASS__)
            ->pluck('term_id')
            ->toArray();

        if (empty($labelIds)) {
            return Label::whereRaw('1 = 0')->get();
        }

        return Label::whereIn('id', $labelIds)->get();
    }

    public function attachLabels($labelIds)
    {
        $labelIds = is_array($labelIds) ? $labelIds : [$labelIds];
        $labelIds = array_values(array_unique(array_filter(array_map('intval', $labelIds))));

        if (empty($labelIds)) {
            return $this;
        }

        $existingLabelIds = TermRelation::where('object_id', $this->id)
            ->where('object_type', __CLASS__)
            ->pluck('term_id')
            ->map(function ($id) {
                return (int)$id;
            })
            ->toArray();

        $newLabelIds = array_diff($labelIds, $existingLabelIds);

        foreach ($newLabelIds as $labelId) {
            TermRelation::create([
                'object_id'   => $this->id,
                'object_type' => __CLASS__,
                'term_id'     => $labelId
            ]);
        }

        return $this;
    }

    public function detachLabels($labelIds)
    {
        $labelIds = is_array($labelIds) ? $labelIds : [$labelIds];
        $labelIds = array_values(array_unique(array_filter(array_map('intval', $labelIds))));

        if (empty($labelIds)) {
            return $this;
        }

        TermRelation::where('object_id', $this->id)
            ->where('object_type', __CLASS__)
            ->whereIn('term_id', $labelIds)
            ->delete();

        return $this;
    }

    public function getSMSScheduleAt()
    {
        static $scheduled_at = null;
        if ($scheduled_at) {
            return $scheduled_at;
        }

        $settings = $this->settings;

        if (Arr::get($settings, 'sending_type') != 'range_schedule') {
            $scheduled_at = $this->scheduled_at;
            return $scheduled_at;
        }

        // this is a range selector
        $ranges = Arr::get($settings, 'schedule_range', [$this->scheduled_at, $this->scheduled_at]);

        $timeStamp = random_int($ranges[0], $ranges[1]);

        if ($timeStamp < current_time('timestamp')) {
            $timeStamp = current_time('timestamp') + 60;
        }

        return date('Y-m-d H:i:s', $timeStamp);
    }

    public function rangedScheduleDates()
    {
        $settings = $this->settings;
        if (Arr::get($settings, 'sending_type') != 'range_schedule') {
            return null;
        }

        $ranges = Arr::get($settings, 'schedule_range', ['', '']);
        if (!$ranges) {
            return null;
        }

        return [
            'start' => gmdate('Y-m-d H:i:s', $ranges[0]),
            'end'   => gmdate('Y-m-d H:i:s', $ranges[1])
        ];
    }

    public function maybeDeleteDuplicates() // sms campaign duplicates messages
    {
        $duplicates = fluentCrmDb()->table('fc_sms_messages')
                                   ->where('campaign_id', $this->id)
                                   ->select([fluentCrmDb()->raw('MIN(`id`) AS min_id'), 'subscriber_id', fluentCrmDb()->raw('COUNT(subscriber_id) as count')])
                                   ->groupBy('subscriber_id')
                                   ->havingRaw('COUNT(subscriber_id) > ?', [1])
                                   ->get();

        if ($duplicates->isEmpty()) {
            return $this;
        }

        $subscriberIds = [];
        $exceptIds = [];
        foreach ($duplicates as $duplicate) {
            $subscriberIds[] = $duplicate->subscriber_id;
            $exceptIds[] = $duplicate->min_id;
        }

        fluentCrmDb()->table('fc_sms_messages')
                     ->where('campaign_id', $this->id)
                     ->whereIn('subscriber_id', $subscriberIds)
                     ->whereNotIn('id', $exceptIds)
                     ->delete();

        $messageCount = $this->getMessageCount();
        if ($messageCount != $this->recipients_count) {
            $this->recipients_count = $messageCount;
            $this->save();
        }

        return $this;
    }
}
