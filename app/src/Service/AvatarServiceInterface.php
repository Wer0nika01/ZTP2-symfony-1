<?php

/**
 * Avatar service interface.
 */

namespace App\Service;

use App\Entity\Avatar;
use App\Entity\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class Avatar service.
 */
interface AvatarServiceInterface
{
    /**
     * Create avatar.
     *
     * @param UploadedFile $uploadedFile Uploaded file
     * @param Avatar       $avatar       Avatar entity
     * @param User         $user         User entity
     */
    public function create(UploadedFile $uploadedFile, Avatar $avatar, User $user): void;
    public function update(UploadedFile $uploadedFile, Avatar $avatar, User $user): void;

    public function delete(Avatar $avatar): void;

}
