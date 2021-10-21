<?php


namespace Drupal\annotation_route\Loaders;


use Drupal\annotation_route\AnnotationInterface;

class FormRouteAnnotationClassLoader extends AnnotationClassLoader
{
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
    $isCompile = false;
    foreach ($class->getMethods() as $method) {
      $this->defaultIndex = 0;
      foreach ($this->reader->getMethodAnnotations($method) as $annot) {
        if ($annot instanceof $this->annotationClass) {
          $this->compile($annotationItem, $annot, $class, $method);
          if (!$isCompile) $isCompile = true;
        }
      }
    }

    if ($isCompile == false){
      // 如果为false，则编译当前class
      foreach ( $this->reader->getClassAnnotations($class) as $classAnnotation){
        if ($classAnnotation instanceof $this->annotationClass){
          if ($classAnnotation instanceof AnnotationInterface){
            $classAnnotation->setClass($class->getName());
          }
          $this->compileClass($annotationItem,$classAnnotation,$class);
        }
      }

    }

    return $annotationItem;
  }
}
