<?php

namespace Drupal\boardgames\Form;

use Drupal\boardgames\BoardgameBatch;
use Drupal\boardgames\BoardgameManagerInterface;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\Html;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\user\UserDataInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BoardgameBulkImportForm extends ConfirmFormBase implements ContainerInjectionInterface {

  /**
   * The submitted data needing to be confirmed.
   *
   * @var array
   */
  protected $data = [];

  /**
   * The component key from the event type rule.
   *
   * @var string
   */
  var $boardgameId;

  /**
   * The Drupal account to use for checking for access to block.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * @var \Drupal\boardgames\BoardgameManagerInterface
   */
  protected $boardgameManager;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The import status flag stating whether its an new import or an update.
   *
   * @var bool
   */
  protected $importStatus = FALSE;

  /**
   * The entity repository interface object.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface|null
   */
  protected $entityRepository;

  /**
   * Constructs a new BoardgameBulkImportForm.
   *
   * @TODO: Consider adding a mapping element to the settings page for field to
   * field values and machine names.
   *
   * @param \Drupal\boardgames\BoardgameManagerInterface $boardgame_manager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\user\UserStorageInterface $user_storage
   * @param \Drupal\user\UserDataInterface $user_data
   * @param \Drupal\Core\Session\AccountInterface $current_user
   * @param \Drupal\Core\Entity\EntityRepositoryInterface|null $entity_repository
   */
  public function __construct(BoardgameManagerInterface $boardgame_manager, EntityTypeManagerInterface $entity_type_manager, UserStorageInterface $user_storage, UserDataInterface $user_data, AccountInterface $current_user, EntityRepositoryInterface $entity_repository = NULL) {
    $this->boardgameManager = $boardgame_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->userStorage = $user_storage;
    $this->userData = $user_data;
    $this->currentUser = $current_user;
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('boardgames.manager'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('user.data'),
      $container->get('current_user'),
      $container->get('entity.repository')
    );
  }

  /**
   * @inheritDoc
   */
  public function getQuestion() {
    $number = 1;
    $list_array = [];
    $list = implode(', ', $list_array);

    $args = [
      '%number' => $number,
      '@list' => $list,
    ];

    if ($this->importStatus) {
      $question = $this->t('Are you sure you want to update these %number boardgames: @list?', $args);
    }
    else {
      $question = $this->t('Are you sure you want to create these %number new boardgames: @list?', $args);
    }
    return $question;
  }

  /**
   * @inheritDoc
   */
  public function getCancelUrl() {
    return new Url('boardgame.bulk_import_form');
  }

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'boardgame_bulk_import_from_api';
  }

  /**
   * @inheritDoc
   * @throws \Exception
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // When this is the confirmation step fall through to the confirmation form.
    if ($this->data) {
      return parent::buildForm($form, $form_state);
    }

    $vid = 'conference_years';
    /** @var \Drupal\taxonomy\TermInterface[] $terms */
    try {
      $terms = $this->entityTypeManager->getStorage('taxonomy_term')
        ->loadTree($vid, 0, NULL, TRUE);
    }
    catch (\Exception $e) {
      throw new \Exception('There is no vocabulary by that name: ' . $vid);
    }

    foreach ($terms as $term) {
      $convention_years[$term->id()] = str_repeat('-', $term->depth) . Html::escape($this->entityRepository->getTranslationFromContext($term)->label());
    }
    $form['convention_years'] = [
      '#title' => $this->t('Convention Years'),
      '#type' => 'select',
      '#options' => $convention_years,
      '#required' => TRUE,
      '#description' => $this->t('This value will be attributed to each game being imported. It is important to note that each game coming into the list should not be repeated, otherwise the convention year will change.'),
    ];
    $form['import'] = [
      '#title' => $this->t('Paste your import list here'),
      '#type' => 'textarea',
      '#rows' => 24,
      '#required' => TRUE,
      '#description' => $this->t('The list can handle as many as you can throw at it. Just remember, the bulk import will batch through it all but it could take quite some time.'),
    ];

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced'),
    ];
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->userStorage->load($this->currentUser->id());
    $form['advanced']['author_id'] = [
      '#title' => $this->t('Author of the game.'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#default_value' => $user,
      '#description' => $this->t('Unless otherwise selected, the author of the game will be who ever the account logged in performing the import.'),
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
      '#button_type' => 'primary',
    ];
      return $form;
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $game_list = [];
    $unfiltered_list = explode( "\n", $form_state->getValue('import'));
    $counter = 0;
    foreach ($unfiltered_list as $key => $value) {
      $counter++;
      $value = trim($value, " \r");
      $game_list[$key] = $value;
    }
    $author_id = $form_state->getValue('author_id');
    $convention_years =  $form_state->getValue('convention_years');

    $batch = [
      'title' => t('Importing Boardgames...'),
      'operations' => [
        [
          [BoardgameBatch::class,'boardgameImport'],
          [$game_list, $author_id, $convention_years],
        ],
      ],
      'finished' => [BoardgameBatch::class, 'boardgameImportFinishedCallback'],
    ];
    batch_set($batch);

  }


}
