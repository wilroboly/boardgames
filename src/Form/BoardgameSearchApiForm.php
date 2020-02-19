<?php

namespace Drupal\boardgames\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\boardgames\Truncate\TruncateHtml;
use \Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BoardgameSettingsForm.
 *
 * @ingroup boardgames
 */
class BoardgameSearchApiForm extends FormBase {

  protected $limit;
  protected $skip;

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
   * Private Temp Store Factory service.
   *
   * @var PrivateTempStoreFactory
   */
  protected $privateTempStore;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = parent::create($container);
    $instance->account = $container->get('current_user');
    $instance->boardgameManager = $container->get('boardgames.manager');
    $instance->privateTempStore = $container->get('user.private_tempstore');
    return $instance;
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'boardgame_search_api';
  }

  /**
   * Defines the settings form for Boardgame entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->boardgameManager->setCurrentApi();
    $tempStore = $this->privateTempStore->get('boardgame');
    if (!empty($tempStore->get('limit'))) {
      $this->limit = $tempStore->get('limit');
    }
    if (!empty($tempStore->get('skip'))) {
      $this->skip = $tempStore->get('skip');
    }

    $query_parameters = \Drupal::request()->query->all();

    $form['#attached']['library'][] = 'core/drupal.ajax';

    $form['#prefix'] = '<div id="boardgame_ajax_form">';
    $form['#suffix'] = '</div>';

    $form['search_terms'] = [
      '#description' => $this->t(''),
      '#type' => 'textfield',
      '#attributes' => [
        'placeholder' => $this->t('search for boargame : enter a name for a game'),
      ],
      '#default_value' => isset($query_parameters['search_terms']) ? $query_parameters['search_terms'] : NULL,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Go'),
    ];

    $name = '';
    if (isset($query_parameters['search_terms'])) {
      $name = $query_parameters['search_terms'];
    }

    if (isset($query_parameters['limit'])) {
      $limit = $query_parameters['limit'];
    }
    elseif (isset($this->limit)) {
      $limit = $this->limit;
    }
    else {
      $limit = 30;
    }
    $tempStore->set('limit', $limit);

    $form['limit'] = [
      '#type' => 'hidden',
      '#default_value' => $limit,
      '#attributes' => [
        'id' => ['edit-limit'],
      ],
    ];

    if (isset($query_parameters['skip'])) {
      $skip = $query_parameters['skip'];
    }
    elseif (isset($this->skip)) {
      $skip = $this->skip;
    }
    else {
      $skip = 0;
    }
    $tempStore->set('skip', $skip);

    $form['skip'] = [
      '#type' => 'hidden',
      '#default_value' => $skip,
      '#attributes' => [
        'id' => ['edit-skip'],
      ],
    ];

    $default_options = [
      'name' => $name,
      'exact' => 'false',
      'fuzzy_match' => 'false',
      'limit' => $limit,
      'skip' => $skip,
    ];

    $search_output = $this->doBoardgameSearch($default_options);
    $form += $this->doBuildTable($search_output);

    if (count($search_output['games']) == 30) {
      $form['load_more'] = [
        '#type' => 'submit',
        '#name' => 'load_more',
        '#value' => 'Load More Games',
        '#submit' => [[$this, 'submitLoadMore']],
        '#ajax' => [
          'callback' => [$this, 'searchAjaxLoadMore'],
          'event' => 'click',
          'wrapper' => 'boardgame_ajax_form',
        ],
      ];
    }
    return $form;
  }

  public function doBoardgameSearch(array $options = []) {
    $entity_type = 'boardgame';
    $query = ['client_id' => $this->boardgameManager->getConfigKey('client_id')];
    foreach ($options as $key => $value) {
      $query[$key] = $value;
    }
    $this->boardgameManager->setApiSetting('url', $this->boardgameManager->getConfigKey('api_url') . '/search');
    $this->boardgameManager->setApiSetting('query', $query);

    $this->boardgameManager->getApiResponse('boardgame_atlas');
    return $this->boardgameManager->getApiResponseObject();
  }

  public function doBuildTable(array $boardgames) {
    $form['games'] = $this->doBuildTableHeader($boardgames);
    $form['games'] += $this->doBuildTableRows($boardgames);

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $tempStore = $this->privateTempStore->get('boardgame');
    $tempStore->set('skip', 0);
    $data = $form_state->getValue('search_terms');
    $url = Url::fromRoute('boardgame.search_api_form', [], ['query' => ['search_terms' => $data]]);
    $form_state->setRedirectUrl($url);
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function submitLoadMore(array &$form, FormStateInterface $form_state) {
    $tempStore = $this->privateTempStore->get('boardgame');
    $this->limit = $tempStore->get('limit');
    $this->skip = $tempStore->get('skip') + $this->limit;
    $tempStore->set('skip', $this->skip);
    $form_state->setValue('skip', $this->skip);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Ajax callback.
   *
   * @param array $form
   *   The form object.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form_state object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response object.
   */
  public function searchAjaxLoadMore(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  public function doBuildTableHeader(array $boardgames) {
    return [
      '#type' => 'table',
      '#attributes' => [
        'class' => ['boardgames-table', 'mt-5', 'table', 'table-striped'],
      ],
      '#header' => [
        t('Box Art'),
        t('Information'),
        t('Operations'),
      ],

      '#empty' => t('There are no items yet. <a href="@add-url">Add an item.</a>', array(
        '@add-url' => '',
      )),
    ];
  }

  public function doBuildTableRows(array $boardgames) {
    $form = [];
    foreach ($boardgames['games'] as $id => $game) {
      $form[$id]['#attributes']['class'][] = 'row';
      $form[$id]['#weight'] = $id;

      $image = new FormattableMarkup('<img class="img-fluid rounded" src="@image" />', [
        '@image' => $game['thumb_url']
      ]);
      $form[$id]['boxart'] = array(
        '#markup' => $image,
        '#wrapper_attributes' => [
          'class' => 'col-3',
          'style' => 'width:150px',
        ],
      );

      $truncate = new TruncateHTML();
      $length = 50;
      $ellipse = '...';
      $output = '<h3>' . $game['name'] . '</h3><br/>' . $truncate->truncateWords($game['description_preview'], $length, $ellipse);

      $form[$id]['description'] = array(
        '#markup' => new FormattableMarkup($output, []),
        '#wrapper_attributes' => [
          'class' => 'col-5',
          'style' => 'vertical-align: top;',
        ],
      );

      $form[$id]['operations'] = array(
        '#type' => 'operations',
        '#links' => array(),
        '#wrapper_attributes' => [
          'class' => 'col-1',
          'style' => 'vertical-align: top;',
        ],
      );
      $form[$id]['operations']['#links']['add'] = array(
        'title' => t('Add'),
        'url' => Url::fromRoute('boardgame.search_api_add', array('api_boardgame' => $game['id'])),
      );
      $form[$id]['operations']['#links']['view'] = array(
        'title' => t('View'),
        'url' => Url::fromUri($game['url'], [
          'attributes' => [
            'target' => '_blank',
          ],
        ]),
      );
    }
    return $form;
  }
}
