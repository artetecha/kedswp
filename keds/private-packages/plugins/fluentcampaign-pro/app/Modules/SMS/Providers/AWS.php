<?php

namespace FluentCampaign\App\Modules\SMS\Providers;

use FluentCampaign\App\Modules\SMS\SMSHelper;

class AWS
{
    protected static array $defaults = [
        'timeout'             => 20,
        'message_type'        => 'TRANSACTIONAL', // or 'PROMOTIONAL'
        'destination_country_params' => [],       // eg. ['IN_ENTITY_ID' => '...', 'IN_TEMPLATE_ID' => '...']
        // you can still set/override these if needed:
        // 'access_key_id'     => null,
        // 'secret_access_key' => null,
        // 'session_token'     => null,
        // 'region'            => null,
        // 'origination_identity' => null,
    ];

    /**
     * Send a text message via AWS End User Messaging (Pinpoint SMS & Voice v2).
     *
     * @param string $to   E.164 format (+8801XXXXXXXXX)
     * @param string $msg  SMS body
     * @param array  $opts Optional overrides:
     *   - access_key_id, secret_access_key, session_token
     *   - region, origination_identity, message_type, destination_country_params, timeout
     * @return array { status:int|null, body:array|string|null, error:string|null, raw:string|null }
     */
    public static function sendSMS(string $to, string $msg, array $opts = []): array
    {
        // Merge config from WP settings -> env -> defaults -> $opts
        $cfg = array_merge(
            self::configFromSettings(),
            self::configFromEnv(),
            self::$defaults,
            array_filter($opts, fn($v) => $v !== null && $v !== '')
        );

        // Validate required bits
        $required = ['region','access_key_id','secret_access_key','origination_identity'];
        foreach ($required as $k) {
            if (empty($cfg[$k])) {
                return [
                    'status' => null,
                    'body'   => null,
                    'error'  => "Missing required config: {$k}",
                    'raw'    => null,
                ];
            }
        }

        $service   = 'sms-voice';
        $host      = "sms-voice.{$cfg['region']}.amazonaws.com";
        $endpoint  = "https://{$host}";  // Just the base endpoint
        $ctype     = 'application/x-amz-json-1.0';  // CHANGED: AWS JSON protocol

        // Build payload
        $payloadArr = [
            'DestinationPhoneNumber' => $to,
            'MessageBody'            => $msg,
            'MessageType'            => strtoupper($cfg['message_type'] ?? 'TRANSACTIONAL'),
            'OriginationIdentity'    => $cfg['origination_identity'],
        ];

        if (!empty($cfg['destination_country_params']) && is_array($cfg['destination_country_params'])) {
            $payloadArr['DestinationCountryParameters'] = $cfg['destination_country_params'];
        }

        $payload = json_encode($payloadArr, JSON_UNESCAPED_SLASHES);

        // SigV4 time bits
        $amzDate   = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');

        // Canonical request
        $canonicalUri  = '/';
        $canonicalQS   = '';


        // Build headers - order matters for canonical headers (alphabetical)
        $canonicalHdrs = "content-type:{$ctype}\nhost:{$host}\nx-amz-date:{$amzDate}\nx-amz-target:PinpointSMSVoiceV2.SendTextMessage\n";
        $signedHdrs    = 'content-type;host;x-amz-date;x-amz-target';

        $headers = [
            "content-type: {$ctype}",
            "host: {$host}",
            "x-amz-date: {$amzDate}",
            "x-amz-target: PinpointSMSVoiceV2.SendTextMessage",  // CRITICAL: This tells AWS which action to call
        ];

        if (!empty($cfg['session_token'])) {
            $canonicalHdrs = "content-type:{$ctype}\nhost:{$host}\nx-amz-date:{$amzDate}\nx-amz-security-token:{$cfg['session_token']}\nx-amz-target:PinpointSMSVoiceV2.SendTextMessage\n";
            $signedHdrs    = 'content-type;host;x-amz-date;x-amz-security-token;x-amz-target';
            $headers[] = "x-amz-security-token: {$cfg['session_token']}";
        }

        $payloadHash = hash('sha256', $payload);
        $canonicalRequest = implode("\n", [
            'POST',
            $canonicalUri,
            $canonicalQS,
            $canonicalHdrs,
            $signedHdrs,
            $payloadHash,
        ]);

        // String to sign
        $credentialScope = "{$dateStamp}/{$cfg['region']}/{$service}/aws4_request";
        $algorithm = 'AWS4-HMAC-SHA256';
        $stringToSign = implode("\n", [
            $algorithm,
            $amzDate,
            $credentialScope,
            hash('sha256', $canonicalRequest),
        ]);

        // Signature
        $signature = self::signV4(
            $cfg['secret_access_key'],
            $dateStamp,
            $cfg['region'],
            $service,
            $stringToSign
        );

        $authHeader = "{$algorithm} Credential={$cfg['access_key_id']}/{$credentialScope}, SignedHeaders={$signedHdrs}, Signature={$signature}";
        $headers[] = "Authorization: {$authHeader}";

        // Fire!
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => (int) ($cfg['timeout'] ?? 20),
        ]);

        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = $raw === false ? curl_error($ch) : null;
        curl_close($ch);

        $decoded = null;
        if ($raw !== false) {
            $maybe = json_decode($raw, true);
            $decoded = (json_last_error() === JSON_ERROR_NONE) ? $maybe : $raw;
        }

        return [
            'status' => $raw === false ? null : $code,
            'body'   => $decoded,
            'error'  => $err,
            'raw'    => $raw === false ? null : $raw,
        ];
    }

    // --- config sources ---

    protected static function configFromSettings(): array
    {
        $settings  = SMSHelper::awsEumSettings() ?: [];

        $accessKey = $settings['aws_eum_access_key'] ?? '';
        $secretKey = $settings['aws_eum_secret_key'] ?? '';
        $region    = $settings['aws_eum_region'] ?? 'us-east-1';
        $from      = $settings['aws_eum_sender_id'] ?? '';

        $cfg = [
            'access_key_id'       => $accessKey ?: null,
            'secret_access_key'   => $secretKey ?: null,
            'region'              => $region ?: null,
            // In many regions, Sender ID is valid as OriginationIdentity; if you're using a number/pool, override per call.
            'origination_identity'=> $from ?: null,
        ];

        return array_filter($cfg, fn($v) => $v !== null && $v !== '');
    }

    protected static function configFromEnv(): array
    {
        // Optional: allow env fallback (nice for CLI/tests)
        return array_filter([
            'access_key_id'       => getenv('AWS_ACCESS_KEY_ID') ?: null,
            'secret_access_key'   => getenv('AWS_SECRET_ACCESS_KEY') ?: null,
            'session_token'       => getenv('AWS_SESSION_TOKEN') ?: null,
            'region'              => getenv('AWS_REGION') ?: (getenv('AWS_DEFAULT_REGION') ?: null),
        ], fn($v) => $v !== null && $v !== '');
    }

    protected static function signV4(string $secretKey, string $dateStamp, string $region, string $service, string $stringToSign): string
    {
        $kDate    = hash_hmac('sha256', $dateStamp, "AWS4{$secretKey}", true);
        $kRegion  = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        return hash_hmac('sha256', $stringToSign, $kSigning);
    }
}
