<?php

namespace Drupal\oauth2_server;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines the interface for token entities.
 *
 * @package Drupal\oauth2_server
 */
interface TokenInterface extends ContentEntityInterface {

  /**
   * Return the user the token belongs to.
   *
   * @return \Drupal\user\UserInterface
   *   The user object or FALSE.
   */
  public function getUser();

  /**
   * Return the client the token belongs to.
   *
   * @return \Drupal\oauth2_server\ClientInterface
   *   The client object or FALSE.
   */
  public function getClient();

}
