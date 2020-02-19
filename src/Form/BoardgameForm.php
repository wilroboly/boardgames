<?php

namespace Drupal\boardgames\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Boardgame edit forms.
 *
 * @ingroup boardgames
 */
class BoardgameForm extends ContentEntityForm {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * @var \Drupal\boardgames\BoardgameManagerInterface
   */
  protected $boardgameManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = parent::create($container);
    $instance->account = $container->get('current_user');
    $instance->boardgameManager = $container->get('boardgames.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\boardgames\Entity\Boardgame $entity */
    $form = parent::buildForm($form, $form_state);

    if (!$this->entity->isNew()) {
      $form['new_revision'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Create new revision'),
        '#default_value' => FALSE,
        '#weight' => 10,
      ];

      $form['actions']['update'] = [
        '#type' => 'submit',
        '#name' => 'update',
        '#value' => 'Update from API',
        '#submit' => [[$this, 'submitUpdateFromApi']],
        '#button_type' => 'primary',
        '#weight' => $form['actions']['submit']['#weight'] + 1,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {
      $entity->setNewRevision();

      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime($this->time->getRequestTime());
      $entity->setRevisionUserId($this->account->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Boardgame.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Boardgame.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.boardgame.canonical', ['boardgame' => $entity->id()]);
  }

  public function submitUpdateFromApi(array &$form, FormStateInterface $form_state) {
    $entity_type = 'boardgame';
    $config = $this->boardgameManager->getConfigFactory()->get('boardgames.settings');

    $this->boardgameManager->setApiSetting('url', $config->get('api_url') . '/search');
    $this->boardgameManager->setApiSetting('query', [
      'client_id' => $config->get('client_id'),
      'ids' => $form_state->getValue(['field_game_id', 0, 'value']),
    ]);

    $this->boardgameManager->getApiResponse('boardgame_atlas');
    $this->boardgameManager->setBoardgameObject();
    $this->boardgameManager->setBoardgameFieldValues();
    $this->boardgameManager->setBoardgameFieldReferenceValues();

    $form_state->cleanValues();
    $this->entity = $this->buildEntity($form, $form_state);
    $this->boardgameManager->updateBoardgameFormFields($this->entity);
    $this->save($form, $form_state);
  }

}
