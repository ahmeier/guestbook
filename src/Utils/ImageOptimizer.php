<?php

namespace App\Utils;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;

class ImageOptimizer
{
    private const MAX_WIDTH = 200;
    private const MAX_HEIGHT = 150;

    /** @var Imagine $imagine */
    private Imagine $imagine;

    public function __construct()
    {
        $this->imagine = new Imagine();
    }

    public function resize(string $filename): void
    {
        [$iWidth, $iHeight] = getimagesize($filename);
        $ratio = $iWidth/$iHeight;
        $height = self::MAX_HEIGHT;
        $width = self::MAX_WIDTH;
        if ($width / $height > $ratio) {
            $width = $height * $ratio;
        } else {
            $height = $width / $ratio;
        }

        $photo = $this->imagine->open($filename);
        $photo->resize(new Box($width, $height));
    }
}