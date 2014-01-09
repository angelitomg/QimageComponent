Qimage Component
==============

Quick Image Component (Qimage) is a component for CakePHP to facilitate image manipulation and upload.

Tested on CakePHP 2.x

Methods
--------------

copy() -> For copy uploaded images.  
resize() -> For resize an image.  
watermark() -> To add watermark in an image.  
verifyMime() -> An internal method for verify the MIME type of a file based on file extension.

For more details of the methods, see comments in the QimageComponent.php file.  

Installation
--------------

 - Copy QimageComponent.php to /app/Controller/Component/
 - Add QimageComponent to components array of your controller: var $components = array('Qimage');
 - Call the Qimage methods: $this->Qimage->methodName();  
 
 NOTE: default watermark image is /app/webroot/img/watermark.png

License
--------------

Qimage is opensource and do not have any guarantee. You are free to use, modify and redistribute.

Author
--------------

Angelito M. Goulart

www.angelitomg.com
