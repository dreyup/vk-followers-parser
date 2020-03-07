<?php

namespace App\Service;

use VK\Client\VKApiClient;
use VK\Exceptions\VKApiException;
use VK\Exceptions\VKClientException;

/**
 * Class VkAPIWrapper
 * @package App\Service
 * @author  Andrey Zaytsev <dreyup96@gmail.com>
 */
class VkApiClientWrapper
{
    private const API_VERSION = '5.103';

    private const LIMIT = 1000;

    /**
     * @var VKApiClient
     */
    private $api;

    /**
     * @var string
     */
    private $accessToken;

    /**
     * @var string
     */
    private $getFollowersCode = '
var limit  = {limit};
var offset = {offset};
var maxCount  = offset + 25000;
var followers = [];

while (offset < maxCount) {
    followers = followers + (API.users.getFollowers({"user_id": {userId}, "v": "{apiVersion}", "count": limit, "offset": offset}).items);
    offset    = limit + offset;
}
return followers;';

    /**
     * VkApiClientWrapper constructor.
     * @param string $accessToken
     */
    public function __construct(string $accessToken)
    {
        $this->api         = new VKApiClient();
        $this->accessToken = $accessToken;
    }

    /**
     * Get count of user followers by id from Vk API
     *
     * @param int $userId
     * @return int
     * @throws VKApiException
     * @throws VKClientException
     */
    public function getFollowersCount(int $userId) :int
    {
        $response = $this->api->users()->get(
            $this->accessToken,
            [
                'user_id' => $userId,
                'fields' => 'followers_count'
            ]
        );

        return $response[0]['followers_count'] ?? 0;
    }

    /**
     * Get user followers by id from Vk API
     *
     * @param int $userId
     * @return mixed
     * @throws VKApiException
     * @throws VKClientException
     */
    public function getFollowers(int $userId)
    {
        $followers      = [];
        $followersCount = $this->getFollowersCount($userId);

        while (count($followers) < $followersCount) {
            $response = $this->api->getRequest()->post('execute', $this->accessToken, [
                'v'    => self::API_VERSION,
                'code' => str_replace(
                    ['{userId}', '{apiVersion}', '{limit}', '{offset}'],
                    [$userId, self::API_VERSION, self::LIMIT, count($followers)],
                    $this->getFollowersCode
                ),
            ]);

            $followers = array_merge($followers, $response);
        }

        return $followers;
    }
}
