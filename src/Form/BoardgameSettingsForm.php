<?php

namespace Drupal\boardgames\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BoardgameSettingsForm.
 *
 * @ingroup boardgames
 */
class BoardgameSettingsForm extends ConfigFormBase {

  /**
   * The aggregator plugin definitions.
   *
   * @var array
   */
  protected $definitions = [
    'boardgame_atlas' => [
      'api_url' => 'https://www.boardgameatlas.com/api',
      'client_id' => '',
      'client_secret' => '',
      'api_type' => 'json',
    ],
    'boardgame_geek' => [
      'api_url' => 'https://www.boardgamegeek.com/xmlapi',
      'api_type' => 'xml',
    ],
  ];

  /**
   * BoardgameSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return \Drupal\Core\Form\ConfigFormBase|\Drupal\Core\Form\FormBase|static
   */
  public static function create(ContainerInterface $container) {
    return  new static(
      $container->get('config.factory')
    );
  }

  /**
   * @inheritDoc
   */
  protected function getEditableConfigNames() {
    return ['boardgames.settings'];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'boardgame_settings';
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
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('boardgames.settings');

    $type_options = [
      'json' => 'JSON',
      'xml'  => 'XML',
    ];

    $form['boardgame_atlas'] = [
      '#type' => 'details',
      '#title' => t('Board Game Atlas settings'),
      '#open' => TRUE,
      '#weight' => 1,
      '#tree' => TRUE,
    ];

    $form['boardgame_atlas']['info'] = [
      '#markup' => $this->t('The Board Game Atlas API is free and easy to use. To learn more about it, follow this <a href="@link" target="_blank">link</a>. To create a client_id, you will need to setup a profile and <strong>create an app</strong>. You do not need to provide a <em>redirect_uri</em> at this time, so you can skip that step. The Board Game Atlas API leverages <strong>JSON</strong>.', ['@link' => 'https://www.boardgameatlas.com/api/docs']),
    ];

    $form['boardgame_atlas']['api_url'] = [
      '#type' => 'url',
      '#title' => $this->t('API REST URI'),
      '#size' => 60,
      '#placeholder' => 'https://www.boardgameatlas.com/api',
      '#maxlength' => 2048,
      '#default_value' => $config->get('boardgame_atlas.api_url') == NULL ? $this->definitions['boardgame_atlas']['api_url'] : $config->get('boardgame_atlas.api_url'),
      '#description' => $this->t('Please leave out the trailing slash.'),
      '#group' => 'boardgame_atlas',
    ];

    $form['boardgame_atlas']['api_list'] = [
      '#markup' =>
        '<pre>' . $config->get('boardgame_atlas.api_url') . '/{search}/' . "\n" .
        $config->get('boardgame_atlas.api_url') . '/{game}/{mechanics}' . "\n" .
        $config->get('boardgame_atlas.api_url') . '/{game}/{categories}/' . '</pre>' .
      '<p>' . $this->t('The REST endpoints above will be used by the module to communicate with Boardgame Atlas\'s API. Please make sure they are correct and you are able to receive successful return.') . '</p>',
      '#group' => 'boardgame_atlas',
    ];

    $form['boardgame_atlas']['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#size' => 16,
      '#default_value' => $config->get('boardgame_atlas.client_id'),
    ];

    $form['boardgame_atlas']['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Secret'),
      '#size' => 32,
      '#default_value' => $config->get('boardgame_atlas.client_secret'),
      '#description' => $this->t('The Client Secret is mainly used for WRITE authentication. Presently, since we really only GET, this won\'t currently be used. But it is always worth being prepared as we may extend the functionality of this module to work with the Boardgame Atlas game listing functionality.'),
    ];

    $form['boardgame_atlas']['api_type'] = [
      '#type' => 'select',
      '#title' => $this->t('The API Response type'),
      '#options' => $type_options,
      '#value' => $config->get('boardgame_atlas.api_type') == NULL ? $this->definitions['boardgame_atlas']['api_type'] : $config->get('boardgame_atlas.api_type'),
    ];

    $form['boardgame_geek'] = [
      '#type' => 'details',
      '#title' => t('Board Game Geek settings'),
      '#open' => TRUE,
      '#weight' => 1,
      '#tree' => TRUE,
    ];

    $form['boardgame_geek']['api_url'] = [
      '#type' => 'url',
      '#title' => $this->t('API XMLRPC URI'),
      '#size' => 60,
      '#placeholder' => 'https://www.boardgamegeek.com/xmlapi',
      '#maxlength' => 2048,
      '#default_value' => $config->get('boardgame_geek.api_url') == NULL ? $this->definitions['boardgame_geek']['api_url'] : $config->get('boardgame_geek.api_url'),
      '#description' => $this->t('Please leave out the trailing slash.'),
      '#group' => 'boardgame_geek',
    ];

    $form['boardgame_geek']['api_list'] = [
      '#markup' =>
        '<pre>' . $config->get('boardgame_geek.api_url') . '/{search}/' . "\n" .
        $config->get('boardgame_geek.api_url') . '/{boardgame}/' . '</pre>' .
        '<p>' . $this->t('The XMLRPC endpoints above will be used by the module to communicate with Board Game Geek\'s API. Please make sure they are correct and you are able to receive successful return. There is NO client ID or authorization required unless you wish to interact with their PUT mechanism (which we do not provide at the moment.)') . '</p>',
      '#group' => 'boardgame_geek',
    ];

    $form['boardgame_geek']['api_type'] = [
      '#type' => 'select',
      '#title' => $this->t('The API Response type'),
      '#options' => $type_options,
      '#value' => $config->get('boardgame_geek.api_type') == NULL ? $this->definitions['boardgame_geek']['api_type'] : $config->get('boardgame_geek.api_type'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('boardgames.settings');

    $config->set('boardgame_atlas.api_url', rtrim($form_state->getValue(['boardgame_atlas', 'api_url']), '/'));
    $config->set('boardgame_atlas.client_id', $form_state->getValue(['boardgame_atlas', 'client_id']));
    $config->set('boardgame_atlas.client_secret', $form_state->getValue(['boardgame_atlas', 'client_secret']));
    $config->set('boardgame_atlas.api_type', $form_state->getValue(['boardgame_atlas', 'api_type']));
    $config->set('boardgame_geek.api_url', rtrim($form_state->getValue(['boardgame_geek', 'api_url']), '/'));
    $config->set('boardgame_geek.api_type', $form_state->getValue(['boardgame_geek', 'api_type']));

    $config->save();
  }

}
