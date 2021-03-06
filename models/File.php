<?php

namespace app\models;

use app\components\BaseActive;
use yii\imagine;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\ManipulatorInterface;
use Yii;

/**
 * This is the model class for table "file".
 *
 * @property integer $id
 * @property integer $userId
 * @property integer $ownerId
 * @property integer $ownerType
 * @property string $title
 * @property string $name
 * @property integer $size
 * @property string $mime
 * @property string $dateCreate
 * @property string $dateUpdate
 * @property integer $ip
 * @property integer $position
 */
class File extends BaseActive
{
    const UPLOAD_DIR_TMP = 'uploads/files/tmp';
    const UPLOAD_DIR = 'uploads/files';
    
    //
    // owner types
    //
    const OWNER_TYPE_NEWS_GALLERY = 1;
    const OWNER_TYPE_NEWS_PREVIEW = 2;
    const OWNER_TYPE_NEWS_TEXT    = 3;
    
    const OWNER_TYPE_USER_PHOTO = 4;
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'file';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'userId' => Yii::t('app', 'User'),
            'ownerId' => Yii::t('app', 'Owner'),
            'ownerType' => Yii::t('app', 'Owner type'),
            'title' => Yii::t('app', 'Title'),
            'name' => Yii::t('app', 'Name'),
            'size' => Yii::t('app', 'Size'),
            'mime' => Yii::t('app', 'Mime'),
            'dateCreate' => Yii::t('app', 'Date create'),
            'dateUpdate' => Yii::t('app', 'Date update'),
            'ip' => Yii::t('app', 'IP'),
            'position' => Yii::t('app', 'Position'),
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'dateCreate',
                'updatedAtAttribute' => 'dateUpdate',
                'value' => new \yii\db\Expression('NOW()'),
            ],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
        ];
    }
    
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {        
            if ($insert) {
                $this->userId = user()->isGuest ? 0 : user()->id;
                $this->ip = ip2long(Yii::$app->request->getUserIP());
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Generate a name for new file.
     *
     * @param string $extension
     * @return string
     */
    public function generateName($extension = null)
    {
        $name = date('YmdHis') . substr(md5(microtime() . uniqid()), 0, 10);
        return $extension ? $name . '.' . $extension : $name;
    }
    
    /**
     * Path to temporary directory of file.
     *
     * @param bool $full
     * @return string
     */
    public function dirTmp($full = false)
    {
        return
            ($full ? Yii::getAlias('@webroot') : '') . 
            '/' . self::UPLOAD_DIR_TMP . 
            '/' . $this->ownerType;
    }
    
    /**
     * Path to directory of file.
     *
     * @param bool $full.
     * @return string
     */
    public function dir($full = false)
    {
        if ($this->tmp) {
            return $this->dirTmp($full);
        } else {
            return 
                ($full ? Yii::getAlias('@webroot') : '') . 
                '/' . self::UPLOAD_DIR . 
                '/' . $this->ownerType .
                '/' . $this->ownerId;
        }
    }
    
    /**
     * Path to file.
     *
     * @param bool $full
     * @return string
     */
    public function pathTmp($full = false)
    { 
        return $this->dirTmp($full) . '/'. $this->name;
    }
        
    /**
     * Path to file.
     *
     * @param bool $full
     * @return string
     */
    public function path($full = false)
    { 
        return $this->dir($full) . '/'. $this->name;
    }
    
    /**
     * Create file from UploadedFile.
     *
     * @param UploadedFile $data
     * @param int $ownerType
     * @param bool $tmp The temporary file.
     * @return File|bool
     */
    public static function createFromUpload($data, $ownerType, $tmp = true)
    {
        $fileInfo = pathinfo($data->name);
        
        $file = new self();
        $file->ownerType = $ownerType;
        $file->tmp = $tmp;
        $file->size = $data->size;
        $file->mime = $data->type;
        $file->title = $fileInfo['filename'];
        $file->name = $file->generateName($fileInfo['extension']);
        
        if (FileHelper::createDirectory($file->dir(true))) {
            if (move_uploaded_file($data->tempName, $file->path(true))) {
                if ($file->save()) {
                    return $file;
                }   
            }
        }
        
        return false;
    }
    
    /**
     * Create file from Url
     *
     * @param string $url
     * @param int $ownerType
     * @param bool $tmp The temporary file.
     * @return File|bool
     */
    public static function createFromUrl($url, $ownerType, $tmp = true)
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'file');
        
        if ($tmpFileContent = @file_get_contents($url)) {
            if (@file_put_contents($tmpFile, $tmpFileContent)) {
                $fileInfo = pathinfo($url);
                
                $file = new self();
                $file->ownerType = $ownerType;
                $file->tmp = $tmp;
                $file->size = filesize($tmpFile);
                $file->mime = FileHelper::getMimeType($tmpFile);
                $file->title = $fileInfo['filename'];
                $file->name = $file->generateName($fileInfo['extension']);

                if (FileHelper::createDirectory($file->dir(true))) {
                    if (rename($tmpFile, $file->path(true))) {
                        if ($file->save()) {
                            return $file;
                        }   
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Check owner.
     *
     * @param File $file
     * @param int $ownerId 
     * @param int $ownerType 
     * @return bool
     */
    public static function checkOwner($file, $ownerId, $ownerType)
    {
        $ownerType = $file->ownerType === $ownerType;
        $ownerId = $file->ownerId === $ownerId;
        $user = $file->userId === user()->id || $file->userId === 0;
        
        return 
            (!$file->tmp && $ownerType && $ownerId) || 
            ($file->tmp && $ownerType && $user);
    }
    
    /**
     * Binding files with owner.
     *
     * @param int $ownerId
     * @param int $ownerType
     * @param array|int $files
     * @return File|bool|array
     */
    public static function bind($ownerId, $ownerType, $files)
    {
        if ($files === [] || $files === '') {
            return self::deleteByOwner($ownerId, $ownerType);
        }
        
        return is_array($files)
            ? self::bindMultiple($ownerId, $ownerType, $files)
            : self::bindSingle($ownerId, $ownerType, $files);
    }
    
    /**
     * Binding file with owner.
     *
     * @param int $ownerId
     * @param int $ownerType
     * @param int $fileId
     * @return File|bool
     */
    public static function bindSingle($ownerId, $ownerType, $fileId)
    {
        $file = static::findOne($fileId);
        
        // check owner
        if (!$file || !self::checkOwner($file, $ownerId, $ownerType)) {
            return false;
        }
 
        // check and save tmp file
        if ($file->tmp) {
            $file->tmp = false;
            $file->ownerId = $ownerId;
            
            if (file_exists($file->pathTmp(true)) && FileHelper::createDirectory($file->dir(true))) {
                if (rename($file->pathTmp(true), $file->path(true))) {
                    $file->updateAttributes(['tmp' => $file->tmp, 'ownerId' => $file->ownerId]);
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
        
        // delete unnecessary files
        $currentFiles = self::getByOwner($ownerId, $ownerType);
        foreach ($currentFiles as $currFile) {
            if ($currFile->id !== $file->id) {
                $currFile->delete();
            }
        }
        
        return $file;
    }
    
    /**
     * Binding files with owner.
     *
     * @param int $ownerId
     * @param int $ownerType
     * @param array $files
     * @return array|bool
     */
    public static function bindMultiple($ownerId, $ownerType, $files)
    {
        if (!is_array($files)) {
            return;
        }
        
        // prepare files
        $files = array_filter($files);
        $files = array_combine(array_map(function ($a) {
            return substr($a, 2);
        }, array_keys($files)), $files);

        // get new files
        $newFiles = static::findAll(array_keys($files));
        $newFiles = ArrayHelper::index($newFiles, 'id');
        
        // get current files
        $currentFiles = self::getByOwner($ownerId, $ownerType);
        $currentFiles = ArrayHelper::index($currentFiles, 'id');
    
        if (count($newFiles)) {
            // check new files
            foreach ($newFiles as $file) {
                // check owner
                if (!self::checkOwner($file, $ownerId, $ownerType)) {
                    unset($newFiles[$file->id]);
                    continue;
                }
                // save tmp file
                if ($file->tmp) {
                    $file->tmp = false;
                    $file->ownerId = $ownerId;
                    
                    if (file_exists($file->pathTmp(true)) && FileHelper::createDirectory($file->dir(true))) {
                        if (!rename($file->pathTmp(true), $file->path(true))) {
                           return false;
                        }
                    } else {
                        return false;
                    }  
                }
                
                $file->updateAttributes([
                    'tmp'      => $file->tmp, 
                    'ownerId'  => $file->ownerId,
                    'title'    => @$files[$file->id],
                    'position' => @array_search($file->id, array_keys($files)) + 1
                ]);
            }
            
            // delete unnecessary files
            foreach ($currentFiles as $currFile) {
                if (!array_key_exists($currFile->id, $newFiles)) {
                    $currFile->delete();
                }
            }
        
        } else {
            // if empty array — delete current files
            foreach ($currentFiles as $currFile) {
                $currFile->delete();
            }
        }

        return $newFiles;
    }

    /**
     * Resize.
     *
     * @param string $file
     * @param int $width
     * @param int $height
     * @param bool $ratio
     * @return string
     */
    public static function resize($file, $width, $height, $ratio = false)
    {
        if (!file_exists(Yii::getAlias('@webroot') . $file)) {
            return $file;
        }

        $fileName = pathinfo($file, PATHINFO_FILENAME);
        $thumb = str_replace($fileName, $width . 'x' . $height . '_' . $fileName, $file);
        
        if (file_exists(Yii::getAlias('@webroot') . $thumb)) {
            return $thumb;
        }

        $imagine = imagine\Image::getImagine();
        
        try {
            $image = $imagine->open(Yii::getAlias('@webroot') . $file);
            
            if ($width < 1 || $height < 1) {
                if ($height < 1) {
                    $image = $image->resize($image->getSize()->widen($width));
                } else {
                    $image = $image->resize($image->getSize()->heighten($height));
                }
                
            } else {
                $size = new Box($width, $height);
                
                if ($ratio) {
                    $mode = ImageInterface::THUMBNAIL_INSET;
                } else {
                    $mode = ImageInterface::THUMBNAIL_OUTBOUND;
                }
                
                $image = $image->thumbnail($size, $mode);
            }
            
            $image->save(Yii::getAlias('@webroot') . $thumb, [
                'jpeg_quality' => 100,
                'png_compression_level' => 9
            ]);
            
        } catch (Exception $exception) {
            return $file;
        }
        
        return $thumb;
    }
    
    /**
     * Get by owner.
     *
     * @param int $ownerId
     * @param int $ownerType
     * @return array
     */
    public static function getByOwner($ownerId, $ownerType)
    {
        return static::find()
            ->where(['ownerId' => $ownerId, 'ownerType' => $ownerType])
            ->orderBy('position ASC')
            ->all();
    }
    
    /**
     * Delete by owner.
     *
     * @param int $ownerId
     * @param int $ownerType
     */
    public static function deleteByOwner($ownerId, $ownerType)
    {
        $files = self::getByOwner($ownerId, $ownerType);
        
        foreach ($files as $file) {
            $dir = $file->dir(true);
            $file->delete();
        }
        
        if (isset($dir) && !empty($dir)) {
            FileHelper::removeDirectory($dir);
        }
    }
    
    /**
     * Deleting a file from the db and from the file system.
     *
     * @return bool
     */
    public function beforeDelete()
    {
        if (file_exists($this->path(true))) {
            return @unlink($this->path(true));
        }
        
        return true;
    }
}
