<?php

namespace App\controllers;

use App\entities\User;
use App\exceptions\DataValidationException;
use App\helpers\JWTHelpers;
use App\utils\encoders\JWTTokenEncoder;
use App\utils\PasswordService;
use App\validators\UserValidators;
use DateTime;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Random\RandomException;
use Slim\Exception\HttpBadRequestException;
use Slim\Psr7\Request;
use Slim\Psr7\Response;


/**
 * Class UserController
 *
 * This class handles the user-related operations such as registration, login, and activation.
 */
class UserController extends AbstractController
{

    /**
     * @param Request $request
     * @param Response $response
     * @return ResponseInterface
     * @throws DataValidationException
     * @throws Exception|RandomException
     */
    public function register(Request $request, Response $response): ResponseInterface
    {
        $userData = $request->getParsedBody();
        $userValidator = UserValidators::validateUserRegistration($userData);
        if (!$userValidator->validate()) {
            throw new DataValidationException($userValidator->errors());
        }
        list("name" => $name, "email" => $email, "password" => $password) = $userData;

        $token = bin2hex(random_bytes(16));
        $expiration = time() + 24 * 3600;

        $user = new User();
        $user
            ->setName($name)
            ->setEmail($email)
            ->setPassword(PasswordService::hashPassword($password))
            ->setActivationToken($token)
            ->setActivationTokenExpiryDate($expiration)
            ->setCreatedAt(new DateTime())
            ->setUpdatedAt(new DateTime());

        $entityManager = $this->getEntityManager();
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->JSONResponse($response, json_encode($user), 201);
    }


    /**
     * @param Request $request
     * @param Response $response
     * @return ResponseInterface
     * @throws DataValidationException
     */
    public function login(Request $request, Response $response): ResponseInterface
    {
        $userData = $request->getParsedBody();
        $userValidator = UserValidators::validateUserLogin($userData);
        if (!$userValidator->validate()) {
            throw new DataValidationException($userValidator->errors());
        }
        ["email" => $email, "password" => $password] = $userData;
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($user === null) {
            throw new HttpBadRequestException($request, 'Account not found');
        }
        if (!$user->isActive()) {
            throw new HttpBadRequestException($request, 'Account not activated');
        }

        if (!PasswordService::verifyPassword($password, $user->getPassword())) {
            throw new HttpBadRequestException($request, 'Wrong password');
        }


        $helper = new JWTHelpers(new JWTTokenEncoder());
        $accessToken = $helper->encodeToken($_ENV['JWT_SECRET'], $user->getEmail(), $_ENV['JWT_MINUTES_ACCESS_TOKEN_EXPIRY']);

        // Create refresh token with longer life
        $refreshToken = $helper->encodeToken($_ENV['JWT_SECRET'], $user->getEmail(), $_ENV['JWT_MINUTES_REFRESH_TOKEN_EXPIRY']);
        $response = $response
            ->withAddedHeader(
                'Set-Cookie',
                "accessToken=$accessToken; HttpOnly=true; Expires=" . gmdate('D, d M Y H:i:s T', strtotime('+5 minutes')) . "; Path=/"
            )
            ->withAddedHeader(
                'Set-Cookie',
                "refreshToken=$refreshToken; HttpOnly=true; Expires=" . gmdate('D, d M Y H:i:s T', strtotime('+120 minutes')) . "; Path=/"
            );
        return $this->JSONResponse($response, json_encode(['accessToken' => $accessToken, 'refreshToken' => $refreshToken]));
    }

