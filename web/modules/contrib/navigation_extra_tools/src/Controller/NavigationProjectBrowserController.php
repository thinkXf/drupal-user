<?php

declare(strict_types=1);

namespace Drupal\navigation_extra_tools\Controller;

use Drupal\project_browser\EnabledSourceHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns responses for Navigation Extra Tools routes.
 */
final class NavigationProjectBrowserController extends ReloadableControllerBase {

  /**
   * The controller constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\project_browser\EnabledSourceHandler $enabledSourceHandler
   *   The Project Browser source handler.
   */
  public function __construct(
    readonly RequestStack $requestStack,
    private readonly EnabledSourceHandler $enabledSourceHandler,
  ) {
    parent::__construct($requestStack);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('request_stack'),
      $container->get(EnabledSourceHandler::class),
    );
  }

  /**
   * Clears the project browser storage.
   */
  public function clearStorage() {
    $this->enabledSourceHandler->clearStorage();
    $this->messenger()->addStatus($this->t('Project Browser storage cleared.'));
    return new RedirectResponse($this->reloadPage());
  }

}
