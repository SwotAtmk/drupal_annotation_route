<?php


namespace Drupal\annotation_route;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * 移植Symfony3.4 中的底层控制器
 * Class BaseController
 * @package Drupal\annotation_route
 */
abstract class BaseController implements ContainerAwareInterface
{
  use ContainerAwareTrait;


  /**
   * Gets a container configuration parameter by its name.
   *
   * @param string $name The parameter name
   *
   * @return mixed
   *
   * @final since version 3.4
   */
  protected function getParameter($name)
  {
    return $this->container->getParameter($name);
  }
  /**
   * Returns true if the service id is defined.
   *
   * @param string $id The service id
   *
   * @return bool true if the service id is defined, false otherwise
   *
   * @final since version 3.4
   */
  protected function has($id)
  {
    return $this->container->has($id);
  }

  /**
   * Gets a container service by its id.
   *
   * @param string $id The service id
   *
   * @return object The service
   *
   * @final since version 3.4
   */
  protected function get($id)
  {
    return $this->container->get($id);
  }

  /**
   * Generates a URL from the given parameters.
   *
   * @param string $route         The name of the route
   * @param array  $parameters    An array of parameters
   * @param int    $referenceType The type of reference (one of the constants in UrlGeneratorInterface)
   *
   * @return string The generated URL
   *
   * @see UrlGeneratorInterface
   *
   * @final since version 3.4
   */
  protected function generateUrl($route, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
  {
    return $this->container->get('router')->generate($route, $parameters, $referenceType);
  }

  /**
   * Forwards the request to another controller.
   *
   * @param string $controller The controller name (a string like BlogBundle:Post:index)
   * @param array  $path       An array of path parameters
   * @param array  $query      An array of query parameters
   *
   * @return Response A Response instance
   *
   * @final since version 3.4
   */
  protected function forward($controller, array $path = array(), array $query = array())
  {
    $request = $this->container->get('request_stack')->getCurrentRequest();
    $path['_forwarded'] = $request->attributes;
    $path['_controller'] = $controller;
    $subRequest = $request->duplicate($query, null, $path);

    return $this->container->get('http_kernel')->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
  }

  /**
   * Returns a RedirectResponse to the given URL.
   *
   * @param string $url    The URL to redirect to
   * @param int    $status The status code to use for the Response
   *
   * @return RedirectResponse
   *
   * @final since version 3.4
   */
  protected function redirect($url, $status = 302)
  {
    return new RedirectResponse($url, $status);
  }

  /**
   * Returns a RedirectResponse to the given route with the given parameters.
   *
   * @param string $route      The name of the route
   * @param array  $parameters An array of parameters
   * @param int    $status     The status code to use for the Response
   *
   * @return RedirectResponse
   *
   * @final since version 3.4
   */
  protected function redirectToRoute($route, array $parameters = array(), $status = 302)
  {
    return $this->redirect($this->generateUrl($route, $parameters), $status);
  }

  /**
   * Returns a JsonResponse that uses the serializer component if enabled, or json_encode.
   *
   * @param mixed $data    The response data
   * @param int   $status  The status code to use for the Response
   * @param array $headers Array of extra headers to add
   * @param array $context Context to pass to serializer when using serializer component
   *
   * @return JsonResponse
   *
   * @final since version 3.4
   */
  protected function json($data, $status = 200, $headers = array(), $context = array())
  {
    if ($this->container->has('serializer')) {
      $json = $this->container->get('serializer')->serialize($data, 'json', array_merge(array(
        'json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS,
      ), $context));

      return new JsonResponse($json, $status, $headers, true);
    }

    return new JsonResponse($data, $status, $headers);
  }

  /**
   * Returns a BinaryFileResponse object with original or customized file name and disposition header.
   *
   * @param \SplFileInfo|string $file        File object or path to file to be sent as response
   * @param string|null         $fileName    File name to be sent to response or null (will use original file name)
   * @param string              $disposition Disposition of response ("attachment" is default, other type is "inline")
   *
   * @return BinaryFileResponse
   *
   * @final since version 3.4
   */
  protected function file($file, $fileName = null, $disposition = ResponseHeaderBag::DISPOSITION_ATTACHMENT)
  {
    $response = new BinaryFileResponse($file);
    $response->setContentDisposition($disposition, null === $fileName ? $response->getFile()->getFilename() : $fileName);

    return $response;
  }

  /**
   * Adds a flash message to the current session for type.
   *
   * @param string $type    The type
   * @param string $message The message
   *
   * @throws \LogicException
   *
   * @final since version 3.4
   */
  protected function addFlash($type, $message)
  {
    if (!$this->container->has('session')) {
      throw new \LogicException('You can not use the addFlash method if sessions are disabled. Enable them in "config/packages/framework.yaml".');
    }

    $this->container->get('session')->getFlashBag()->add($type, $message);
  }

  /**
   * Checks if the attributes are granted against the current authentication token and optionally supplied subject.
   *
   * @param mixed $attributes The attributes
   * @param mixed $subject    The subject
   *
   * @return bool
   *
   * @throws \LogicException
   *
   * @final since version 3.4
   */
  protected function isGranted($attributes, $subject = null)
  {
  }

  /**
   * Throws an exception unless the attributes are granted against the current authentication token and optionally
   * supplied subject.
   *
   * @param mixed  $attributes The attributes
   * @param mixed  $subject    The subject
   * @param string $message    The message passed to the exception
   *
   *
   * @final since version 3.4
   */
  protected function denyAccessUnlessGranted($attributes, $subject = null, $message = 'Access Denied.')
  {
  }

  /**
   * Returns a rendered view.
   *
   * @param string $view       The view name
   * @param array  $parameters An array of parameters to pass to the view
   *
   * @return string The rendered view
   *
   * @final since version 3.4
   */
  protected function renderView($view, array $parameters = array())
  {
    if (!$this->container->has('twig')) {
      throw new \LogicException('You can not use the "renderView" method if the Templating Component or the Twig Bundle are not available. Try running "composer require symfony/twig-bundle".');
    }

    return $this->container->get('twig')->render($view, $parameters);
  }

  /**
   * Renders a view.
   *
   * @param string   $view       The view name
   * @param array    $parameters An array of parameters to pass to the view
   * @param Response $response   A response instance
   *
   * @return Response A Response instance
   *
   * @final since version 3.4
   */
  protected function render($view, array $parameters = array(), Response $response = null)
  {
    if ($this->container->has('twig')) {
      $content = $this->container->get('twig')->render($view, $parameters);
    } else {
      throw new \LogicException('You can not use the "render" method if the Templating Component or the Twig Bundle are not available. Try running "composer require symfony/twig-bundle".');
    }

    if (null === $response) {
      $response = new Response();
    }

    $response->setContent($content);

    return $response;
  }

  /**
   * Streams a view.
   *
   * @param string           $view       The view name
   * @param array            $parameters An array of parameters to pass to the view
   * @param StreamedResponse $response   A response instance
   *
   * @return StreamedResponse A StreamedResponse instance
   *
   * @final since version 3.4
   */
  protected function stream($view, array $parameters = array(), StreamedResponse $response = null)
  {
    if($this->container->has('twig')) {
      $twig = $this->container->get('twig');

      $callback = function () use ($twig, $view, $parameters) {
        $twig->display($view, $parameters);
      };
    } else {
      throw new \LogicException('You can not use the "stream" method if the Templating Component or the Twig Bundle are not available. Try running "composer require symfony/twig-bundle".');
    }

    if (null === $response) {
      return new StreamedResponse($callback);
    }

    $response->setCallback($callback);

    return $response;
  }

  /**
   * Returns a NotFoundHttpException.
   *
   * This will result in a 404 response code. Usage example:
   *
   *     throw $this->createNotFoundException('Page not found!');
   *
   * @param string          $message  A message
   * @param \Exception|null $previous The previous exception
   *
   * @return NotFoundHttpException
   *
   * @final since version 3.4
   */
  protected function createNotFoundException($message = 'Not Found', \Exception $previous = null)
  {
    return new NotFoundHttpException($message, $previous);
  }

  /**
   * Returns an AccessDeniedException.
   *
   * This will result in a 403 response code. Usage example:
   *
   *     throw $this->createAccessDeniedException('Unable to access this page!');
   *
   * @param string          $message  A message
   * @param \Exception|null $previous The previous exception
   *
   */
  protected function createAccessDeniedException($message = 'Access Denied.', \Exception $previous = null)
  {
    if (!class_exists(AccessDeniedHttpException::class)) {
      throw new \LogicException('You can not use the "AccessDeniedHttpException" method if the Security component is not available.');
    }

    return new AccessDeniedHttpException($message,$previous);
  }

  /**
   * Creates and returns a Form instance from the type of the form.
   *
   * @param string $type    The fully qualified class name of the form type
   * @param mixed  $data    The initial data for the form
   * @param array  $options Options for the form
   *
   *
   */
  protected function createForm($type, $data = null, array $options = array())
  {

  }

  /**
   * Creates and returns a form builder instance.
   *
   * @param mixed $data    The initial data for the form
   * @param array $options Options for the form
   *
   *
   */
  protected function createFormBuilder($data = null, array $options = array())
  {

  }

  /**
   * 获取管理
   */
  protected function getEntityTypeStorage($entity_type_id)
  {
    return AnnotationRouteClassFactory::getEntityTypeStorage($entity_type_id);
  }

  /**
   * 获取用户。
   *
   * @return \Drupal\Core\Session\AccountProxy|object|null
   *
   * @final since version 3.4
   */
  protected function getUser()
  {
    return AnnotationRouteClassFactory::User();
  }

  /**
   * @return \Drupal\Core\Session\AccountProxy|object|null|bool
   */
  protected function checkLogin(){
    if ($this->getUser()->isAnonymous()) return false;
    return $this->getUser();
  }


}
