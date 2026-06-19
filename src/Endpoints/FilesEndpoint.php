<?php

namespace Podio\Client\Endpoints;

use RuntimeException;

final class FilesEndpoint extends BaseEndpoint
{
    /**
     * @link https://developers.podio.com/doc/files
     */
    public function getRaw(int $fileId, ?string $size = null): string
    {
        $uri = '/file/' . $fileId . '/raw';

        if ($size !== null) {
            $uri .= '/' . $this->path($size);
        }

        return (string) $this->request('GET', $uri, ['raw' => true]);
    }

    /**
     * @link https://developers.podio.com/doc/files/upload-file-1004361
     */
    public function upload(string $filepath, string $filename): object
    {
        if (! is_readable($filepath)) {
            throw new RuntimeException('Podio file upload: source file is not readable: ' . $filepath);
        }

        $contents = file_get_contents($filepath);

        if ($contents === false) {
            throw new RuntimeException('Podio file upload: failed to read source file: ' . $filepath);
        }

        return $this->uploadContents($contents, $filename);
    }

    /**
     * @link https://developers.podio.com/doc/files/upload-file-1004361
     */
    public function uploadContents(string $contents, string $filename, ?string $contentType = null): object
    {
        return $this->request('POST', '/file/', [
            'multipart' => [
                [
                    'name' => 'source',
                    'contents' => $contents,
                    'filename' => $filename,
                    'content_type' => $contentType ?? 'application/octet-stream',
                ],
                [
                    'name' => 'filename',
                    'contents' => $filename,
                ],
            ],
        ]);
    }

    /**
     * @link https://developers.podio.com/doc/files/attach-file-22518
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $params
     */
    public function attach(int $fileId, array $data, array $params = []): void
    {
        $this->request('POST', '/file/' . $fileId . '/attach', ['json' => $data, 'query' => $params]);
    }
}