    public function getLoggedInUser(Request $request, Response $response): ResponseInterface
    {
        $username = $request->getAttribute('username');
        if ($username === null) {
            throw new HttpBadRequestException($request, 'User not found');
        }
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => $username]);
        return $this->JSONResponse($response, json_encode($user));
    }

    /**
     * @throws Exception
     */
    public function activate(Request $request, Response $response): ResponseInterface
    {
        $token = $request->getQueryParams()['token'];
        if ($token === null) {
            throw new HttpBadRequestException($request, 'No token provided');
        }
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy(['activationToken' => $token]);
        if ($user === null) {
            throw new HttpBadRequestException($request, 'Invalid token');
        }

        //if the token has expired
        if ($user->getActivationTokenExpiryDate() < time()) {
            throw new HttpBadRequestException($request, 'Token expired');
        }

        $user->setActivationToken(null);
        $user->setActivationTokenExpiryDate(null);
        $user->setIsActive(true);

        $this->getEntityManager()->flush();

        return $this->JSONResponse($response, json_encode($user));
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function sendActivationToken(Request $request, Response $response): ResponseInterface
    {
        $email = $request->getParsedBody()['email'];
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($user === null) {
            throw new HttpBadRequestException($request, 'User not found');
        }
        if ($user->isActive()) {
            throw new HttpBadRequestException($request, 'User already activated');
        }

        $token = bin2hex(random_bytes(16));
        $expiration = time() + 24 * 3600;
        $user->setActivationToken($token);
        $user->setActivationTokenExpiryDate($expiration);

        $this->getEntityManager()->flush();

        return $this->JSONResponse($response, json_encode($user));

    }

    /**
     * @throws \Exception
     */
    public function passwordReset(Request $request, Response $response): ResponseInterface
    {
        $email = $request->getParsedBody()['email'];
        if (!$email) {
            throw new HttpBadRequestException($request, 'Email is required');
        }
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($user === null) {
            throw new HttpBadRequestException($request, 'Account not found');
        }

        if (!$user->isActive()) {
            throw new HttpBadRequestException($request, 'Account not activated');
        }
        $token = bin2hex(random_bytes(16));
        $expiration = time() + 24 * 3600;

        $user->setPasswordResetToken($token);
        $user->setPasswordResetTokenExpiryDate($expiration);
        $this->getEntityManager()->flush();

        return $this->JSONResponse($response, json_encode($user));
    }


    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return ResponseInterface
     * @throws Exception
     */
    public function passwordResetConfirm(Request $request, Response $response, array $args): ResponseInterface
    {

        $newPassword = $request->getParsedBody()['password'];
        $token = $args['token'];
        if (!$newPassword) {
            throw new HttpBadRequestException($request, 'Password not provided');
        }
        if ($token === null) {
            throw new HttpBadRequestException($request, 'No token provided');
        }
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy(['passwordResetToken' => $token]);
        if ($user === null) {
            throw new HttpBadRequestException($request, 'Invalid token');
        }
        if ($user->getPasswordResetTokenExpiryDate() < time()) {
            throw new HttpBadRequestException($request, 'Token expired');
        }

        $user->setPasswordResetToken(null);
        $user->setPasswordResetTokenExpiryDate(null);
        $user->setPassword(PasswordService::hashPassword($newPassword));
        $this->getEntityManager()->flush();

        return $this->JSONResponse($response, json_encode($user));
    }


    public function refreshToken(Request $request, Response $response): ResponseInterface
    {
        $header = $request->getHeaderLine('Cookie'); // gets the 'Cookie' header
        $cookies = [];
        parse_str(strtr($header, ['&' => '%26', '+' => '%2B', ';' => '&']), $cookies);

        if (empty($cookies['refreshToken'])) {
            throw new HttpBadRequestException($request, 'Refresh token not provided');
        }

        $helper = new JWTHelpers(new JWTTokenEncoder());

        // Verify the refresh token
        $decoded = $helper->decodeToken($cookies['refreshToken'], $_ENV['JWT_SECRET']);

        if (!$decoded) {
            throw new HttpBadRequestException($request, 'Invalid refresh token');
        }

        $email = $decoded["username"];

        // The refresh token is valid, let's issue a new access token
        $newAccessToken = $helper->encodeToken($_ENV['JWT_SECRET'], $email, $_ENV['JWT_MINUTES_ACCESS_TOKEN_EXPIRY']);

        $response = $response
            ->withHeader(
                'Set-Cookie',
                "accessToken=$newAccessToken; HttpOnly=true; Expires=" . gmdate('D, d M Y H:i:s T', strtotime('+5 minutes')) . "; Path=/"
            );

        return $this->JSONResponse($response, json_encode(['accessToken' => $newAccessToken]));
    }

    public function logout(Response $response): ResponseInterface
    {
        $response = $response
            ->withAddedHeader(
                'Set-Cookie',
                "accessToken=expired; HttpOnly=true; Expires=" . gmdate('D, d M Y H:i:s T', strtotime('-1 year')) . "; Path=/"
            )
            ->withAddedHeader(
                'Set-Cookie',
                "refreshToken=expired; HttpOnly=true; Expires=" . gmdate('D, d M Y H:i:s T', strtotime('-1 year')) . "; Path=/"
            );

        return $this->JSONResponse($response, json_encode(['status' => 'Logged out successfully']));
    }
}