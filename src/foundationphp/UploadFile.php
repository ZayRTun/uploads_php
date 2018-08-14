<?php
namespace foundationphp;

class UploadFile
{
  protected $destination;
  protected $messages = [];
  protected $maxSize = 51200;

  public function __construct($uploadFolder)
  {
    if (!is_dir($uploadFolder) || !is_writable($uploadFolder)) {
      throw new \Exception("$uploadFolder must be a valid, writable folder.");
    }
    if ($uploadFolder[strlen($uploadFolder) - 1] != '/') {
      $uploadFolder .= '/';
    }
    $this->destination = $uploadFolder;
  }

  public function setMaxSize($bytes)
  {
    $serverMax = self::convertToBytes(ini_get('upload_max_filesize'));
    if ($bytes > $serverMax) {
      throw new \Exception('Maximum size cannot exceed server limit for individual files: ' . self::convertFromBytes($serverMax));
    }
    if (is_numeric($bytes) && $bytes > 0) {
      $this->maxSize = $bytes;
    }
  }

  public static function convertToBytes($val)
  {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    if (in_array($last, array('g', 'm', 'k'))) {
      $val = (float) $val;
      switch ($last) {
        case 'g':
          $val *= 1024;
        case 'm':
          $val *= 1024;
        case 'k':
          $val *= 1024;
      }
    }
    return $val;
  }

  public static function convertFromBytes($bytes)
  {
    $bytes /= 1024;
    if ($bytes > 1024) {
      return number_format($bytes/1024, 1) . ' MB';
    } else {
      return number_format($bytes, 1) . ' KB';
    }
  }

  public function upload()
  {
    $uploaded = current($_FILES);
    if ($this->checkFile($uploaded)) {
      $this->moveFile($uploaded);
    }
  }

  public function getMessages()
  {
    return $this->messages;
  }

  protected function checkFile($file)
  {
    if ($file['error'] != 0) {
      $this->getErrorMessage($file);
      return false;
    }
    if (!$this->checkSize($file)) {
      return false;
    }
    return true;
  }

  protected function getErrorMessage($file)
  {
    switch ($file['error']) {
      case 1:
      case 2:
        $this->messages[] = $file['name'] . ' is too big: (max: '. self::convertFromBytes($this->maxSize) . ').';
        break;
      case 3:
        $this->messages[] = $file['name'] . ' was only partially uploaded.';
        break;
      case 4:
        $this->messages[] = 'No file submitted.';
        break;
      default:
        $this->messages[] = 'Sorry, there was a problem uploading ' . $file['name'];
        break;
    }
  }

  protected function checkSize($file)
  {
    if ($file['size'] == 0) {
      $this->messages[] = $file['name'] . ' is empty.';
      return false;
    } elseif ($file['size'] > $this->maxSize) {
      $this->messages[] = $file['name'] . ' exceeds the maximum size for a file (' . self::convertFromBytes($this->maxSize) . ').';
      return false;
    } else {
      return true;
    }
  }

  protected function moveFile($file)
  {
    $this->messages[] = $file['name'] . ' was uploaded successfully.';
  }

}