<?php

namespace Podio\Client\Endpoints;

final class HooksEndpoint extends BaseEndpoint
{
    /**
     * @link https://developers.podio.com/doc/hooks/get-hooks-215285
     *
     * @return array<int, object>
     */
    public function getForApp(int $appId): array
    {
        $hooks = $this->request('GET', '/hook/app/' . $appId . '/');

        return is_array($hooks) ? $hooks : [];
    }

    /**
     * @link https://developers.podio.com/doc/hooks/create-hook-215056
     *
     * @param  array<string, mixed>  $data
     */
    public function createForApp(int $appId, array $data): object
    {
        return $this->request('POST', '/hook/app/' . $appId . '/', ['json' => $data]);
    }

    /**
     * @link https://developers.podio.com/doc/hooks/delete-hook-215291
     */
    public function delete(int $hookId): void
    {
        $this->request('DELETE', '/hook/' . $hookId);
    }

    /**
     * @link https://developers.podio.com/doc/hooks/request-hook-verification-215232
     */
    public function verify(int $hookId): void
    {
        $this->request('POST', '/hook/' . $hookId . '/verify/request');
    }

    /**
     * @link https://developers.podio.com/doc/hooks/validate-hook-verification-215241
     */
    public function validate(int $hookId, string $code): void
    {
        $this->request('POST', '/hook/' . $hookId . '/verify/validate', [
            'json' => ['code' => $code],
        ]);
    }
}
