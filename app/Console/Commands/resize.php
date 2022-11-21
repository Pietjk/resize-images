<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Image;

class resize extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:resize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resize all images in a given directory';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Open directory
        $dir = $this->ask('Geef de bestandsmap op');
        if (!is_dir($dir)) {
            $this->error('Oeps, dat is geen bestandsmap :(');
            return Command::FAILURE;
        }

        // Get the user requested size
        $size = $this->getSize();

        // Get the user requested quality
        $quality = $this->getQuality();

        // Get all images from directory
        $images = $this->getImages($dir);

        // Stop program when there are no images in the directory
        if (empty($images)) {
            $this->error('Oeps, het lijkt erop dat er geen afbeeldingen in deze map zitten.');
            return Command::FAILURE;
        }

        // Make new directory path
        $path = $dir.DIRECTORY_SEPARATOR.'verkleind-'.date('dmY_His').DIRECTORY_SEPARATOR;

        // Resize all images
        $this->resizeImages($path, $images, $size, $quality);

        $this->newLine();
        $this->newLine();
        $this->info('Alle afbeeldingen zijn verkleind! Je kan ze hier vinden:');
        $this->info($path);

        return Command::SUCCESS;
    }

    /**
     * Get the user requested width
     *
     * @return integer $size
     */
    public function getSize(): int
    {
        $size = $this->ask('Hoe groot wil je dat de afbeelding is? (standaard 550px)');

        if (! is_numeric($size)) {
            $size = 550;
            $this->warn('De grootte is door een error naar de standaard waarde van 550px gezet');
        } else {
            $this->info("De grootte is naar $size px gezet");
            $size = (int)$size;
        }

        return $size;
    }

    /**
     * Get the user requested quality
     *
     * @return integer $quality
     */
    public function getQuality(): int
    {

        $quality = $this->ask('geef de gewenste kwaliteit (standaard 100%)');

        if (! is_numeric($quality) || $quality > 100 || $quality < 1) {
            $quality = 100;
            $this->warn('De kwaliteit is door een error naar de standaard waarde van 100% gezet');
        } else {
            $this->info("De grootte is naar $quality% gezet");
            $quality = (int)$quality;
        }

        return $quality;
    }

    /**
     * Get the images from the given directory
     *
     * @param string $dir
     *
     * @return array $images
     */
    public function getImages($dir): array
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        $imageTypes = ['png', 'jpg', 'jpeg', 'webp'];
        $images = [];
        foreach ($files as $file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            if (in_array($extension, $imageTypes) ) {
                $images[] = $dir.DIRECTORY_SEPARATOR.$file;
            }
        }

        return $images;
    }

    /**
     * Resize the images
     *
     * @param string $path
     * @param array $images
     * @param int $size
     * @param int $quality
     *
     * @return void
     */
    public function resizeImages($path, $images, $size, $quality): void
    {
        if (!file_exists( $path ) && !is_dir($path)) {
            mkdir($path);
        }

        $bar = $this->output->createProgressBar(count($images));
        $bar->start();

        foreach ($images as $image) {
            $explodedPath = explode(DIRECTORY_SEPARATOR, $image);
            $filename = end($explodedPath);
            $img = Image::make($image);

            $img->widen($size, function ($constraint) {
                $constraint->upsize();
            })->encode('jpg', $quality);

            file_put_contents($path.$filename, $img);

            $bar->advance();
        }

        $bar->finish();
    }
}
