<?php

/**
 * Ork
 *
 * @package   Ork_BooBooKittyFuck
 * @copyright 2016-2021 Alex Howansky (https://github.com/AlexHowansky)
 * @license   https://github.com/AlexHowansky/bbkf/blob/master/LICENSE MIT License
 * @link      https://github.com/AlexHowansky/bbkf
 */

namespace Ork\BooBooKittyFuck;

/**
 * BooBooKittyFuck (de)compiler.
 */
class Compiler
{

    // The BF instructions and their image file names.
    protected const INSTRUCTIONS = [
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
     * @var array<string, \Imagick>
     */
    protected array $cats = [];

    /**
     * The image for this program.
     *
     * @var ?\Imagick
     */
    protected ?\Imagick $image = null;

    /**
     * The path to the cat images.
     *
     * @var string
     */
    protected string $imagePath;

    /**
     * The image compression quality.
     *
     * @var int
     */
    protected int $quality = 40;

    /**
     * The number of tile columns in the image.
     *
     * Defaults to square-ish if not specified.
     *
     * @var ?int
     */
    protected ?int $sizeX = null;

    /**
     * The number of tile rows in the image.
     *
     * Defaults to square-ish if not specified.
     *
     * @var ?int
     */
    protected ?int $sizeY = null;

    /**
     * The Brainfuck source for this program.
     *
     * @var ?string
     */
    protected ?string $source = null;

    /**
     * The value under which images are considered identical.
     *
     * @var float
     */
    protected float $threshold = 0.01;

    /**
     * The image grid tile size.
     *
     * @var int
     */
    protected int $tileSize = 128;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->setImagePath((string) realpath(__DIR__ . '/../images/'));
    }

    /**
     * Get the image for this program.
     *
     * @return \Imagick The image for this program.
     *
     * @throws \RuntimeException If no image has been set yet.
     */
    public function getImage(): \Imagick
    {
        if ($this->image instanceof \Imagick === false) {
            throw new \RuntimeException('Image must first be set with setImage() or setSource().');
        }
        return $this->image;
    }

    /**
     * Get the path for the cat images.
     *
     * @return string The path for the cat images.
     */
    public function getImagePath(): string
    {
        return $this->imagePath;
    }

    /**
     * Get the image compression quality.
     *
     * @return int The image compression quality.
     */
    public function getQuality(): int
    {
        return $this->quality;
    }

    /**
     * Get the number of tile columns in the image.
     *
     * @return ?int The number of tile columns in the image.
     */
    public function getSizeX(): ?int
    {
        return $this->sizeX;
    }

    /**
     * Get the number of tile rows in the image.
     *
     * @return ?int The number of tile rows in the image.
     */
    public function getSizeY(): ?int
    {
        return $this->sizeY;
    }

    /**
     * Get the Brainfuck source for this program
     *
     * @return string The Brainfuck source for this program.
     *
     * @throws \RuntimeException If no source has been set yet.
     */
    public function getSource(): string
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
    public function getThreshold(): float
    {
        return $this->threshold;
    }

