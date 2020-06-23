<?php
class Thumbs 
{
	public function __construct($filename)
	{
		$this->filename = $filename;
		if (empty($this->filename)) {
			throw new Exception('Файл не найден');
		} else {
			$info = getimagesize($this->filename);
			if (empty($info)) {
				throw new Exception('Файл не найден');
			} else {
				$this->width  = $info[0];
				$this->height = $info[1];
				$this->type   = $info[2];	
				switch ($this->type) { 
					case 1: 
						$this->img = imageCreateFromGif($this->filename);
						break;					
					case 2: 	
						$this->img = imageCreateFromJpeg($this->filename); 
						break;	
					case 3:
						$this->img = imageCreateFromPng($this->filename); 	
						imageAlphaBlending($this->img, true);
						imageSaveAlpha($this->img, true);
						break;
					case 18: 
						$this->img = imageCreatefromWebp($this->filename);
						break;
					default:
						throw new Exception('Формат файла не подерживается');
						break;
				}
			}
		}
	}

	public function fixWebpBlue()
	{
	    $f_im = imagecreatetruecolor(imagesx($this->img),imagesy($this->img));
	    $c = imagecolorallocate($f_im, 255, 255, 255);
	    imagefill($f_im, 0, 0, $c);
	
	    for($y=0;$y<imagesy($this->img);$y++)
	    {
	        for($x=0;$x<imagesx($this->img);$x++)
	        {
	            $rgb_old = imagecolorat($this->img , $x, $y);
	            $r = ($rgb_old >> 24) & 0xFF;
	            $g = ($rgb_old >> 16) & 0xFF;
	            $b = ($rgb_old >> 8) & 0xFF;
	            $pixelcolor = imagecolorallocate($f_im, $r, $g, $b);
	            imagesetpixel($f_im, $x, $y, $pixelcolor);
	        }
	    }
	    $this->img = $f_im;
	}

	/**
	 * Изминение размера изображения.
	 */
	public function resize($width, $height)
	{
		if (empty($width)) {
			$width = ceil($height / ($this->height / $this->width));
		}
		if (empty($height)) {
			$height = ceil($width / ($this->width / $this->height));
		}

		$tmp = imageCreateTrueColor($width, $height);
		if ($this->type == 1 || $this->type == 3) {
			imagealphablending($tmp, true); 
			imageSaveAlpha($tmp, true);
			$transparent = imagecolorallocatealpha($tmp, 0, 0, 0, 127); 
			imagefill($tmp, 0, 0, $transparent); 
			imagecolortransparent($tmp, $transparent);	
		}	 

		if ($width < $this->width || $height < $this->height) {
			$tw = ceil($height / ($this->height / $this->width));
			if ($tw < $width) {
				imageCopyResampled($tmp, $this->img, ceil(($width - $tw) / 2), ceil(($height - $height) / 2), 0, 0, $tw, $height, $this->width, $this->height);		
			} else {
				$th = ceil($width / ($this->width / $this->height));
				imageCopyResampled($tmp, $this->img, ceil(($width - $width) / 2), ceil(($height - $th) / 2), 0, 0, $width, $th, $this->width, $this->height);	
			}			
		} else {	
			imageCopyResampled($tmp, $this->img, 0, 0, 0, 0, $width, $height, $this->width, $this->height);
		}

		$this->img = $tmp;
		unset($tmp);

		$this->width = $width;
		$this->height = $height;
	}

	/**
	 * Уменьшить изображение, ели оно больше указанных размеров.
	 */
	public function reduce($max_width, $max_height)
	{
		if (empty($max_width) && empty($max_height)) {
			throw new Exception('Не указан размер изображения');
		} elseif (empty($max_width)) {
			$max_width = ceil($max_height / ($this->height / $this->width));
		} elseif (empty($max_height)) {
			$max_height = ceil($max_width / ($this->width / $this->height));
		}

		if ($this->width > $max_width || $this->height > $max_height) {
			$width = ceil($max_height / ($this->height / $this->width));
			$height = ceil($max_width / ($this->width / $this->height));	
			
			if ($width > $max_width) {
				$width = $max_width;
			} else {
				$height = $max_height;
			}

			return $this->resize($width, $height);
		}
	}
	
