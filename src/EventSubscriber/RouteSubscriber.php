<?php

namespace Drupal\annotation_route\EventSubscriber;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Drupal\annotation_route\Annotation\Form;
use Drupal\annotation_route\AnnotationRouteClassFactory;
use Drupal\annotation_route\AnnotationInterface;
use Drupal\annotation_route\Loaders\AnnotationItem;
use Drupal\annotation_route\Loaders\AnnotationRouteAnnotationClassLoader;
use Drupal\annotation_route\Loaders\FormRouteAnnotationClassLoader;
use Drupal\Core\Routing\RouteCompiler;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Loader\AnnotationDirectoryLoader;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route as SymfonyRoute;

/**
 * 路由事件订阅者
 * Class RouteSubscriber
 * @package Drupal\annotation_route\Routing
 */
class RouteSubscriber extends RouteSubscriberBase
{

  // 注解路由扫描目录路径
  private $ROUTE_ANNOTATION_SCAN_PATHS;

  public function __construct()
  {
    $this->getRouteAnnotationScanPaths(); // 设置扫描路径
  }

  /**
   * 设置路由扫描路径
   */
  private function getRouteAnnotationScanPaths(){
    //  扫描所有模块 Controller 目录
    $namespaces = [];
    $module_handler = \Drupal::moduleHandler();
    foreach ($module_handler->getModuleList() as $name => $extension) {
      $namespaces[$name] = $extension->getPath();
    }

    foreach ($namespaces as $name => $path) {
      $path = \Drupal::root().DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR."src".DIRECTORY_SEPARATOR;
      // 判断文件目录是否存在，存在则注入到扫描目录中
      if (is_dir($path)){
        $this->ROUTE_ANNOTATION_SCAN_PATHS[$name] = $path;
      }
    }
  }

  /**
   * 路由订阅者事件，扫描注解路由
   * @param RouteCollection $collection
   * @return RouteCollection
   * @throws AnnotationException
   */
  protected function alterRoutes(RouteCollection $collection)
  {
    // 请勿删除此代码，class_exists方法会触发自动加载Route::class类，避免在后面对注解类进行注入时出现路由未被composer自动加载的异常。
    if (!class_exists(Route::class)) {
      throw new AnnotationException(t('这个动态路由注解类 "@Symfony\Component\Routing\Annotation\Route" 不存在，或未被自动加载. '));
    }
    $fileLocator = new FileLocator(); // 文件加载类
    $sar = new AnnotationReader();// 注解渲染类
    $sar->addGlobalIgnoredName("endlink"); // 添加忽略 @endlink 注解，在扫描@endlink时会遇到异常
    $annotationClassLoader = new AnnotationRouteAnnotationClassLoader($sar); // 控制器注解路由加载配置类
    $annotationDirectoryLoader = new AnnotationDirectoryLoader($fileLocator,$annotationClassLoader); // 注解目录加载类
    //按顺序加载对应的路由
    foreach ($this->ROUTE_ANNOTATION_SCAN_PATHS as $path){
      if (is_dir($path."Controller")){
          try{
            $annotationRouteCollection = $annotationDirectoryLoader->load($path."Controller"); // 扫描注解路由到RouteCollection
            $collection->addCollection($annotationRouteCollection);//将路由添加到RouteCollection
          }catch (\Exception $annotationException){
            AnnotationRouteClassFactory::logger()->error($annotationException->getMessage());
          }
      }
    }

    $annotationService = AnnotationRouteClassFactory::AnnotationService();
    $annotationService->initService(); // 初始化注解服务
    $annotationService->setClassLoader(FormRouteAnnotationClassLoader::class);
    $annotationService->setAnnotationClass(Form::class);
    foreach ($this->ROUTE_ANNOTATION_SCAN_PATHS as $path){
      if (is_dir($path."Form")){
          try {
              $formRouteAnnotations = $annotationService->loadPath($path . "Form");
              $form_collection = $this->compileFormRouteAnnotations($formRouteAnnotations);
              $collection->addCollection($form_collection);
          }catch (\Exception $exception){
              AnnotationRouteClassFactory::logger()->error($exception->getMessage());
          }
      }
    }

    return $collection;
  }

  /**
   * 编译Form表单路由注解
   * @param AnnotationItem $formRouteAnnotations
   * @return RouteCollection
   * @throws \Exception
   */
  private function compileFormRouteAnnotations(AnnotationItem $formRouteAnnotations){


    $route_collection = new RouteCollection();
    /** @var Form $formRouteAnnotation */
    foreach ($formRouteAnnotations->all() as $name => $formRouteAnnotation){
      if ($formRouteAnnotation instanceof AnnotationInterface){
        if(!empty($formRouteAnnotation->getName())){
          $name = $formRouteAnnotation->getName();
        }
        $name = "annotation_route_module.".$name;
        $defaults = $formRouteAnnotation->getDefaults();
        $title = $formRouteAnnotation->getTitle();
        if (empty($title)){
          if (!isset($defaults["_title"]) || empty($defaults["_title"])) throw new \Exception(t("请设置表单路由的'_title'参数在defaults中!!!"));
          $title = $defaults["_title"];
        }
        $defaults["_title"] = $title;
        $defaults["_form"] = "\\".$formRouteAnnotation->getClass();
        $requirements = $formRouteAnnotation->getRequirements();
        if (!isset($requirements["_permission"]) || empty($requirements["_permission"])) $requirements["_permission"] = "access content";
        $route = $this->createFormRoute($formRouteAnnotation->getPath(),$defaults,$requirements,$formRouteAnnotation->getOptions(),$formRouteAnnotation->getHost(),$formRouteAnnotation->getSchemes(),$formRouteAnnotation->getMethods(),$formRouteAnnotation->getCondition());
        $route_collection->add($name,$route);
      }
    }
    return $route_collection;
  }

  private function createFormRoute($path, $defaults, $requirements, $options, $host, $schemes, $methods, $condition)
  {
    $options["compiler_class"] = RouteCompiler::class;    // 设置由Drupal提供的路由编译器
    return new SymfonyRoute($path, $defaults, $requirements, $options, $host, $schemes, $methods, $condition);
  }

}
