<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface IUploaderClass {
    /**
     * @param UploadedFile $file
     * @return string
     */
    public function upload(UploadedFile $file): string;

    /**
     * @param string $url
     * @param string $filename
     * @return void
     */
    public function uploadUrl(string $url, string $filename): string;

}