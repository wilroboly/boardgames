<?php

namespace Drupal\boardgames\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a Boardgame revision.
 *
 * @ingroup boardgames
 */
class BoardgameRevisionDeleteForm extends ConfirmFormBase {

  /**
   * The Boardgame revision.
   *
   * @var \Drupal\boardgames\Entity\BoardgameInterface
   */
  protected $revision;

  /**
   * The Boardgame storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $boardgameStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->boardgameStorage = $container->get('entity_type.manager')->getStorage('boardgame');
    $instance->connection = $container->get('database');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'boardgame_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the revision from %revision-date?', [
      '%revision-date' => format_date($this->revision->getRevisionCreationTime()),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.boardgame.version_history', ['boardgame' => $this->revision->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $boardgame_revision = NULL) {
    $this->revision = $this->BoardgameStorage->loadRevision($boardgame_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->BoardgameStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('Boardgame: deleted %title revision %revision.', ['%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    $this->messenger()->addMessage(t('Revision from %revision-date of Boardgame %title has been deleted.', ['%revision-date' => format_date($this->revision->getRevisionCreationTime()), '%title' => $this->revision->label()]));
    $form_state->setRedirect(
      'entity.boardgame.canonical',
       ['boardgame' => $this->revision->id()]
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {boardgame_field_revision} WHERE id = :id', [':id' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.boardgame.version_history',
         ['boardgame' => $this->revision->id()]
      );
    }
  }

}
