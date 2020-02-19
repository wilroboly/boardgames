<?php

namespace Drupal\boardgames;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Boardgame entity.
 *
 * @see \Drupal\boardgames\Entity\Boardgame.
 */
class BoardgameAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\boardgames\Entity\BoardgameInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished boardgame entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published boardgame entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit boardgame entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete boardgame entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add boardgame entities');
  }


}