    /**
     * Get the image grid tile size.
     *
     * @return int The image grid tile size.
     */
    public function getTileSize(): int
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
     * @return self Allow method chaining.
     *
     * @throws \RuntimeException If the image file does not exist.
     */
    public function setImage(string $file): self
    {

        if (file_exists($file) === false) {
            throw new \RuntimeException('File does not exist: ' . $file);
        }

        $this->image = new \Imagick($file);
        if (
            $this->image->getImageWidth() % $this->getTileSize() !== 0 ||
            $this->image->getImageHeight() % $this->getTileSize() !== 0
        ) {
            throw new \RuntimeException('Image dimensions must be a multiple of tile size.');
        }

        $this->source = '';
        $this->sizeX = $this->image->getImageWidth() / $this->getTileSize();
        $this->sizeY = $this->image->getImageHeight() / $this->getTileSize();

        for ($y = 0; $y < $this->sizeY; $y++) {
            for ($x = 0; $x < $this->sizeX; $x++) {
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
     * @return self Allow method chaining.
     *
     * @throws \RuntimeException If no image can be found for an instruction.
     */
    public function setImagePath(string $path): self
    {
        if (is_dir((string) realpath($path)) === false) {
            throw new \RuntimeException($path . ' is not a directory.');
        }
        $this->imagePath = rtrim($path, '/') . '/';
        foreach (self::INSTRUCTIONS as $instruction => $name) {
            $file = $this->imagePath . $name . '.jpg';
            if (file_exists($file) === false) {
                throw new \RuntimeException('Unable to find image for instruction: ' . $name);
            }
            $this->cats[$instruction] = new \Imagick($this->imagePath . $name . '.jpg');
        }
        return $this;
    }

    /**
     * Set the image compression quality.
     *
     * @param int $quality The image compression quality.
     *
     * @return self Allow method chaining.
     */
    public function setQuality(int $quality): self
    {
        $this->quality = (int) $quality;
        return $this;
    }

    /**
     * Set the number of tile columns in the image.
     *
     * @param int $sizeX The number of tile columns in the image.
     *
     * @return self Allow method chaining.
     */
    public function setSizeX(int $sizeX): self
    {
        $this->sizeX = $sizeX;
        return $this;
    }

    /**
     * Set the number of tile rows in the image.
     *
     * @param int $sizeY The number of tile rows in the image.
     *
     * @return self Allow method chaining.
     */
    public function setSizeY(int $sizeY): self
    {
        $this->sizeY = $sizeY;
        return $this;
    }

    /**
     * Set the Brainfuck source for this program.
     *
     * @param string $bf The Brainfuck source for this program.
     *
     * @return self Allow method chaining.
     *
     * @throws \RuntimeException If the X/Y size is not large enough to contain the required number of tiles.
     */
    public function setSource(string $bf): self
    {

        // Try to determine if the parameter is a file name or source code.
        $regex = '/[^\\' . implode('\\', array_keys(self::INSTRUCTIONS)) . ']/';
        if (preg_match($regex, $bf) === 1 && file_exists($bf) === true) {
            $bf = (string) file_get_contents($bf);
        }

        // Strip non-BF characters.
        $this->source = (string) preg_replace($regex, '', $bf);
        $num = 0;
        $montage = new \Imagick();

        $size = strlen($this->source);
        if (empty($this->sizeX) === true) {
            if (empty($this->sizeY) === true) {
                $this->sizeX = (int) ceil(sqrt($size));
            } else {
                $this->sizeX = (int) ceil($size / $this->sizeY);
            }
        }
        if (empty($this->sizeY) === true) {
            $this->sizeY = (int) ceil($size / $this->sizeX);
        }
        if ($this->sizeX * $this->sizeY < $size) {
            throw new \RuntimeException(
                'sizeX and sizeY are not large enough to contain the required number of tiles.'
            );
        }

        for ($y = 0; $y < $this->sizeY; $y++) {
            for ($x = 0; $x < $this->sizeX; $x++) {
                if ($num < $size) {
                    $montage->addImage($this->cats[$this->source[$num++]]);
                }
            }
        }

        $this->image = $montage->montageImage(
            new \ImagickDraw(),
            $this->sizeX . 'x' . $this->sizeY . '+0+0',
            $this->tileSize . 'x' . $this->tileSize . '+0+0',
            \Imagick::MONTAGEMODE_UNFRAME,
            '0x0+0+0'
        );
        $this->image->setImageFormat('jpg');
        $this->image->setImageCompressionQuality($this->quality);

        return $this;

    }

    /**
     * Set the image comparison threshold.
     *
     * @param float $threshold The image comparison threshold.
     *
     * @return self Allow method chaining.
     */
    public function setThreshold(float $threshold): self
    {
        $this->threshold = $threshold;
        return $this;
    }

    /**
     * Set the image grid tile size.
     *
     * @param int $size The image grid tile size.
     *
     * @return self Allow method chaining.
     */
    public function setTileSize(int $size): self
    {
        $this->tileSize = $size;
        return $this;
    }

}
