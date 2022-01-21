# AnnotationRoute Module

作者：Jarming

## Composer依赖
```
  composer require symfony/config
```

## PHP版本
```
  PHP_VERSION >= 7.2
```


## 说明
此模块结合了Symfony3.4框架的设计模式 和 Drupal Module的扩展功能进行实现。方便使用注解的方式来配置路由，而不是使用module_name.route.yml的方式配置路由。路由部分与symfony3.4的设计模式类似，详细的使用方式可以参照symfony和Drupal官方文档对Route的解释说明。

## 接入Symfony注解路由控制器及注解路由Form表单

注解路由控制器的详细使用方式，请参考Symfony官方注解路由文档。

Drupal Form表单注解路由的使用说明
#### 其使用方式与注解路由控制器类似，唯一不同的是其中的title，title可以在参数中直接定义，也可以像Drupal官方文档一样在 defaults={"_title":"自定义title"} 中定义

```php
  # 在 annotation_route/src/Form 下新建自定义Form表单TestAnnotationForm
  namespace Drupal\annotation_route\Form;

  use Drupal\annotation_route\Annotation\Form;use Drupal\Core\Form\FormBase;
  use Drupal\Core\Form\FormStateInterface;

  /**
   * Class TestForm
   * @package Drupal\annotation_route\Form
   * @Form(
   *   "/test/form/lll",
   *   title="这是一个测试表单注解路由，title参数为必选参数，title与defaults参数中的'_title'参数一致，title参数的优先级大于defaults参数中的_title"
   * )
   */
  class TestAnnotationForm extends FormBase
  {

    public function getFormId()
    {
      return "test_form_lll";
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
      return [
        "#type"=>"markup",
        "#markup" => "这是一个测试表单"
      ];
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
      return null;
    }
  }
```
#### 注：请勿将注解标签@Form用在function上，不然会有意想不到的问题，使用@Form注解时请在当前类中引入'use Drupal\annotation_route\Annotation\Form;'，否则会出错。


## 注解服务 （Annotation Service）
注解服务用于编译扫描class类和function上的注解，用于扫描和解析注解。注解扫描服务部分功能暂未完善，请谨慎使用。

用例：
```php
class TestAnnotationClass{
    public $author = "jarming";
    public $title = "";
    public $introduction;
}

/**
 * Class Account
 */
class AccountClass{
    /**
     * @TestAnnotationClass(author="LJM",title="ssss",introduction="这是一个注解类")
     */
    public function checkUser(){
        // pass
    }
}

$annotationService = \Drupal\annotation_route\AnnotationRouteClassFactory::AnnotationService();
$annotationService->initService();
$annotationService->setAnnotationClass(TestAnnotationClass::class); // 设置注解类
$annotationService->loadClass(AccountClass::class); // 指定扫描类形式
$annotationService->loadPath("/Users/jarming/……/modules/custom/annotation_route/src/Controller"); // 指定扫描路径，工具类会扫描该目录下中的 *.php 文件中的所有class类