	/**
	 * Изменение размера холста
	 */
	public function resizeCanvas($width, $height, $bg = array())
	{
		if (empty($width)) {
			$width = ceil($height / ($this->height / $this->width));
		}
		if (empty($height)) {
			$height = ceil($width / ($this->width / $this->height));
		}

		$tw = ceil($height / ($this->height / $this->width));
		$th = ceil($width / ($this->width / $this->height));

		$tmp = imageCreateTrueColor($width, $height);

		if (!empty($bg)) {
			$bgc = imagecolorallocate($tmp, $bg[0], $bg[1], $bg[2]);
			imagefilledrectangle($tmp, 0, 0, $width, $height, $bgc);					
		} else {
			imagealphablending($tmp, true); 
			imageSaveAlpha($tmp, true);
			$transparent = imagecolorallocatealpha($tmp, 0, 0, 0, 127); 
			imagefill($tmp, 0, 0, $transparent); 
			imagecolortransparent($tmp, $transparent);				
		}

		if ($width >= $this->width && $height >= $this->height) {
			$seze = array(ceil(($width - $this->width) / 2), ceil(($height - $this->height) / 2), $this->width, $this->height);
		} elseif ($width >= $this->width) {
			$seze = array(ceil(($width - $tw) / 2), 0, ceil($height / ($this->height / $this->width)), $height);
		} elseif ($height >= $this->height) {
			$seze = array(0, ceil(($height - $th) / 2), $width, ceil($width / ($this->width / $this->height)));
		} elseif ($tw < $width) {
			$seze = array(ceil(($width - $tw) / 2), ceil(($height - $height) / 2), $tw, $height);		
		} else {
			$seze = array(0, ceil(($height - $th) / 2), $width, $th);	
		} 

		imageCopyResampled($tmp, $this->img, $seze[0], $seze[1], 0, 0, $seze[2], $seze[3], $this->width, $this->height);
		$this->img = $tmp;
		unset($tmp);
		
		$this->width = $width;
		$this->height = $height;
	}
	
	/**
	 * Вырезать часть изображения
	 */
	public function crop($x, $y, $width, $height)
	{
		if (empty($height)) {
			$height = $width;
		}
		if (empty($width)) {
			$width = $height;
		}
		
		$tmp = imageCreateTrueColor($width, $height);
		if ($this->type == 1 || $this->type == 3) {
			imagealphablending($tmp, true); 
			imageSaveAlpha($tmp, true);
			$transparent = imagecolorallocatealpha($tmp, 0, 0, 0, 127); 
			imagefill($tmp, 0, 0, $transparent); 
			imagecolortransparent($tmp, $transparent);	
		}

		imageCopyResampled($tmp, $this->img, 0, 0, $x, $y, $this->width, $this->height, $this->width, $this->height);

		$this->img = $tmp;
		unset($tmp);

		$this->width = $width;
		$this->height = $height;
	}

