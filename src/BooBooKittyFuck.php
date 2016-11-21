<?php

/**
 * Ork
 *
 * @package   Ork_BooBooKittyFuck
 * @copyright 2016 Alex Howansky (https://github.com/AlexHowansky)
 * @license   https://github.com/AlexHowansky/bbkf/blob/master/LICENSE MIT License
 * @link      https://github.com/AlexHowansky/bbkf
 */

namespace Ork;

/**
 * BooBooKittyFuck (de)compiler.
 */
class BooBooKittyFuck
{

    // The BF instructions and their image file names.
    const INSTRUCTIONS = [
        '+' => 'increment',
        '-' => 'decrement',
        '<' => 'right',
        '>' => 'left',
        '.' => 'output',
        ',' => 'input',
        '[' => 'open',
        ']' => 'close',
    ];

    /**
     * The cat images
     *
     * @var array
     */
    protected $cats = [];

    /**
     * The image for this program.
     *
     * @var \imagick
     */
    protected $image = null;

    /**
     * The path to the cat images.
     *
     * @var string
     */
    protected $imagePath = null;

    /**
     * The Brainfuck source for this program.
     *
     * @var string
     */
    protected $source = null;

    /**
     * The value under which images are considered identical.
     *
     * @var float
     */
    protected $threshold = 0.01;

    /**
     * The image grid tile size.
     *
     * @var int
     */
    protected $tileSize = 128;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->setImagePath(realpath(__DIR__ . '/../images/'));
    }

    /**
     * Get the image for this program.
     *
     * @return \imagick The image for this program.
     */
    public function getImage()
    {
        if ($this->image instanceof \imagick === false) {
            throw new \RuntimeException('Image must first be set with setImage() or setSource().');
        }
        return $this->image;
    }

    /**
     * Get the path for the cat images.
     *
     * @return string The path for the cat images.
     */
    public function getImagePath()
    {
        return $this->imagePath;
    }

    /**
     * Get the Brainfuck source for this program
     *
     * @return string The Brainfuck source for this program.
     */
    public function getSource()
    {
        if ($this->source === null) {
            throw new \RuntimeException('Source must first be set with setImage() or setSource().');
        }
        return $this->source;
    }

    /**
     * Get the image comparison threshold.
     *
     * @return float The image comparison threshold.
     */
    public function getThreshold()
    {
        return $this->threshold;
    }

    /**
     * Get the image grid tile size.
     *
     * @return int The image grid tile size.
     */
    public function getTileSize()
    {
        return $this->tileSize;
    }

    /**
     * Set the image for this program.
     *
     * This will open the image file and analyze it against the
     * list of instruction images, converting to BF source.
     *
     * @param string $file The source file containing the image.
     *
     * @return BooBooKittyFuck Allow method chaining.
     */
    public function setImage($file)
    {

        if (file_exists($file) === false) {
            throw new \RuntimeException('File does not exist: ' . $file);
        }

        $this->image = new \imagick($file);
        if (
            $this->image->getImageWidth() % $this->getTileSize() !== 0 ||
            $this->image->getImageHeight() % $this->getTileSize() !== 0
        ) {
            throw new \RuntimeException('Image dimensions must be a multiple of tile size.');
        }

        $this->source = '';
        $width = $this->image->getImageWidth() / $this->getTileSize();
        $height = $this->image->getImageHeight() / $this->getTileSize();

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $tile = $this->image->clone();
                $tile->cropImage(
                    $this->getTileSize(),
                    $this->getTileSize(),
                    $x * $this->getTileSize(),
                    $y * $this->getTileSize()
                );
                foreach ($this->cats as $instruction => $cat) {
                    if ($tile->compareImages($cat, \Imagick::METRIC_MEANSQUAREERROR)[1] < $this->getThreshold()) {
                        $this->source .= $instruction;
                        break;
                    }
                }
            }
        }

        return $this;

    }

    /**
     * Set the path for the cat images.
     *
     * @param string $path The path for the cat iamges.
     *
     * @return BooBooKittyFuck Allow method chaining.
     */
    public function setImagePath($path)
    {
        if (is_dir(realpath($path)) === false) {
            throw new \RuntimeException($dir . ' is not a directory.');
        }
        $this->imagePath = rtrim($path, '/') . '/';
        foreach (self::INSTRUCTIONS as $instruction => $name) {
            $file = $this->imagePath . $name . '.jpg';
            if (file_exists($file) === false) {
                throw new \RuntimeException('Unable to find image for instruction: ' . $name);
            }
            $this->cats[$instruction] = new \imagick($this->imagePath . $name . '.jpg');
        }
        return $this;
    }

    /**
     * Set the Brainfuck source for this program.
     *
     * @param string $bf The Brainfuck source for this program.
     *
     * @return BooBooKittyFuck Allow method chaining.
     */
    public function setSource($bf)
    {

        // Try to determine if the parameter is a file name or source code.
        $regex = '/[^\\' . implode('\\', array_keys(self::INSTRUCTIONS)) . ']/';
        if (preg_match($regex, $bf) === 1 && file_exists($bf) === true) {
            $bf = file_get_contents($bf);
        }

        // Strip non-BF characters.
        $this->source = preg_replace($regex, '', $bf);

        $num = 0;
        $size = strlen($this->source);
        $xsize = ceil(sqrt($size));
        $ysize = ceil($size / $xsize);
        $montage = new \imagick();

        for ($y = 0; $y < $ysize; $y++) {
            for ($x = 0; $x < $xsize; $x++) {
                if ($num < $size) {
                    $montage->addImage($this->cats[$this->source[$num++]]);
                }
            }
        }

        $this->image = $montage->montageImage(
            new \imagickDraw(),
            $xsize . 'x' . $ysize . '+0+0',
            $this->tileSize . 'x' . $this->tileSize . '+0+0',
            \imagick::MONTAGEMODE_UNFRAME,
            '0x0+0+0'
        );
        $this->image->setImageFormat('jpg');

        return $this;

    }

    /**
     * Set the image comparison threshold.
     *
     * @param float $threshold The image comparison threshold.
     *
     * @return BooBooKittyFuck Allow method chaining.
     */
    public function setThreshold($threshold)
    {
        $this->threshold = $threshold;
        return $this;
    }

    /**
     * Set the image grid tile size.
     *
     * @param int $size The image grid tile size.
     *
     * @return BooBooKittyFuck Allow method chaining.
     */
    public function setTileSize($size)
    {
        $this->tileSize = $size;
        return $this;
    }

}
