<?php

class jwtKeyGen{
    private $key = "";
    private $keyLength = 342;
    private $str = 'azertyuiopqsdfghjklmwxcvbn-_AZERTYUIOPQSDFGHJKLMWXCVBN';
    private $arr = [];
    
    function __construct(){
        $this->arr = str_split($this->str);
        $this->generateKey();
        $this->replaceKeyInFile('.env');
        $this->replaceKeyInFile('.env.test');
    }

    function generateKey(){
        for($i = 0; $i < $this->keyLength; $i++){
            $rand = rand(0, strlen($this->str)-1);
            $char = $this->arr[$rand];
            $this->key = $this->key.$char;
        }
    }
    
    function replaceKeyInFile($fileName){
        $data = file($fileName); // reads an array of lines
        $file = fopen($fileName, 'w') or die("Unable to open file!");
        for($i = 0; $i < sizeof($data); $i++){
            if(strpos($data[$i], 'JWT_KEY')!== false){
                $data[$i] = 'JWT_KEY='.$this->key."\n";
            }
        }
        fwrite($file, implode("", $data));
    }
}

new jwtKeyGen;