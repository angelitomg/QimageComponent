<?php
	
	App::uses('Component', 'Controller');
	
	
	/**
	 *
	 * CakePHP (version 2) component to upload, resize, crop and
	 * add watermark to images.
	 *
	 * @author Angelito M. Goulart <ange.sap@hotmail.com>
	 *
	 */
	class QimageComponent extends Component{
		
		/**
		 * Watermark image file (must be png)
		 *
		 * @var string
		 */
		public $watermarkImage;		


		/**
		 * Jpeg image quality (0 - 100)
		 *
		 * @var int
		 */
		public $jpgQuality;

		
		/**
		 * Property that will contain execution errors
		 *
		 * @var array
		 */
		private $errors;
	
		
		/**
		 * Initialize method. Initialize class properties.
		 *
		 * @param Controller $controller
		 */
		public function initialize(Controller $controller) {
			$this->watermarkImage = "img" . DIRECTORY_SEPARATOR . "watermark.png";
			$this->jpgQuality = 100;
			$this->errors = array();
		}
	
		
		/**
		 * Copy an uploaded image to the destination path
		 *
		 * $data['file'] 	-> array with image data (found in $_FILES)
		 * $data['path'] 	-> destination path
		 *
		 * @param array $data
		 * @return mixed
		 */
		public function copy($data){
			
			// Verify file and path
			if (!isset($data['file']) || !isset($data['path']) || !is_array($data['file'])){
				$this->errors[] = 'Name or path not found!';
				return false;
			}

			if (!is_writable($data['path'])){
				$this->errors[] = 'Destination path is not writable!';
				return false;
			}
			
			if (!$this->_verifyMime($data['file']['name'])){
				$this->errors[] = 'The file must be an jpg, gif or png image!';
				return false;
			}
			
			// Generate filename and move file to destination path
			$filename_array = explode('.', $data['file']['name']);
			$ext = end($filename_array);
			$ext = strtolower($ext);
			$name = uniqid() . date('dmYHis') . '.' . $ext;
			$complete_path = $data['path'] . DIRECTORY_SEPARATOR . $name;
			
			if (!move_uploaded_file($data['file']['tmp_name'], $data['path'] . $name)){
				$this->errors[] = 'Error while upload the image!';
				return false;
			}
			
			// Return image filename
			return $name;
			
		}
		
		
		
		/**
		 * Adds a watermark on footer of an image.
		 * The watermark image file must be informed in public $watermarkImage.
		 *
		 * $data['file'] -> image path
		 *
		 * @param array $data
		 * @return bool
		 */
		public function watermark($data){
			
			// Verify files
			if (!is_file($this->watermarkImage)){
				$this->errors[] = 'Invalid watermark file!';
				return false;
			}
			
			if (!is_file($data['file'])){
				$this->errors[] = 'Invalid file!';
				return false;
			}
			
			if(!$this->_verifyMime($data['file'])){
				$this->errors[] = 'Invalid file type!';
				return false;
			}
			
			// Get image info
			$img = getimagesize($data['file']);

			// Get watermark image info
			$wimg = getimagesize($this->watermarkImage);
			if ($wimg['mime'] !== 'image/png') {
				$this->errors[] = 'Watermark image must be png!';
				return false;
			}

			$watermark = imagecreatefrompng($this->watermarkImage);
			$watermark_width = imagesx($watermark);
			$watermark_height = imagesy($watermark);
			
			// Defines watermark margin
			$margin_right = $img[0] - $watermark_width - 15;
			$margin_bottom = $img[1] - $watermark_height - 15;
			
			$createFunction = $this->_getCreateFunction($img['mime']);
			$finishFunction = $this->_getFinishFunction($img['mime']);
			if (false === $createFunction || false === $finishFunction) {
				$this->errors[] = 'Invalid file type!';
				return false;
			}
			
			// Generate image with watermark
			$image = $createFunction($data['file']);
			imagecopy($image, $watermark, $margin_right, $margin_bottom, 0, 0, $watermark_width, $watermark_height);
			
			// Replace the original image with the new image with watermark
			if ($img['mime'] == 'image/jpeg' || $img['mime'] == 'image/pjpeg'){
				$finishFunction($image, $data['file'], (int) $this->jpgQuality);
			} else {
				$finishFunction($image, $data['file']);
			}
			
			return true;
		
		}
		
		
		/**
		 * Method responsible for resize an image. Return false on error.
		 *
		 * $data['file']                -> complete path of original image file
		 * $data['width']               -> new width
		 * $data['height']              -> new height
		 * $data['output']              -> output path where resized image will be saved
		 * $data['proportional']        -> (true or false). If true, the image will be resized 
		 * only if its dimensions are larger than the values reported in width and height 
		 * parameters. Default: true.
		 *
		 * If only the width or height is given, the function will automatically calculate 
		 * whether the image is horizontal or vertical and will automatically apply the informed 
		 * size in the correct property (width or height).
		 *
		 * @param array $data
		 * @return bool
		 */
		public function resize($data){
			
			// Verify parameters
			if (!isset($data['file']) || (!isset($data['width']) && !isset($data['height']))){
				$this->errors[] = 'Invalid filename or width/height!';
				return false;
			}
			
			if (!isset($data['output']) || !is_dir($data['output'])){
				$this->errors[] = 'Invalid output dir!';
				return false;
			}
			
			$data['proportional'] = (isset($data['proportional'])) ? $data['proportional'] : true;

			$data['height'] = (isset($data['height'])) ? $data['height'] : 0;
			$data['width']  = (isset($data['width']))  ? $data['width']  : 0;
			
			if (!is_writable($data['output'])){
				$this->errors[] = 'Output dir is not writable!';
				return false;
			}
			
			if (!is_file($data['file'])){
				$this->errors[] = 'Invalid file!';
				return false;
			}
		
			if(!$this->_verifyMime($data['file'])){
				$this->errors[] = 'Invalid file type!';
				return false;
			}
				
			if (!isset($data['proportional']))
				$data['proportional'] = true;	
			
			// Validates width and height
			$width  = (isset($data['width']))  ? (int) $data['width']  : 0;
			$height = (isset($data['height'])) ? (int) $data['height'] : 0;		
			
			// Get attributes of image
			$img = getimagesize($data['file']);
			$original_width = $img[0];
			$original_height = $img[1];
			$mime = $img['mime'];
					 			
			$source = ($mime == 'image/png') ? imagecreatefrompng($data['file']) : imagecreatefromstring(file_get_contents($data['file']));
			$filename = basename($data['file']);
			$output = $data['output'] . $filename;
			
			// Verify if resize it's necessary
			if (($width > $original_width || $height > $original_height) && $data['proportional'] === true){
			
				$width = $original_width;
				$height = $original_height;
			
			} else {
				
				// If width or height not defined, it's necessary calculate proportional size
				if (!($width > 0 && $height > 0)){
					
					
					// Verify if image is horizontal or vertical
					if ($original_height > $original_width){
						$height = ($data['width'] > 0) ? $data['width'] : $data['height'];
						$width  = ($height / $original_height) * $original_width;
					} else {
						$width = ($data['height'] > 0) ? $data['height'] : $data['width'];
						$height = ($width / $original_width) * $original_height;
					}
					
				} 
			
			}
			
			// Generate thumb
			$thumb = imagecreatetruecolor($width, $height);
			
			// Add transparency if image is png
			if ($mime == 'image/png') {
				imagealphablending($thumb, false);
				imagesavealpha($thumb,true);
				$transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
				imagefilledrectangle($thumb, 0, 0, $width, $height, $transparent);
			} 
			
			// Finish image
			imagecopyresampled($thumb, $source, 0, 0, 0, 0, $width, $height, $original_width, $original_height);
			$finishFunction = $this->_getFinishFunction($mime); 
						
			if (false === $finishFunction) { 
				$this->errors[] = 'Invalid file type.';
				return false;	
			} elseif ($mime == 'image/jpeg' || $mime == 'image/pjpeg') {
				$finishFunction($thumb, $output, (int) $this->jpgQuality);
			} else {
				$finishFunction($thumb, $output);
			} 

			return true;
			
		}


		/**
		 * Method to crop an image
		 * 
		 * $data['file']        -> complete path of original image file
		 * $data['w']           -> width of crop area
		 * $data['h']           -> height of crop area
		 * $data['x']           -> x coordinate of source point
		 * $data['y']           -> y coordinate of source point
		 * $data['output']      -> output path where cropped image will be saved
		 *
		 * @param array $data
		 * @return bool
		 */
		public function crop($data = array()){

			// Validates params
			if (!isset($data['file']) ||
			    !isset($data['w']) ||
			    !isset($data['h']) ||
			    !isset($data['x']) ||
			    !isset($data['y']) ||
			    !isset($data['output'])) {
				 $this->errors[] = 'Params missing!';
			}
			
			if (!is_file($data['file'])) {
				$this->errors[] = 'Invalid image!';
				return false;
			}

			if (!is_dir($data['output']) || !is_writable($data['output'])) {
				$this->errors[] = 'Output dir is not a dir or not writeable!';
				return false;
			}

			// Output path
			$path = $data['output'] . DIRECTORY_SEPARATOR . basename($data['file']);

			// Get image data
			$img = getimagesize($data['file']);

			$createFunction = $this->_getCreateFunction($img['mime']);
			$finishFunction = $this->_getFinishFunction($img['mime']);

			// Create source and destination image
			$src_img = $createFunction($data['file']);
			$dst_img = imagecreatetruecolor($data['w'], $data['h']);

			// Crop image
			imagecopyresampled($dst_img, 
					   $src_img, 
					   0, 
					   0, 
					   (int) $data['x'], 
					   (int) $data['y'],
	        			   (int) $data['w'],
	        			   (int) $data['h'],
	        			   (int) $data['w'], 
	        			   (int) $data['h']);

			// Finish image
			if ($img['mime'] == 'image/jpeg' || $img['mime'] == 'image/pjpeg'){
				$finishFunction($dst_img, $path, (int) $this->jpgQuality);
			} else {
				$finishFunction($dst_img, $path);
			}

		}
		
		
		/** 
		 * Verifies the mime-type of a file
		 *
		 * @param string $file
		 * @return bool
		 */
		private function _verifyMime($file){
			
			$filename_array = explode('.',$file);

			$extension = end($filename_array);
			
			$extension = strtolower($extension);
			
			$mimes = array('jpeg', 'jpg', 'png', 'gif');
			
			if (in_array($extension, $mimes)){
				return true;
			} else {
				return false;
			}
			
		}


		/**
		 * Method to get the specific function to create an image
		 * 
		 * @param string $mime
		 * @return string
		 */
		private function _getCreateFunction($mime){
		    if ($mime == 'image/jpeg' || $mime == 'image/pjpeg'){
	            	return 'imagecreatefromjpeg';
		    } elseif ($mime == 'image/gif') {
		        return 'imagecreatefromgif';
		    } elseif ($mime == 'image/png') {
		        return 'imagecreatefrompng';
		    } else {
		        $this->errors[] = 'Invalid file type!';
		        return false;
		    }
		}


		/**
		 * Method to get the specific function to finish an image
		 *
		 * @param string $mime
		 * @return string
		 */
		private function _getFinishFunction($mime) {
			if ($mime == 'image/jpeg' || $mime == 'image/pjpeg'){
				return 'imagejpeg';
			} elseif ($mime == 'image/gif') {
				return 'imagegif';
			} elseif ($mime == 'image/png') {
				return 'imagepng';
			} else {
				$this->errors[] = 'Invalid file type.';
				return false;	
			}
		}


		/**
		 * Get errors
		 *
		 * @return array
		 */
		public function getErrors() {
			return $this->errors;
		}

		
	}
	
?>
