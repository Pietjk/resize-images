<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Storage;
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
        $dir = $this->ask('Geef de bestandsmap op');
        if (!is_dir($dir)) {
            dd('Oeps, dat is geen bestandsmap :(');
        }

        $size = $this->ask('Hoe groot wil je dat de afbeelding is? (standaard 550px)');

        if (! is_numeric($size)) {
            $size = 550;
            $this->warn('De grootte is door een error naar de standaard waarde van 550px gezet');
        } else {
            $this->info("De grootte is naar $size px gezet");
            $size = (int)$size;
        }

        $quality = $this->ask('geef de gewenste kwaliteit (standaard 100%)');

        if (! is_numeric($quality) || $quality > 100 || $quality < 1) {
            $quality = 100;
            $this->warn('De kwaliteit is door een error naar de standaard waarde van 100% gezet');
        } else {
            $this->info("De grootte is naar $quality% gezet");
            $quality = (int)$quality;
        }


        $files = array_diff(scandir($dir), array('.', '..'));
        $imageTypes = ['png', 'jpg', 'jpeg', 'webp'];
        $images = [];
        foreach ($files as $file) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            if (in_array($extension, $imageTypes) ) {
                $images[] = $dir.'\\'.$file;
            }
        }

        if (empty($images)) {
            dd('Oeps, geen picjes :(');
        }

        $path = $dir.'\verkleind-'.date('dmY_His').'\\';

        if (!file_exists( $path ) && !is_dir($path)) {
            mkdir($path);
        }

        foreach ($images as $image) {
            $explodedPath = explode('\\', $image);
            $filename = end($explodedPath);
            $img = Image::make($image);
            $img->widen(720, function ($constraint) {
                $constraint->upsize();
            })->encode('jpg', $quality);
            file_put_contents($path.$filename, $img);
        }

        return Command::SUCCESS;
    }

    public function randomString()
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        return substr(str_shuffle($chars), 0, 4);
    }
}
