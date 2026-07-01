<?php

namespace FluentCampaign\App\Modules\SMS\Providers;

class SMSDriverManager
{
    /** @var AbstractSMSDriver[] keyed by driver slug */
    private static array $drivers = [];

    /**
     * Register a driver. Built-in drivers are registered in SMSModule.
     * Third-party drivers hook into 'fluent_crm/register_sms_providers':
     *
     *   add_action('fluent_crm/register_sms_providers', function () {
     *       new MyCustomSMSDriver();
     *   });
     */
    public static function register(AbstractSMSDriver $driver): void
    {
        static::$drivers[$driver->getSlug()] = $driver;
    }

    /**
     * @param string $slug
     * @return AbstractSMSDriver|null
     */
    public static function getDriver(string $slug): ?AbstractSMSDriver
    {
        return static::$drivers[$slug] ?? null;
    }

    /**
     * @return AbstractSMSDriver[]
     */
    public static function getAll(): array
    {
        return static::$drivers;
    }

    /**
     * @return string[]
     */
    public static function getSlugs(): array
    {
        return array_keys(static::$drivers);
    }
}
