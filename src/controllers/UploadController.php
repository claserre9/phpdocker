<?php

namespace App\controllers;

use App\entities\User;
use App\helpers\UploaderHelpers;
use App\utils\uploaders\LocalUploader;
use DateTime;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

/**
 * Class UploadController
 *
 * This class handles the upload functionality for files.
 */
class UploadController extends AbstractController
{
    public function upload(Request $request, Response $response): ResponseInterface
    {
        $location = "uploads";
        $serverRoot = $request->getServerParams()["DOCUMENT_ROOT"] . DIRECTORY_SEPARATOR . "public/$location";
        $file = $request->getUploadedFiles()['file'];
        $uploaderHelpers = new UploaderHelpers(new LocalUploader());

        $url = $uploaderHelpers->upload($serverRoot, $file);
        // fetch the user
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => $request->getAttribute('username')]);

        if (!$user) {
            throw new HttpUnauthorizedException($request, 'Not authorized');
        }
        $user->setProfilePicture($url);
        $user->setUpdatedAt(new DateTime());
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
        return $this->JSONResponse($response, json_encode(["url" => $url]));
    }

}