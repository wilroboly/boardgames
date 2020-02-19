<?php

namespace Drupal\boardgames;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Boardgame entities.
 *
 * @ingroup boardgames
 */
class BoardgameListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Boardgame ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\boardgames\Entity\Boardgame $entity */
    $row['id'] =  Link::createFromRoute(
      $entity->id(),
      'entity.boardgame.canonical',
      ['boardgame' => $entity->id()]
    );;
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.boardgame.canonical',
      ['boardgame' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