	/**
	 * Получение основной части изображения.
	 */
	public function cut($width, $height)
	{
		if (empty($width)) {
			$width = ceil($height / ($this->height / $this->width));
		}
		if (empty($height)) {
			$height = ceil($width / ($this->width / $this->height));
		}	
		
		if ($this->width != $width && $this->height != $height) {
			$tw = ceil($height / ($this->height / $this->width));
			$th = ceil($width / ($this->width / $this->height));

			if ($this->width == $this->height) {
				// Источник - квадратная фотка
				if ($width == $height) {
					// Превью - квадратная.
					$this->resize($width, $height);
				} elseif ($width > $height) {
					// Превью - горизонтальная.
					$this->resize($width, $width);
					$this->crop(0, ceil(($this->height - $height) / 2), $width, $height);				
				} else {
					// Превью - вертикальная.
					$this->resize($height, $height);
					$this->crop(ceil(($this->width - $width) / 2), 0, $width, $height);
				}
			} elseif ($this->width > $this->height) {
				// Источник - горизонтальная фотка
				if ($width == $height) {
					// Превью - квадратная.
					$this->resize(0, $height);
					$this->crop(ceil(($this->width - $width) / 2), 0, $width, $height);					
				} elseif ($width > $height) {
					// Превью - горизонтальная.
					if ($width <= $tw) {
						$this->resize(0, $height);
						$this->crop(ceil(($this->width - $width) / 2), 0, $width, $height);
					} else {
						$this->resize($width + 1, 0);
						$this->crop(0, ceil(($this->height - $height) / 2), $width, $height);
					}
				} else {
					// Превью - вертикальная.
					$this->resize(0, $height);
					$this->crop(ceil(($this->width - $width) / 2), 0, $width, $height);					
				}
			} else {
				// Источник - вертикальная фотка
				if ($width == $height) {
					// Превью - квадратная.
					$this->resize($width, 0);
					$this->crop((ceil($this->width - $width) / 2), ceil((($this->height - $height) / 2) / 2), $width, $height);
				} elseif ($width > $height) {
					// Превью - горизонтальная.
					$this->resize($width, 0);
					$this->crop(0, ceil((($this->height - $height) / 2) / 3), $width, $height);
				} else {
					// Превью - вертикальная.
					if ($tw > $width) {
						$this->resize(0, $height);
						$this->crop(ceil(($this->width - $width) / 2), 0, $width, $height);
					} else {
						$this->resize(0, $th);
						$this->crop(ceil(($this->width - $width) / 2), 0, $width, $height);
					}
				}
			}

			$this->width = $width;
			$this->height = $height;
		}

		return true;
	}

