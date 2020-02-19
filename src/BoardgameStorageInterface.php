<?php

namespace Drupal\boardgames;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\boardgames\Entity\BoardgameInterface;

/**
 * Defines the storage handler class for Boardgame entities.
 *
 * This extends the base storage class, adding required special handling for
 * Boardgame entities.
 *
 * @ingroup boardgames
 */
interface BoardgameStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Boardgame revision IDs for a specific Boardgame.
   *
   * @param \Drupal\boardgames\Entity\BoardgameInterface $entity
   *   The Boardgame entity.
   *
   * @return int[]
   *   Boardgame revision IDs (in ascending order).
   */
  public function revisionIds(BoardgameInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Boardgame author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Boardgame revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\boardgames\Entity\BoardgameInterface $entity
   *   The Boardgame entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(BoardgameInterface $entity);

  /**
   * Unsets the language for all Boardgame with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
