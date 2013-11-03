<?php
/*
 * 
 * @author https://github.com/beyondpeng/iphoto
 *
 */
class Imghash{
	
	private static $_instance = null;
	
	public $rate = 1;
	
	public static function getInstance(){
		if (self::$_instance === null){
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	public function run($file){
		if (!function_exists('imagecreatetruecolor')){
			throw new Exception('must load gd lib', 1);
		}
		$isString = false;
		if (is_string($file)){
			$file = array($file);
			$isString = true;
		}
		$result = array();
		foreach ($file as $f){
			$result[] = $this->hash($f);
		}
		return $isString ? $result[0] : $result;
	}
	public function checkIsSimilarImg($imgHash, $otherImgHash){
		if ((file_exists($imgHash)||@fopen( $imgHash, 'r' )) && (file_exists($otherImgHash)||@fopen( $otherImgHash, 'r' ))){
			$imgHash = $this->run($imgHash);
			$otherImgHash = $this->run($otherImgHash);
		}
		if (strlen($imgHash) !== strlen($otherImgHash)) return false;
		$count = levenshtein($imgHash,$otherImgHash);
		return $count <= (5 * $this->rate * $this->rate) ? true : false;
	}
	public function hash($file){
		if ((!file_exists($file))&&(!@fopen( $file, 'r' ))){
			return false;
		}
		$height = 8 * $this->rate;
		$width = 8 * $this->rate;
		$img = imagecreatetruecolor($width, $height);
		list($w, $h, $ext) = getimagesize($file);
		$source = NULL;
		switch ($ext){
			case '3' : $source = imagecreatefrompng($file);break;
			case '2' : $source = imagecreatefromjpeg($file);break;
			case '1' : $source = imagecreatefromgif($file);
		}
		imagecopyresampled($img, $source, 0, 0, 0, 0, $width, $height, $w, $h);
		$value = $this->getHashValue($img);
		imagedestroy($img);
		return $value;
	}
	public function getHashValue($img){
		$width = imagesx($img);
		$height = imagesy($img);
		$total = 0;
		$array = array();
		for ($y=0;$y<$height;$y++){
			for ($x=0;$x<$width;$x++){
				$rgb = imagecolorat($img, $x, $y);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
				$gray = ($r*306 + $g*601 + $b*117) >> 10;
				$array[$y][$x] = $gray;
				$total += $gray;
			}
		}
		$average = intval($total / (64 * $this->rate * $this->rate));
		$result = '';
		for ($y=0;$y<$height;$y++){
			for ($x=0;$x<$width;$x++){
				if ($array[$y][$x] >= $average){
					$result .= '1';
				}else{
					$result .= '0';
				}
			}
		}
		return $result;
	}
}