	/**
	 * Превью.
	 */
	public function thumb($width, $height)
	{
		if (empty($width)) {
			$width = ceil($height / ($this->height / $this->width));
		}
		if (empty($height)) {
			$height = ceil($width / ($this->width / $this->height));
		}		
	
		$tw = ceil($height / ($this->height / $this->width));
		$th = ceil($width / ($this->width / $this->height));

		if ($this->width != $width && $this->height != $height) {
			$tmp = imageCreateTrueColor($width, $height);
			if ($this->type == 1 || $this->type == 3) {
				imagealphablending($tmp, true); 
				imageSaveAlpha($tmp, true);
				$transparent = imagecolorallocatealpha($tmp, 0, 0, 0, 127); 
				imagefill($tmp, 0, 0, $transparent); 
				imagecolortransparent($tmp, $transparent);
			}

			// Определение цвета фона...
			$entry_1 = $entry_2 = array();
			for ($n = 0; $n < $this->width; $n++) {
				$color = imagecolorat($this->img, $n, 0);
				$entry_1[$color] = (isset($entry_1[$color])) ? $entry_1[$color] + 1 : 1;
				
				$color = imagecolorat($this->img, $n, $this->height - 1);
				$entry_2[$color] = (isset($entry_2[$color])) ? $entry_2[$color] + 1 : 1;
			}

			arsort($entry_1); 
			$top_color = key($entry_1);
			$top_defined = (100 * $entry_1[$top_color] / $this->width) > 45;	

			arsort($entry_2); 
			$bottom_color = key($entry_2);
			$bottom_defined = (100 * $entry_2[$bottom_color] / $this->width) > 45;			
			unset($entry_1, $entry_2);
	
			for ($n = 0; $n < $this->height; $n++) {
				$color = imagecolorat($this->img, 0, $n);
				$entry_1[$color] = (isset($entry_1[$color])) ? $entry_1[$color] + 1 : 1;
				
				$color = imagecolorat($this->img, $this->width - 1, $n);
				$entry_2[$color] = (isset($entry_2[$color])) ? $entry_2[$color] + 1 : 1;
			}

			arsort($entry_1); 
			$left_color = key($entry_1);
			$left_defined = (100 * $entry_1[$left_color] / $this->height) > 45;		
			
			arsort($entry_2); 
			$right_color = key($entry_2);
			$right_defined = (100 * $entry_2[$right_color] / $this->height) > 45;
			unset($entry_1, $entry_2);
	
			if ($top_defined || $bottom_defined || $right_defined || $left_defined) {
				if (imageistruecolor($this->img)) {
					$top_rgb    = imagecolorsforindex($tmp, $top_color);
					$right_rgb  = imagecolorsforindex($tmp, $right_color);
					$bottom_rgb = imagecolorsforindex($tmp, $bottom_color);
					$left_rgb   = imagecolorsforindex($tmp, $left_color);				
					
					if (!empty($top_rgb['alpha']) && !empty($top_rgb['alpha']) && !empty($top_rgb['alpha']) && !empty($top_rgb['alpha'])) {	
						$top_defined = $right_defined = $bottom_defined = $left_defined = true;
					} elseif (empty($top_rgb['alpha']) || empty($top_rgb['alpha']) || empty($top_rgb['alpha']) || empty($top_rgb['alpha'])) {			
						if ($top_defined && $bottom_defined && $left_defined == false && $right_defined == false) {
							if ($th < $height) {
								imagefilledrectangle($tmp, 0, 0, $width - 1, $height / 2, $top_color);
								imagefilledrectangle($tmp, 0, $height / 2, $width - 1, $height - 1, $bottom_color);							
							} else {
								return $this->cut($width, $height);
							}
						} elseif ($left_defined && $right_defined && $top_defined == false && $bottom_defined == false) {
							if ($tw < $width) {
								imagefilledrectangle($tmp, 0, 0, $width / 2, $height - 1, $left_color);
								imagefilledrectangle($tmp, $width / 2, 0, $width - 1, $height - 1, $right_color);
							} else {
								return $this->cut($width, $height);
							}
						} else {
							if ($top_defined) {
								imagefill($tmp, 0, 0, $top_color);
							} elseif($right_defined) {
								imagefill($tmp, 0, 0, $right_color);
							} elseif($bottom_defined) {
								imagefill($tmp, 0, 0, $bottom_color);
							} else {
								imagefill($tmp, 0, 0, $left_color);
							}				
						}
					}
				}

				if ($top_defined == false && $right_defined && $bottom_defined && $left_defined) {
					// top
					if ($width >= $this->width && $height >= $this->height) {
						$seze = array(ceil(($width - $this->width) / 2), 0, $this->width, $this->height);
					} elseif ($width >= $this->width) {
						$seze = array(ceil(($width - ($height / ($this->height / $this->width))) / 2), 0, ceil($height / ($this->height / $this->width)), $height);
					} elseif ($height >= $this->height) {
						$seze = array(0, 0, $width, ceil($width / ($this->width / $this->height)));
					} elseif ($tw < $width) {
						$seze = array(ceil(($width - $tw) / 2), 0, $tw, $height);	
					} else {
						$seze = array(ceil(($width - $width) / 2), 0, $width, $th);
					}				
				} elseif ($top_defined == false && $right_defined && $bottom_defined && $left_defined == false) {
					// top-left 
					if ($width >= $this->width && $height >= $this->height) {
						$seze = array(0, 0, $this->width, $this->height);
					} elseif ($width >= $this->width) {
						$seze = array(0, 0, ceil($height / ($this->height / $this->width)), $height);
					} elseif ($height >= $this->height) {
						$seze = array(0, 0, $width, ceil($width / ($this->width / $this->height)));
					} elseif ($tw < $width) {
						$seze = array(0, 0, $tw, $height);
					} else {
						$seze = array(0, 0, $width, $th);
					}
				} elseif ($top_defined == false && $right_defined == false && $bottom_defined && $left_defined) {
					// top-right 
					if ($width >= $this->width && $height >= $this->height) {
						$seze = array($width - $this->width, 0, $this->width, $this->height);
					} elseif ($width >= $this->width) {
						$seze = array($width - $tw, 0, ceil($height / ($this->height / $this->width)), $height);
					} elseif ($height >= $this->height) {
						$seze = array(0, 0, $width, ceil($width / ($this->width / $this->height)));
					} elseif ($tw < $width) {
						$seze = array($width - $tw, 0, $tw, $height);		
					} else {
						$seze = array(0, 0, $width, $th);
					} 
				} elseif ($top_defined && $right_defined && $bottom_defined == false && $left_defined == false) {
					// bottom-left 
					if ($width >= $this->width && $height >= $this->height) {
						$seze = array(0, $height - $this->height, $this->width, $this->height);
					} elseif ($width >= $this->width) {
						$seze = array(0, 0, ceil($height / ($this->height / $this->width)), $height);
					} elseif ($height >= $this->height) {
						$seze = array(0, $height - $th, $width, ceil($width / ($this->width / $this->height)));
					} elseif ($tw < $width) {
						$seze = array(0, 0, $tw, $height);	
					} else {
						$seze = array(0, $height - $th, $width, $th);	
					}
				} elseif ($top_defined && $right_defined == false && $bottom_defined == false && $left_defined) {
					// bottom-right 
					if ($width >= $this->width && $height >= $this->height) {
						$seze = array($width - $this->width, $height - $this->height, $this->width, $this->height);
					} elseif ($width >= $this->width) {
						$seze = array($width - $tw, 0, ceil($height / ($this->height / $this->width)), $height);
					} elseif ($height >= $this->height) {
						$seze = array(0, $height - $th, $width, ceil($width / ($this->width / $this->height)));
					} elseif ($tw < $width) {
						$seze = array($width - $tw, 0, $tw, $height);	
					} else {
						$seze = array($width - $tw, $height - $th, $width, $th);	
					}
				} elseif ($top_defined && $right_defined == false && $bottom_defined && $left_defined && ($top_color == $bottom_color && $bottom_color == $left_color)) {
					// right
					if ($width >= $this->width && $height >= $this->height) {
						$seze = array($width - $this->width, ceil(($height - $this->height) / 2), $this->width, $this->height);
					} elseif ($width >= $this->width) {
						$seze = array($width - $tw, 0, ceil($height / ($this->height / $this->width)), $height);
					} elseif ($height >= $this->height) {
						$seze = array(0, $height - $th, $width, ceil($width / ($this->width / $this->height)));
					} elseif ($tw < $width) {
						$seze = array($width - $tw, ceil(($height - $height) / 2), $tw, $height);	
					} else {
						$seze = array(0, ceil(($height - $th) / 2), $width, $th);
					} 
				} elseif ($top_defined && $right_defined && $bottom_defined == false && $left_defined) {
					// bottom
					if ($width >= $this->width && $height >= $this->height) {
						$seze = array(ceil(($width - $this->width) / 2), $height - $this->height, $this->width, $this->height);
					} elseif ($width >= $this->width) {
						$seze = array(ceil(($width - ($height / ($this->height / $this->width))) / 2), 0, ceil($height / ($this->height / $this->width)), $height);
					} elseif ($height >= $this->height) {
						$seze = array(0, $height - $th, $width, ceil($width / ($this->width / $this->height)));
					} elseif ($tw < $width) {
						$seze = array(ceil(($width - $tw) / 2), 0, $tw, $height);	
					} else {
						$seze = array(ceil(($width - $width) / 2), $height - $th, $width, $th);	
					}
				} elseif ($top_defined && $right_defined && $bottom_defined && $left_defined == false) {
					// left
					if ($width >= $this->width && $height >= $this->height) {
						$seze = array(0, ceil(($height - $this->height) / 2), $this->width, $this->height);
					} elseif ($width >= $this->width) {
						$seze = array(0, 0, ceil($height / ($this->height / $this->width)), $height);
					} elseif ($height >= $this->height) {
						$seze = array(0, ceil(($height - $th) / 2), $width, ceil($width / ($this->width / $this->height)));
					} elseif ($tw < $width) {
						$seze = array(0, ceil(($height - $height) / 2), $tw, $height);		
					} else {
						$seze = array(0, ceil(($height - $th) / 2), $width, $th);	
					} 
				} elseif (($top_defined && $bottom_defined) || ($right_defined && $left_defined)) {
					// more
					if ($width >= $this->width && $height >= $this->height) {
						$seze = array(ceil(($width - $this->width) / 2), ceil(($height - $this->height) / 2), $this->width, $this->height);
					} elseif ($width >= $this->width) {
						$seze = array(ceil(($width - $tw) / 2), 0, ceil($height / ($this->height / $this->width)), $height);
					} elseif ($height >= $this->height) {
						$seze = array(0, ceil(($height - $th) / 2), $width, ceil($width / ($this->width / $this->height)));
					} elseif ($tw < $width) {
						$seze = array(ceil(($width - $tw) / 2), ceil(($height - $height) / 2), $tw, $height);		
					} else {
						$seze = array(0, ceil(($height - $th) / 2), $width, $th);	
					} 					
				} else {
					return $this->cut($width, $height);
				}

				imageCopyResampled($tmp, $this->img, $seze[0], $seze[1], 0, 0, $seze[2], $seze[3], $this->width, $this->height);
				$this->img = $tmp;
				unset($tmp);
				
				return true;
			} else {
				return $this->cut($width, $height);
			}
		}
	}
	
