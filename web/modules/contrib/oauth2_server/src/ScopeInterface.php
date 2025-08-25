<?php

namespace Drupal\oauth2_server;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the interface for scope entities.
 *
 * @package Drupal\oauth2_server
 */
interface ScopeInterface extends ConfigEntityInterface {

  /**
   * Returns the server the scope belongs to.
   *
   * @return \Drupal\oauth2_server\ServerInterface
   *   Returns the server object the scope belongs to.
   */
  public function getServer();

  /**
   * Returns whether the scope is the default server scope.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function isDefault();

}
