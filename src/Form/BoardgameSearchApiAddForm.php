<?php

namespace Drupal\boardgames\Form;

use Drupal\boardgames\BoardgameManagerInterface;
use Drupal\boardgames\Entity\Boardgame;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BoardgameSearchApiAddForm.
 *
 * @ingroup boardgames
 */
class BoardgameSearchApiAddForm extends FormBase implements ContainerInjectionInterface {

  /**
   * The component key from the event type rule.
   *
   * @var string
   */
  var $boardgameId;

  /**
   * @var \Drupal\boardgames\BoardgameManagerInterface
   */
  protected $boardgameManager;

  /**
   * BoardgameSearchApiAddForm constructor.
   *
   * @TODO: Consider adding a mapping element to the settings page for field to
   * field values and machine names.
   *
   * @param \Drupal\boardgames\BoardgameManagerInterface $boardgame_manager
   */
  public function __construct(BoardgameManagerInterface $boardgame_manager) {
    $this->boardgameManager = $boardgame_manager;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return \Drupal\Core\Form\FormBase|static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('boardgames.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'boardgame_search_api_add';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Empty implementation of the abstract submit class.
  }

  /**
   * @return array
   */
  protected function fieldMapping() {
    return $this->boardgameManager->getFieldMapping();
  }

  /**
   * @return array
   */
  protected function fieldReferenceMapping() {
    return $this->boardgameManager->getFieldReferenceMapping();
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param null $api_boardgame
   *
   * @return array $form
   *   Returns a built form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $api_boardgame = NULL) {
    $values = [];
    $entity_type = 'boardgame';
    $this->boardgameManager->setCurrentApi();
    $this->boardgameId = $api_boardgame;

    if (empty($api_boardgame)) {
      return ['warning' => [
        '#markup' => $this->t('No Boardgame ID was provided.'),
      ]];
    }

    $this->boardgameManager->setApiSetting('url', $this->boardgameManager->getConfigKey('api_url') . '/search');
    $this->boardgameManager->setApiSetting('query', [
      'client_id' => $this->boardgameManager->getConfigKey('client_id'),
      'ids' => $api_boardgame,
    ]);

    $properties = ['field_game_id' => $api_boardgame];
    $boardgame_entity_storage = $this->boardgameManager->getEntityTypeManager()->getStorage($entity_type);
    $entity = $boardgame_entity_storage->loadByProperties($properties);

    if (reset($entity) instanceof Boardgame) {
      $build = $this->t('Boardgame already exists in the library!');
    }
    else {
      $entity = $this->boardgameManager->buildBoardgameEntityValues();
      $build = $this->boardgameManager->renderApiBoardgameEntity($entity, $entity_type);
    }

    if ($build) {
      $form['game'] = [
        '#markup' => render($build),
      ];
    }

    $url = Url::fromRoute('boardgame.search_api_form')->toString();

    $form['search'] = [
      '#markup' => $this->t('<p><a href="@url" class="button button--primary">Search Again</a></p>', ['@url' => $url]),
    ];

    return $form;
  }

}
