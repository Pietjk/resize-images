<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
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
    public function handle(Request $request)
    {
        // Open directory
        $dir = $this->ask('Geef de bestandsmap op');
        if (!is_dir($dir)) {
            $this->error('Oeps, dat is geen bestandsmap :(');
            die();
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
            die();
        }

        // Make new directory path
        $path = $dir.'\verkleind-'.date('dmY_His').'\\';

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
     * @return Int $size
     */
    public function getSize()
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
     * @return Int $quality
     */
    public function getQuality()
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
     * @param Str $dir
     *
     * @return Arr $images
     */
    public function getImages($dir)
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        $imageTypes = ['png', 'jpg', 'jpeg', 'webp'];
        $images = [];
        foreach ($files as $file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            if (in_array($extension, $imageTypes) ) {
                $images[] = $dir.'\\'.$file;
            }
        }

        return $images;
    }

    /**
     * Resize the images
     *
     * @param Str $path
     * @param Arr $images
     * @param Int $size
     * @param Int $quality
     *
     * @return void
     */
    public function resizeImages($path, $images, $size, $quality)
    {
        if (!file_exists( $path ) && !is_dir($path)) {
            mkdir($path);
        }

        $bar = $this->output->createProgressBar(count($images));
        $bar->start();

        foreach ($images as $image) {
            $explodedPath = explode('\\', $image);
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
