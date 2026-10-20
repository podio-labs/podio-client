<?php

namespace Podio\Client\Endpoints;

use Podio\Client\Resources\User;
use Podio\Client\Resources\UserStatus;

final class UsersEndpoint extends BaseEndpoint
{
    /**
     * @link https://developers.podio.com/doc/users/get-user-22378
     */
    public function get(): User
    {
        return new User($this->request('GET', '/user/'));
    }

    /**
     * @link https://developers.podio.com/doc/users/get-user-status-22480
     */
    public function getStatus(): UserStatus
    {
        return new UserStatus($this->request('GET', '/user/status'));
    }
}
