<?php


namespace Drupal\annotation_route\Loaders;


use Symfony\Component\Config\Resource\DirectoryResource;

class AnnotationDirectoryLoader extends AnnotationFileLoader
{
  /**
   * Loads from annotations from a directory.
   *
   * @param string      $path A directory path
   * @param string|null $type The resource type
   *
   * @return AnnotationItem A RouteCollection instance
   *
   * @throws \InvalidArgumentException When the directory does not exist or its routes cannot be parsed
   */
  public function load($path, $type = null)
  {
    if (!is_dir($dir = $this->locator->locate($path))) {
      return parent::supports($path, $type) ? parent::load($path, $type) : new AnnotationItem();
    }

    $collection = new AnnotationItem();
    $collection->addResource(new DirectoryResource($dir, '/\.php$/'));
    $files = iterator_to_array(new \RecursiveIteratorIterator(
      new \RecursiveCallbackFilterIterator(
        new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS),
        function (\SplFileInfo $current) {
          return '.' !== substr($current->getBasename(), 0, 1);
        }
      ),
      \RecursiveIteratorIterator::LEAVES_ONLY
    ));
    usort($files, function (\SplFileInfo $a, \SplFileInfo $b) {
      return (string) $a > (string) $b ? 1 : -1;
    });

    foreach ($files as $file) {
      if (!$file->isFile() || '.php' !== substr($file->getFilename(), -4)) {
        continue;
      }

      if ($class = $this->findClass($file)) {
        $refl = new \ReflectionClass($class);
        if ($refl->isAbstract()) {
          continue;
        }

        $collection->addCollection($this->loader->load($class, $type));
      }
    }

    return $collection;
  }

  /**
   * {@inheritdoc}
   */
  public function supports($resource, $type = null)
  {
    if ('annotation' === $type) {
      return true;
    }

    if ($type || !\is_string($resource)) {
      return false;
    }

    try {
      return is_dir($this->locator->locate($resource));
    } catch (\Exception $e) {
      return false;
    }
  }
}