	/**
	 * Поворот изображения
	 */
	public function rotate($deg)
	{
		$this->img = imagerotate($this->img, $deg, 0);
		
		$width  = $this->width;
		$this->width = $this->height;
		$this->height = $width;
	}	

	/**
	 * Поворот изображения по часовой стрелки.
	 */
	public function rotateRight()
	{
		$this->rotate(-90);
	}
	
	/**
	 * Поворот изображения против часовой стрелки.
	 */
	public function rotateLeft()
	{
		$this->rotate(90);
	}

	public function opacity($image, $opacity)
	{
		$width  = imagesx($image);
		$height = imagesy($image);

		$tmp = imagecreatetruecolor($width, $height);
		imagealphablending($tmp, false);
		imagefill($tmp, 0, 0, imagecolortransparent($tmp));
		imagecopy($tmp, $image, 0, 0, 0, 0, $width, $height);

		for ($x = 0; $x < $width; ++$x) {
			for ($y = 0; $y < $height; ++$y) {
				$pixelColor = imagecolorat($tmp, $x, $y);
				$pixelOpacity = 127 - (($pixelColor >> 24) & 0xFF);
				if ($pixelOpacity > 0) {
					$pixelOpacity = $pixelOpacity * $opacity;
					$pixelColor = ($pixelColor & 0xFFFFFF) | ((int)round( 127 - $pixelOpacity) << 24);
					imagesetpixel($tmp, $x, $y, $pixelColor);
				}
			}
		}

		return $tmp;
	}

