<?php


namespace Drupal\annotation_route\Service;


use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Drupal\annotation_route\Loaders\AnnotationClassLoader;
use Drupal\annotation_route\Loaders\AnnotationDirectoryLoader;
use Symfony\Component\Config\FileLocator;

/**
 *
 * Class AnnotationService
 * @author Jarming
 * @package Drupal\annotation_route\Service
 */
class AnnotationService
{
  /**
   * @var FileLocator
   */
  private $fileLocator;

  /**
   * @var AnnotationReader
   */
  private $reader;

  /**
   * @var AnnotationClassLoader
   */
  private $classLoader;

  /**
   * @var AnnotationDirectoryLoader
   */
  private $directoryLoader;

  /**
   * @var string
   */
  private $annotationClass;


  public function __construct()
  {
    $this->initService();
  }

  /**
   * 初始化服务，使用此服务之前请务必先调用该方法，不然可能会出现冲突
   */
  public function initService(){
    $this->fileLocator = new FileLocator();
    $this->reader = new AnnotationReader();
    $this->classLoader = new AnnotationClassLoader($this->reader);
    $this->directoryLoader = new AnnotationDirectoryLoader($this->fileLocator, $this->classLoader);
    $this->addGlobalIgnoredName("endlink"); // 新增忽略注解@endlink
  }

  /**
   * @param $path
   * @param null $type
   * @return \Drupal\annotation_route\Loaders\AnnotationItem
   */
  public function loadPath($path,$type=null){
    return $this->directoryLoader->load($path,$type);
  }

  /**
   * @param $class
   * @param null $type
   * @return \Drupal\annotation_route\Loaders\AnnotationItem
   */
  public function loadClass($class,$type=null){
    return $this->classLoader->load($class,$type);
  }

  /**
   * @param $class
   * @return AnnotationService
   * @throws AnnotationException
   */
  public function setAnnotationClass($class){
    if (!class_exists($class)) {
      throw new AnnotationException("这个动态路由注解类 \"${class}\" 不存在，或未被自动加载. ");
    }
    $this->annotationClass = $class;
    $this->classLoader->setAnnotationClass($class);
    return $this;
  }

  /**
   * @return string
   */
  public function getAnnotationClass(){
    return $this->annotationClass;
  }

  /**
   * @param $name
   * @return AnnotationService
   */
  public function addGlobalIgnoredName($name){
    $this->reader::addGlobalIgnoredName($name);
    return $this;
  }

  /**
   * @param $namespace
   * @return AnnotationService
   */
  public function addGlobalIgnoredNamespace($namespace){
    $this->reader::addGlobalIgnoredNamespace($namespace);
    return $this;
  }

  /**
   * 设置自定义的 ClassLoader 该classLoader需要继承 Drupal\annotation_route\Loaders\AnnotationClassLoader
   * 可以通过重写AnnotationClassLoader中的 load() 等……方法来自定义编译过程。
   * @param string $classLoader
   * @return AnnotationService
   * @throws \Exception
   */
  public function setClassLoader($classLoader)
  {
    if (!class_exists($classLoader)) throw new \Exception("这个类不存在或者这个类未被加载: ${$classLoader}");
    $classLoaderObject = new $classLoader($this->reader);
    if (!$classLoaderObject instanceof AnnotationClassLoader){
      throw new \Exception("这个类'${classLoader}'未继承 'Drupal\\annotation_route\\Loaders\\AnnotationClassLoader'类");
    }
    $this->classLoader = $classLoaderObject;
    $dlc = get_class($this->directoryLoader);
    $this->directoryLoader = new $dlc($this->fileLocator, $this->classLoader);
    return $this;
  }

  /**
   * 设置自定义的 AnnotationDirectoryLoader 自定义等DirectoryLoader类必须继承  Drupal\annotation_route\Loaders\AnnotationDirectoryLoader
   * 可以通过重写 AnnotationDirectoryLoader 中的 load() 等……方法来自定义编译过程。
   * @param string $directoryLoader
   * @return AnnotationService
   * @throws \Exception
   */
  public function setDirectoryLoader($directoryLoader)
  {
    if (!class_exists($directoryLoader)) throw new \Exception("这个类不存在或者这个类未被加载: ${directoryLoader}");
    $directoryLoaderObject = new $directoryLoader($this->fileLocator, $this->classLoader);
    if (!$directoryLoaderObject instanceof AnnotationDirectoryLoader){
      throw new \Exception("这个类'${directoryLoader}'未继承 'Drupal\\annotation_route\\Loaders\\AnnotationDirectoryLoader'类");
    }
    $this->directoryLoader = $directoryLoader;
    return $this;
  }

}
