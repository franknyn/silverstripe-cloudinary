<?php

class CloudinaryImage extends CloudinaryFile
{

    private static $arr_gravity = array(
        'auto' => 'Auto',
        'center' => 'Center',
        'north_east' => 'NE',
        'north' => 'N',
        'north_west' => 'NW',
        'west' => 'W',
        'south_west' => 'SW',
        'south' => 'S',
        'south_east' => 'SE',
        'east' => 'E'
    );

    private static $db = array(
        'Gravity' => 'Enum("auto,center,north_east,north,north_west,west,south_west,south,south_east,east", "auto")',
        'Credit' => 'Varchar(200)',
        'Caption' => 'Varchar(200)',
        'Width' => 'Int',
        'Height' => 'Int',
    );

    public function getTitle() {
        if ($this->URL && CloudinaryUtils::resource_type($this->URL) == 'raw') {
            return $this->FileTitle;
        }
        if ($this->URL && CloudinaryUtils::resource_type($this->URL) != 'raw'){
            return $this->Caption;
        }
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $this->hideCMSFields($fields, array('FileTitle', 'Width', 'Height'));
        if (!$this->ID) {
            $this->hideCMSFields($fields, array('Credit', 'Caption', 'Gravity'));
        }

        return $fields;
    }

    public function getFrontEndFields($params = null)
    {
        $fields = parent::getFrontEndFields($params);
        $fields->replaceField('Caption', TextField::create('Caption'));
        $fields->replaceField('Credit', TextField::create('Credit'));
        $fields->dataFieldByName('Gravity')->setSource(self::$arr_gravity);
        $fields->replaceField('FileTitle', HiddenField::create('FileTitle'));
        $fields->replaceField('Height', TextField::create('Height'));
        $fields->replaceField('Width', TextField::create('Width'));
        return $fields;
    }

    public function Image( $width, $height, $crop, $quality = 'auto', $gravity = false) {
        $options = array(
            'secure' => true,
            'fetch_format' => 'auto',
            'quality' =>  $quality,
            'width' => $width,
            'height' => $height,
            'gravity' => $gravity ?: $this->Gravity
        );

        if($crop){
            $options['crop'] = $crop;
        }

        if ($gravity) {
            $options['gravity'] = $gravity;
        }
        // These crops don't support gravity, Cloudinary returns a 400 if passed
        if (in_array($crop, array('fit', 'limit', 'mfit', 'pad', 'lpad'))) {
            unset($options['gravity']);
        }

        $cloudinaryID = CloudinaryUtils::public_id($this->URL);
        $fileName = $this->Format ? $cloudinaryID. '.'. $this->Format : $cloudinaryID;
        return Cloudinary::cloudinary_url($fileName, $options);
    }

    /**
     * @param int $iWidth
     * @param int $iHeight
     * @param int $iQuality
     * @return CloudinaryImage_Cached|Image_Cached
     */
    public function Thumbnail($iWidth, $iHeight, $iQuality = 60)
    {
        return $this->CMSThumbnail($iWidth, $iHeight, $iQuality);
    }

    /**
     * @return Image_Cached
     */
    public function StripThumbnail()
    {
        return $this->CMSThumbnail(100, 100, 'fill', 60);
    }

    /**
     * @param int $iWidth
     * @param int $iHeight
     * @param string $crop
     * @param int $iQuality
     * @return CloudinaryImage_Cached
     */
    public function CMSThumbnail($iWidth = 80, $iHeight = 60, $crop = 'fill', $iQuality = 80)
    {
        $thumbnailUrl = $this->Image($iWidth, $iHeight, $crop, $iQuality, 'faces');
        $field = new HTMLText();
        if($thumbnailUrl){
            $field->setValue("<img src=\"$thumbnailUrl\" alt=\"$this->Title\"/>");
        }
        return $field;
    }

}