	public function watermark($file, $position = 'center', $transparency = 1) 
	{
		if (empty($file)) {
			throw new Exception('Файл маски не найден');
		} else {
			$info = getimagesize($file);
			if (empty($info)) {
				throw new Exception('Файл маски не найден');
			} else {
				switch ($info[2]) { 
					case 1: 
						$dest = imageCreateFromGif($file);
						break;					
					case 2: 
						$dest = imageCreateFromJpeg($file); 
						break;	
					case 3: 
						$dest = imageCreateFromPng($file); 
						$transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127); 
						imagefill($dest, 0, 0, $transparent); 
						imagecolortransparent($dest, $transparent);
						
						imageAlphaBlending($dest, true);
						imageSaveAlpha($dest, true);
						
						
						break;
					case 18: 
						$dest = imageCreatefromWebp($file); 
						break;
					default:
						throw new Exception('Формат файла маски не подерживается');
						break;
				}
	
				switch ($position) {
					case 'top': 
						$x = ceil(($this->width - $info[0]) / 2);
						$y = 0;
						break;					
					case 'top-left': 
						$x = 0;
						$y = 0;
						break;
					case 'top-right': 
						$x = ceil($this->width - $info[0]);
						$y = 0;
						break;
					case 'left': 
						$x = 0;
						$y = ceil(($this->height - $info[1]) / 2);
						break;
					case 'right': 				 
						$x = ceil($this->width - $info[0]);
						$y = ceil(($this->height - $info[1]) / 2);
						break;
					case 'bottom':  
						$x = ceil(($this->width - $info[0]) / 2);
						$y = ceil($this->height - $info[1]);
						break;					
					case 'bottom-left': 
						$x = 0;
						$y = ceil($this->height - $info[1]);
						break;
					case 'bottom-right':
						$x = ceil($this->width - $info[0]);
						$y = ceil($this->height - $info[1]);
						break;
					default:
						$x = ceil(($this->width - $info[0]) / 2);
						$y = ceil(($this->height - $info[1]) / 2);
						break;
				}

				$dest = $this->opacity($dest, $transparency);
				imagecopy($this->img, $dest, $x, $y, 0, 0, $info[0], $info[1]);
			}
		}
	}

	/**
	 * Сохраняет изображение в файл.
	 */
	public function saveJPG($filename, $quality = 100)
	{
		return imageJpeg($this->img, $filename, $quality);
	}	
	
	public function savePNG($filename)
	{
		if (empty($filename)) {
			$filename = $this->filename;
		}

		return imagePng($this->img, $filename);
	}	
	
	public function saveGIF($filename)
	{
		return imageGif($this->img, $filename);
	}

	public function saveWEBP($filename, $quality = 100)
	{
		return imageWebp($this->img, $filename, $quality);
	}

	public function save($filename = '', $quality = 100)
	{
		if (empty($filename)) {
			$filename = $this->filename;
		}
	
		switch ($this->type) {
			case 1:  return $this->saveGif($filename);		
			case 2:  return $this->saveJpg($filename, $quality);	
			case 3:  return $this->savePng($filename);
			case 18: return $this->saveWebp($filename);
		}
	}

	/**
	 * Выводит изображение в браузер.
	 */
	public function output($quality = 100)
	{
		switch ($this->type) {
			case 1: 
				header('Content-Type: image/gif'); 
				imageGif($this->img);
				break;			
			case 2: 
				header('Content-Type: image/jpeg');
				imageJpeg($this->img, null, $quality);
				break;			
			case 3: 
				header('Content-Type: image/x-png');
				imagePng($this->img);
				break;	
			case 18: 
				header('Content-Type: image/webp');
				imageWebp($this->img, $quality);
				break;
		}
	}
	
	/**
	 * Выводит изображение в браузер и Сохраняет его.
	 */
	public function saveOut($filename, $quality = 100)
	{
		if (empty($filename)) {
			$filename = $this->filename;
		}

		switch ($this->type) {
			case 1:
				header('Content-Type: image/gif'); 
				imageGif($this->img, $filename);
				break;			
			case 2:
				header('Content-Type: image/jpeg');
				imageJpeg($this->img, $filename, $quality);
				break;			
			case 3:
				header('Content-Type: image/x-png');
				imagePng($this->img, $filename);
				break;
		}

		header('Content-Length: ' . filesize($filename));
		readfile($filename);
	}
	
	public function destroy()
	{
		imagedestroy($this->img);
	}
}
