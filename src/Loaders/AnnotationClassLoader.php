<?php


namespace Drupal\annotation_route\Loaders;


use Doctrine\Common\Annotations\Reader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;

class AnnotationClassLoader implements LoaderInterface
{
  protected $reader;

  /**
   * @var string
   */
  protected $annotationClass = '';

  /**
   * @var int
   */
  protected $defaultIndex = 0;

  public function __construct(Reader $reader)
  {
    $this->reader = $reader;
  }

  /**
   * Sets the annotation class to read route properties from.
   *
   * @param string $class A fully-qualified class name
   */
  public function setAnnotationClass($class)
  {
    $this->annotationClass = $class;
  }

  /**
   * Loads from annotations from a class.
   *
   * @param string      $class A class name
   * @param string|null $type  The resource type
   *
   * @return AnnotationItem
   *
   * @throws \InvalidArgumentException When route can't be parsed
   */
  public function load($class, $type = null)
  {
    if (!class_exists($class)) {
      throw new \InvalidArgumentException(sprintf('Class "%s" does not exist.', $class));
    }

    $class = new \ReflectionClass($class);
    if ($class->isAbstract()) {
      throw new \InvalidArgumentException(sprintf('Annotations from class "%s" cannot be read as it is abstract.', $class->getName()));
    }

    $annotationItem = new AnnotationItem();
    foreach ($class->getMethods() as $method) {
      $this->defaultIndex = 0;
      foreach ($this->reader->getMethodAnnotations($method) as $annot) {
        if ($annot instanceof $this->annotationClass) {
          $this->compile($annotationItem, $annot, $class, $method);
        }
      }
    }

    return $annotationItem;
  }

  /**
   * @param AnnotationItem $annotationItem
   * @param $annot
   * @param \ReflectionClass $class
   * @param \ReflectionMethod $method
   */
  protected function compile(AnnotationItem $annotationItem, $annot, \ReflectionClass $class, \ReflectionMethod $method){

    $annotationItem->add($this->getDefaultName($class,$method),$annot);
  }

  protected function getDefaultName(\ReflectionClass $class, \ReflectionMethod $method=null)
  {
    $name = str_replace('\\', '_', $class->name).($method != null ? '_'.$method->name:"");
    $name = \function_exists('mb_strtolower') && preg_match('//u', $name) ? mb_strtolower($name, 'UTF-8') : strtolower($name);
    if ($this->defaultIndex > 0) {
      $name .= '_'.$this->defaultIndex;
    }
    ++$this->defaultIndex;

    return $name;
  }

  protected function compileClass($annotationItem,$annot,\ReflectionCLass $class){
    $annotationItem->add($this->getDefaultName($class),$annot);
  }

  /**
   * {@inheritdoc}
   */
  public function supports($resource, $type = null)
  {
    return \is_string($resource) && preg_match('/^(?:\\\\?[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)+$/', $resource) && (!$type || 'annotation' === $type);
  }

  /**
   * {@inheritdoc}
   */
  public function setResolver(LoaderResolverInterface $resolver)
  {
  }

  /**
   * {@inheritdoc}
   */
  public function getResolver()
  {
  }
}

