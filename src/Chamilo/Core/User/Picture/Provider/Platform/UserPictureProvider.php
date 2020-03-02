<?php
namespace Chamilo\Core\User\Picture\Provider\Platform;

use Chamilo\Core\User\Picture\UserPictureProviderInterface;
use Chamilo\Core\User\Storage\DataClass\User;
use DateTime;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The default user picture provider
 *
 * @author Sven Vanpoucke - Hogeschool Gent
 */
class UserPictureProvider implements UserPictureProviderInterface
{

    /**
     * Downloads the user picture
     *
     * @param User $targetUser
     * @param User $requestUser
     *
     * @throws \Exception
     */
    public function downloadUserPicture(User $targetUser, User $requestUser)
    {
        $file = $targetUser->get_full_picture_path();

        $type = exif_imagetype($file);
        $mime = image_type_to_mime_type($type);
        $size = filesize($file);

        $response = new StreamedResponse();
        $response->headers->add(array('Content-Type' => $mime, 'Content-Length' => $size));
        $response->setPublic();
        $response->setMaxAge(3600 * 24); // 24 hours cache

        $lastModifiedDate = new DateTime();
        $lastModifiedDate->setTimestamp(filemtime($file));

        $response->setLastModified($lastModifiedDate);
        $response->setCallback(
            function () use ($file) {
                readfile($file);
            }
        );

        $response->send();

        exit();
    }

    /**
     * Downloads the user picture
     *
     * @param User $targetUser
     * @param User $requestUser
     *
     * @return string
     */
    public function getUserPictureAsBase64String(User $targetUser, User $requestUser)
    {
        $file = $targetUser->get_full_picture_path();

        $type = exif_imagetype($file);
        $mime = image_type_to_mime_type($type);

        $fileResource = fopen($file, "r");
        $imageBinary = fread($fileResource, filesize($file));
        $imgString = base64_encode($imageBinary);

        fclose($fileResource);

        return 'data:' . $mime . ';base64,' . $imgString;
    }
}
