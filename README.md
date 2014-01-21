Qimage Component
==============

Quick Image Component (Qimage) is a component for CakePHP to facilitate image manipulation and upload.

Tested on CakePHP 2.x

Methods
--------------

**copy** -> Copy uploaded images.  

**resize** -> Resize an image.  

**watermark** -> Add watermark in an image.  

**crop** -> Crop an image.

**_verifyMime** -> Verify the MIME type of a file based on file extension.

**_getCreateFunction** -> Get image creation function based on image mime type.

**_getFinishFunction** -> Get image finish function based on image mime type.

**_getErrors** -> Get all errors that occurred in Qimage.

More details can be found in the comments of the methods. 

Installation
--------------

 - Copy QimageComponent.php to /app/Controller/Component/
 - Add QimageComponent to components array of your controller: var $components = array('Qimage');
 - Call Qimage methods: $this->Qimage->methodName();  
 
 NOTE: default watermark image is /app/webroot/img/watermark.png

License
--------------

Qimage is opensource and do not have any guarantee. You are free to use, modify and redistribute.

Author
--------------

Angelito M. Goulart

www.angelitomg.com
