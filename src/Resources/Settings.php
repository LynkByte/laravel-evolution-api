<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Resources;

use Lynkbyte\EvolutionApi\DTOs\ApiResponse;

/**
 * Settings management resource for Evolution API.
 *
 * @see https://doc.evolution-api.com/v1/en/api-reference/settings
 */
class Settings extends Resource
{
    /**
     * Set instance settings.
     *
     * @param array<string, mixed> $settings
     */
    public function set(array $settings): ApiResponse
    {
        $this->ensureInstance();

        return $this->post("settings/set/{instance}", $settings);
    }

    /**
     * Get instance settings.
     */
    public function find(): ApiResponse
    {
        $this->ensureInstance();

        return $this->get("settings/find/{instance}");
    }

    /**
     * Set reject calls setting.
     */
    public function setRejectCalls(bool $reject, ?string $message = null): ApiResponse
    {
        $settings = ['rejectCall' => $reject];

        if ($message !== null) {
            $settings['msgCall'] = $message;
        }

        return $this->set($settings);
    }

    /**
     * Enable auto-reject calls with message.
     */
    public function rejectCallsWithMessage(string $message): ApiResponse
    {
        return $this->setRejectCalls(true, $message);
    }

    /**
     * Disable auto-reject calls.
     */
    public function allowCalls(): ApiResponse
    {
        return $this->setRejectCalls(false);
    }

    /**
     * Set read messages setting.
     */
    public function setReadMessages(bool $read): ApiResponse
    {
        return $this->set(['readMessages' => $read]);
    }

    /**
     * Enable auto-read messages.
     */
    public function enableAutoRead(): ApiResponse
    {
        return $this->setReadMessages(true);
    }

    /**
     * Disable auto-read messages.
     */
    public function disableAutoRead(): ApiResponse
    {
        return $this->setReadMessages(false);
    }

    /**
     * Set read status setting.
     */
    public function setReadStatus(bool $read): ApiResponse
    {
        return $this->set(['readStatus' => $read]);
    }

    /**
     * Set sync full history setting.
     */
    public function setSyncFullHistory(bool $sync): ApiResponse
    {
        return $this->set(['syncFullHistory' => $sync]);
    }

    /**
     * Set groups ignore setting.
     */
    public function setGroupsIgnore(bool $ignore): ApiResponse
    {
        return $this->set(['groupsIgnore' => $ignore]);
    }

    /**
     * Ignore all group messages.
     */
    public function ignoreGroups(): ApiResponse
    {
        return $this->setGroupsIgnore(true);
    }

    /**
     * Process group messages.
     */
    public function processGroups(): ApiResponse
    {
        return $this->setGroupsIgnore(false);
    }

    /**
     * Set always online setting.
     */
    public function setAlwaysOnline(bool $online): ApiResponse
    {
        return $this->set(['alwaysOnline' => $online]);
    }

    /**
     * Enable always online mode.
     */
    public function enableAlwaysOnline(): ApiResponse
    {
        return $this->setAlwaysOnline(true);
    }

    /**
     * Disable always online mode.
     */
    public function disableAlwaysOnline(): ApiResponse
    {
        return $this->setAlwaysOnline(false);
    }

    /**
     * Configure multiple settings at once.
     *
     * @param array{
     *     rejectCall?: bool,
     *     msgCall?: string,
     *     groupsIgnore?: bool,
     *     alwaysOnline?: bool,
     *     readMessages?: bool,
     *     readStatus?: bool,
     *     syncFullHistory?: bool
     * } $options
     */
    public function configure(array $options): ApiResponse
    {
        return $this->set($options);
    }
}
