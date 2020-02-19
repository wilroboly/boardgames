<?php

namespace Drupal\boardgames;

use Drupal\boardgames\Entity\Boardgame;

class BoardgameBatch {

  public static function boardgameImport($game_list, $author_id, $convention_years, &$context) {
    $count = count($game_list);
    $message = \Drupal::translation()->formatPlural(
      $count,
      'Importing a boardgame...',
      'Importing @count boardgames...'
    );
    drupal_set_message($message);

    $context = BoardgameBatch::initializeSandbox($count, $context);
    $max = BoardgameBatch::batchLimit($context);

    // Start where we left off last time.
    $start = $context['sandbox']['progress'];
    for ($i = $start; $i < $max; $i++) {

      $result = BoardgameBatch::doProcess($game_list[$i], $author_id, $convention_years);

      // We want to display the counter 1-based, not 0-based.
      $counter = $i + 1;
      if ($result['message'] == '') {
        drupal_set_message($counter . '. ' . $result['result']['name'] . ' ' . $result['result']['status']);
      }
      else {
        drupal_set_message($counter . '. ' . $result['result']['name'] . ': ' . $result['message']);
      }

      // Update our progress!
      $context['sandbox']['progress']++;
    }

    $context = self::contextProgress($context);
  }

  /**
   * @param $context
   *
   * @return mixed
   */
  protected static function contextProgress(&$context) {
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
    return $context;
  }

  public static function boardgameImportFinishedCallback($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One boardgame processed.',
        'All boardgames processed.'
      );
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }

  /**
   * @param $number
   * @param $context
   *
   * @return mixed
   */
  protected static function initializeSandbox($number, &$context) {
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = $number;
      $context['sandbox']['working_set'] = [];
    }
    return $context;
  }

  /**
   * @param $context
   *
   * @return int|mixed
   */
  protected static function batchLimit(&$context) {
    $batchSize = 1;

    $max = $context['sandbox']['progress'] + $batchSize;
    if ($max > $context['sandbox']['max']) {
      $max = $context['sandbox']['max'];
    }
    return $max;
  }

  protected static function doProcess($boardgame_name, $author_id, $convention_years) {
    $results = [];
    $message = '';
    $entity_type = 'boardgame';
    $boardgameManager = \Drupal::service('boardgames.manager');
    $config = $boardgameManager->getConfigFactory()->get('boardgames.settings');
    $boardgame_entity_storage = $boardgameManager->getEntityTypeManager()->getStorage($entity_type);

    $boardgameManager->setApiSetting('url', $config->get('api_url') . '/search');
    $query = [
      'client_id' => $config->get('client_id'),
      'exact' => 'TRUE',
      'fuzzy_match' => 'FALSE',
    ];
    $boardgameManager->setApiSetting('query', $query);
    $boardgameManager->resetBoardgameObject();
    $properties = ['name' => $boardgame_name];
    $entity = $boardgame_entity_storage->loadByProperties($properties);

    if (reset($entity) instanceof Boardgame) {
      $message = 'Boardgame already exists in the library!';
      $result = ['name' => $boardgame_name, 'status' => 'exists'];
    }
    else {
      $query['name'] = $boardgame_name;
      $boardgameManager->setApiSetting('query', $query);
      $boardgameManager->getApiResponse();
      $response = reset($boardgameManager->getApiResponseObject());
      if ($response != NULL) {
        if ($boardgameManager->bestBoardgameObject('name', $boardgame_name)) {
          $boardgameManager->setBoardgameFieldValues();
          $boardgameManager->setBoardgameFieldReferenceValues();
          $entity = $boardgameManager->createApiBoardgameEntity();
          $entity->set('user_id', $author_id);
          $entity->set('field_convention_year', $convention_years);
          $entity->save();
          $result = ['name' => $boardgame_name, 'status' => 'added'];
          unset($entity);
        }
        else {
          $result = ['name' => $boardgame_name, 'status' => 'could not find it!'];
        }
      }
      else {
        $result = ['name' => $boardgame_name, 'status' => 'could not find it!'];
      }
    }

    return [
      'message' => $message,
      'result' => $result,
    ];
  }

}
