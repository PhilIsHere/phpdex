<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class ServiceUploader implements IUploaderClass {

    private string $destination;

    public function __construct(string $uploadDir) {

        $this->destination = $uploadDir;
    }

    /**
     * @inheritDoc
     */
    public function upload(UploadedFile $file): string {
        $destination = $this->destination . "/pokemon";
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $newFilename = $originalFilename . '.' . $file->guessExtension();
        $file->move(
            $destination,
            $newFilename
        );
        return $newFilename;
    }

    /**
     * @param string $url
     * @param string $filename
     * @return void
     */
    public function uploadUrl(string $url, string $filename): string {
        $destination = $this->destination . "/pokemon/";
        $file = file_get_contents($url);
        $newFilename = $filename . '.png';
        file_put_contents($destination . $newFilename, $file);
        return $destination . $newFilename;
    }

}