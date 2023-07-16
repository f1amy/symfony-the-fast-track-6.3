<?php

namespace App\Service;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;

class ImageOptimizer
{
    private const MAX_WIDTH = 200;
    private const MAX_HEIGHT = 150;

    public function __construct(
        private Imagine $imagine = new Imagine(),
    ) {
    }

    public function resize(string $filename): void
    {
        [$imageWidth, $imageHeight] = getimagesize($filename);

        $ratio = $imageWidth / $imageHeight;
        $width = self::MAX_WIDTH;
        $height = self::MAX_HEIGHT;

        if ($width / $height > $ratio) {
            $width = $height * $ratio;
        } else {
            $height = $width / $ratio;
        }

        $photo = $this->imagine->open($filename);
        $imageSize = new Box($width, $height);

        $photo->resize($imageSize)->save($filename);
    }
}
