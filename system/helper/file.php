<?php

namespace Opencart\Admin\Model\Tool;

class FileHelper {
    public $base_dir;

    public function __construct($base_dir = false){
        if($base_dir != false){
            $this->base_dir = $base_dir;
        }
        else {
            $this->base_dir = dirname(__DIR__).'/';
        }
    }

    public function file_force_contents($dir, $contents){
        $dir = str_replace("\\", "/", $dir);
        $parts = explode('/', $dir);
        $file = array_pop($parts);
        $dir = $this->base_dir;
        foreach($parts as $part){
            if(!is_dir($dir .= "/$part")) {
                mkdir($dir, 0777);
            }
        }
        file_put_contents("$dir/$file", $contents);
    }

    public function file_force_copy($dir, $source, $name){
        $dir = str_replace("\\", "/", $dir);
        $parts = explode('/', $dir);
        $dir = $this->base_dir;
        foreach($parts as $part){
            if(!is_dir($dir .= "/$part")) {
                mkdir($dir, 0777);
            }
        }
        copy($source, $dir.'/'.$name);
    }

    public function delete_file($dir){
        if(!unlink($dir)) {
            echo 'Error delete file at : '. $dir;
        }
    }

    public function scan_file_types(string $dir, array $types):array {
        $result = array();
        $ffs = scandir($dir);

        unset($ffs[array_search('.', $ffs, true)]);
        unset($ffs[array_search('..', $ffs, true)]);

        // prevent empty ordered elements
        if (count($ffs) < 1)
            return [];

        foreach($ffs as $ff){
            $type = $this->file_type($ff);
            if(in_array($type, $types, true)){
                $result[] = $dir.'/'.$ff;
            }
            if(is_dir($dir.'/'.$ff)) {
                $child = $this->scan_file_types($dir.'/'.$ff, $types);
                $result = $result + $child;
            }
        }
        return $result;
    }

    public function file_type(string $dir):string {
        return strtolower(pathinfo($dir,PATHINFO_EXTENSION));
    }

    public function drop_extension(string $dir):string  {
        $ext = pathinfo($dir,PATHINFO_EXTENSION);
        if($ext){
            return str_replace(".$ext", "", $dir);
        }
        return $dir;
    }

    public function file_name(string $dir):string {
        $base_name = pathinfo($dir,PATHINFO_BASENAME);
        $ext = pathinfo($dir,PATHINFO_EXTENSION);
        if($ext){
            return str_replace(".$ext", "", $base_name);
        }
        return $base_name;
    }

    public function file_name_with_type(string $dir):string {
        return pathinfo($dir,PATHINFO_BASENAME);
    }
}