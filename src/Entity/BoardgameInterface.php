<?php

namespace Drupal\boardgames\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Boardgame entities.
 *
 * @ingroup boardgames
 */
interface BoardgameInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Boardgame name.
   *
   * @return string
   *   Name of the Boardgame.
   */
  public function getName();

  /**
   * Sets the Boardgame name.
   *
   * @param string $name
   *   The Boardgame name.
   *
   * @return \Drupal\boardgames\Entity\BoardgameInterface
   *   The called Boardgame entity.
   */
  public function setName($name);

  /**
   * Gets the Boardgame creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Boardgame.
   */
  public function getCreatedTime();

  /**
   * Sets the Boardgame creation timestamp.
   *
   * @param int $timestamp
   *   The Boardgame creation timestamp.
   *
   * @return \Drupal\boardgames\Entity\BoardgameInterface
   *   The called Boardgame entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Boardgame revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Boardgame revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\boardgames\Entity\BoardgameInterface
   *   The called Boardgame entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Boardgame revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Boardgame revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\boardgames\Entity\BoardgameInterface
   *   The called Boardgame entity.
   */
  public function setRevisionUserId($uid);

}
