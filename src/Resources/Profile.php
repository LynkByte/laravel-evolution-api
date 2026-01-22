<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\Resources;

use Lynkbyte\EvolutionApi\DTOs\ApiResponse;

/**
 * Profile management resource for Evolution API.
 *
 * @see https://doc.evolution-api.com/v1/en/api-reference/profile
 */
class Profile extends Resource
{
    /**
     * Get the profile name.
     */
    public function getName(): ApiResponse
    {
        $this->ensureInstance();

        return $this->get('profile/fetchProfile/{instance}');
    }

    /**
     * Update the profile name.
     */
    public function updateName(string $name): ApiResponse
    {
        $this->ensureInstance();

        return $this->post('profile/updateProfileName/{instance}', [
            'name' => $name,
        ]);
    }

    /**
     * Get the profile status (about).
     */
    public function getStatus(): ApiResponse
    {
        $this->ensureInstance();

        return $this->get('profile/fetchProfile/{instance}');
    }

    /**
     * Update the profile status (about).
     */
    public function updateStatus(string $status): ApiResponse
    {
        $this->ensureInstance();

        return $this->post('profile/updateProfileStatus/{instance}', [
            'status' => $status,
        ]);
    }

    /**
     * Get the profile picture.
     */
    public function getPicture(): ApiResponse
    {
        $this->ensureInstance();

        return $this->get('profile/fetchProfilePicture/{instance}');
    }

    /**
     * Update the profile picture.
     *
     * @param  string  $image  Base64 encoded image or URL
     */
    public function updatePicture(string $image): ApiResponse
    {
        $this->ensureInstance();

        return $this->post('profile/updateProfilePicture/{instance}', [
            'picture' => $image,
        ]);
    }

    /**
     * Remove the profile picture.
     */
    public function removePicture(): ApiResponse
    {
        $this->ensureInstance();

        return $this->delete('profile/removeProfilePicture/{instance}');
    }

    /**
     * Get privacy settings.
     */
    public function getPrivacySettings(): ApiResponse
    {
        $this->ensureInstance();

        return $this->get('profile/fetchPrivacySettings/{instance}');
    }

    /**
     * Update privacy settings.
     *
     * @param  array<string, string>  $settings  Privacy settings
     *                                           Keys: readreceipts, profile, status, online, last, groupadd
     *                                           Values: all, contacts, contact_blacklist, none
     */
    public function updatePrivacySettings(array $settings): ApiResponse
    {
        $this->ensureInstance();

        return $this->post('profile/updatePrivacySettings/{instance}', $settings);
    }

    /**
     * Set read receipts privacy.
     *
     * @param  string  $value  all, none
     */
    public function setReadReceiptsPrivacy(string $value): ApiResponse
    {
        return $this->updatePrivacySettings(['readreceipts' => $value]);
    }

    /**
     * Set profile picture privacy.
     *
     * @param  string  $value  all, contacts, contact_blacklist, none
     */
    public function setProfilePicturePrivacy(string $value): ApiResponse
    {
        return $this->updatePrivacySettings(['profile' => $value]);
    }

    /**
     * Set status privacy.
     *
     * @param  string  $value  all, contacts, contact_blacklist, none
     */
    public function setStatusPrivacy(string $value): ApiResponse
    {
        return $this->updatePrivacySettings(['status' => $value]);
    }

    /**
     * Set online status privacy.
     *
     * @param  string  $value  all, match_last_seen
     */
    public function setOnlinePrivacy(string $value): ApiResponse
    {
        return $this->updatePrivacySettings(['online' => $value]);
    }

    /**
     * Set last seen privacy.
     *
     * @param  string  $value  all, contacts, contact_blacklist, none
     */
    public function setLastSeenPrivacy(string $value): ApiResponse
    {
        return $this->updatePrivacySettings(['last' => $value]);
    }

    /**
     * Set group add privacy.
     *
     * @param  string  $value  all, contacts, contact_blacklist
     */
    public function setGroupAddPrivacy(string $value): ApiResponse
    {
        return $this->updatePrivacySettings(['groupadd' => $value]);
    }

    /**
     * Get full profile info.
     */
    public function fetch(): ApiResponse
    {
        $this->ensureInstance();

        return $this->get('profile/fetchProfile/{instance}');
    }

    /**
     * Get business profile.
     */
    public function getBusinessProfile(): ApiResponse
    {
        $this->ensureInstance();

        return $this->get('profile/fetchBusinessProfile/{instance}');
    }

    /**
     * Update business profile.
     *
     * @param  array<string, mixed>  $profile  Business profile data
     */
    public function updateBusinessProfile(array $profile): ApiResponse
    {
        $this->ensureInstance();

        return $this->post('profile/updateBusinessProfile/{instance}', $profile);
    }
}
