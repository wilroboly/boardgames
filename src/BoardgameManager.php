<?php

namespace Drupal\boardgames;

use Drupal\boardgames\Entity\Boardgame;
use Drupal\boardgames\Entity\BoardgameInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use GuzzleHttp\Client as GuzzleClient;

/**
 * Class BoardgameManager
 *
 * @package Drupal\boardgames
 */
class BoardgameManager implements BoardgameManagerInterface {

  use StringTranslationTrait;

  /**
   * @var array
   */
  protected $boardgameFieldValues;

  /**
   * @var string
   */
  protected $boardgameName;

  /**
   * @var string
   */
  protected $boardgameId;

  /**
   * @var array
   */
  protected $boardgameObject;

  /**
   * @var array
   */
  protected $apiResponseObject;

  /**
   * @var array
   */
  protected $fieldReferenceMapping;

  /**
   * @var array
   */
  protected $fieldMapping;

  /**
   * @var array
   */
  protected $settings;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * current number of inMultiArray() loop
   *
   * @var int
   */
  private $currentMultiArrayExec = 0;

  /**
   * @var
   */
  public $current_api;

  /**
   * BoardgameManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ConfigFactoryInterface $config_factory, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->configFactory = $config_factory;
    $this->stringTranslation = $string_translation;
    $this->setBoardgameObject();
    $this->setApiSettings([]);
    $this->boardgameFieldValues = [];
    $this->setupFieldMappings();
  }

  /**
   * @inheritDoc
   */
  public function getConfigFactory() {
    return $this->configFactory;
  }

  /**
   * @inheritDoc
   */
  public function getEntityTypeManager() {
    return $this->entityTypeManager;
  }

  /**
   * @inheritDoc
   */
  public function getEntityFieldManager() {
    return $this->entityFieldManager;
  }

  /**
   * @inheritDoc
   */
  public function updateApiBoardgameEntity(BoardgameInterface $entity) {
    // TODO: Implement updateApiBoardgameEntity() method.
  }

  /**
   * @inheritDoc
   */
  public function createApiBoardgameEntity() {
    return Boardgame::create($this->boardgameFieldValues);
  }

  /**
   * @inheritDoc
   * @throws \Exception
   */
  public function getApiResponse($api) {
    $this->setCurrentApi($api);
    if (!$this->getApiSettings('url') || !$this->getApiSettings('query')) {
      throw new \Exception('API is missing some settings ');
    }
    $config = $this->configFactory->getEditable('boardgames.settings');

    $client = new GuzzleClient();
    $url = $this->buildApiUrl();
    $response = $client->get($url->toString());
    if ($response->getStatusCode() !== 200 && $response->getStatusCode() !== 304) {
      throw new \Exception(
        'API failed to load, got response code ' . $response->getStatusCode()
      );
    }
    // @TODO: replace current settings approach and have settings mapped to
    //        services.

    $api_response_type = $config->get($api . '.api_type');
    switch ($api_response_type) {
      case 'xml':
        //$this->setApiResponseObject();
        break;

      case 'json':
        $this->setApiResponseObject(Json::decode($response->getBody()->__toString()));
        break;

      default:
        throw new \Exception(
          'API response type could not be found : ' . $response->getStatusCode()
        );
    }
  }


  /**
   * @inheritDoc
   */
  public function buildApiUrl() {
    return Url::fromUri($this->getApiSettings('url'), [
      'query' => $this->getApiSettings('query'),
    ]);
  }

  /**
   * @inheritDoc
   */
  public function renderApiBoardgameEntity(EntityInterface $entity, $entity_type) {
    $view_mode = 'full';
    $view_builder = $this->entityTypeManager->getViewBuilder($entity_type);
    return $view_builder->view($entity, $view_mode);
  }

  /**
   * @inheritDoc
   */
  public function setFieldMapping($field_mapping) {
    $this->fieldMapping = $field_mapping;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getFieldMapping() {
    return $this->fieldMapping;
  }


  /**
   * @inheritDoc
   */
  public function setFieldReferenceMapping($field_mapping) {
    $this->fieldReferenceMapping = $field_mapping;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getFieldReferenceMapping() {
    return $this->fieldReferenceMapping;
  }

  /**
   * @inheritDoc
   */
  public function getApiTerm(array $properties) {
    $term = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadByProperties($properties);
    return reset($term);
  }

  /**
   * @inheritDoc
   */
  public function createApiTerm(array $term_definition) {
    $term = \Drupal\taxonomy\Entity\Term::create($term_definition);
    $term->save();
    return $term;
  }

  /**
   * @inheritDoc
   */
  public function getApiReferenceValue($id) {
    // TODO: Implement getApiReferenceValue() method.
  }

  /**
   * @inheritDoc
   */
  public function getApiSettings($key = NULL) {
    $setting = FALSE;
    if ($key !== NULL) {
      try {
        $setting = $this->settings[$key];
      }
      catch (\Exception $e) {
        return FALSE;
      }
      return $setting;
    }
    else {
      return $this->settings;
    }
  }

  /**
   * @inheritDoc
   */
  public function setApiSettings(array $settings) {
    $this->settings = $settings;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function setApiSetting($key, $value) {
    $this->settings[$key] = $value;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function setBoardgameObject() {
    $boardgameObject = $this->getApiResponseObject();
    if (isset($boardgameObject['games'])) {
      $this->setBoardgameId($boardgameObject['games'][0]['id']);
      $this->setBoardgameName($boardgameObject['games'][0]['name']);
      $this->boardgameObject = $boardgameObject['games'][0];
    }
    else {
      $this->boardgameObject = [];
      $this->setBoardgameId(NULL);
      $this->setBoardgameName(NULL);
    }
  }

  /**
   *
   */
  public function resetBoardgameObject() {
    $this->boardgameObject = [];
    $this->setBoardgameId(NULL);
    $this->setBoardgameName(NULL);
  }

  /**
   * @inheritDoc
   */
  public function bestBoardgameObject($needle, $value) {
    $boardgameObject = reset($this->getApiResponseObject());
    if (count($boardgameObject) > 1) {
      foreach($boardgameObject as $key => $boardgame) {
        if ($boardgame[$needle] == $value) {
          $this->setBoardgameId($boardgame['id']);
          $this->setBoardgameName($boardgame['name']);
          $this->boardgameObject = $boardgame;
          return TRUE;
        }
      }
    }
    else {
      $this->setBoardgameObject();
      return TRUE;
    }
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function getBoardgameObject() {
    return $this->boardgameObject;
  }

  /**
   * @inheritDoc
   */
  public function setBoardgameFieldValues() {
    $current_game = $this->getBoardgameObject();
    foreach ($this->getFieldMapping() as $boardgame_field => $entity_field) {
      if (is_array($entity_field)) {
        if ($entity_field['attribute'] == 'value' && isset($entity_field['format'])) {
          $this->boardgameFieldValues[$entity_field['field']]['format'] = $entity_field['format'];
        }
        if (isset($entity_field['attribute'])) {
          $this->boardgameFieldValues[$entity_field['field']][$entity_field['attribute']] = $current_game[$boardgame_field];
        }
        if (isset($entity_field['size'])) {
          $this->boardgameFieldValues[$entity_field['field']] = substr($current_game[$boardgame_field], 0, $entity_field['size']);
        }
      }
      else {
        $this->boardgameFieldValues[$entity_field] = $current_game[$boardgame_field];
      }
    }
  }

  /**
   * @inheritDoc
   * @throws \Exception
   */
  public function setBoardgameFieldReferenceValues() {
    // This value should be acquire from the entity type.
    $entity_type = 'boardgame';
    $current_game = $this->getBoardgameObject();
    $config = $this->getConfigFactory()->get('boardgames.settings');
    $definitions = $this->entityFieldManager->getFieldDefinitions($entity_type, $entity_type);
    foreach ($this->getFieldReferenceMapping() as $boardgame_field => $entity_field) {
      if (isset($definitions[$entity_field['name']])) {
        if ($entity_field['type'] == 'remote_image') {

          // If the reference field has an object set to FALSE, meaning we
          // are not going to call upon the game object for values, we are
          // instead going to leverage the image uploaded to the API for
          // the game in question.
          if (isset($entity_field['object']) && $entity_field['object'] == FALSE) {
            $this->setApiSetting('url', $config->get('api_url') . $entity_field['uri']);
            $this->setApiSetting('query', [
              'client_id' => $config->get('client_id'),
              'limit' => 20,
              'game_id' => $this->getBoardgameId(),
            ]);
            $this->getApiResponse('boardgame_atlas');
            $response = $this->getApiResponseObject()[$boardgame_field];

            foreach ($response as $image_key => $image_values) {
              foreach(['small','medium','large'] as $size_key) {
                $uri = $image_values[$size_key];

                // check first if the file exists for the uri
                $files = $this->entityTypeManager->getStorage('file')
                  ->loadByProperties(['uri' => $uri]);
                $file = reset($files);

                if (isset($entity_field['title'])) {
                  $title = $entity_field['title'];
                }
                else {
                  $title = 'Artwork';
                }
                $title = $title . ' (' . $size_key . ')';

                // if not create a file
                if (!$file) {
                  $file = File::create([
                    'uri' => $uri,
                    'alt' => $title . ' for ' . $this->getBoardgameName(),
                    'title' => $title . ' for ' . $this->getBoardgameName(),
                  ]);
                  $file->save();
                }

                $this->boardgameFieldValues[$entity_field['name']][] = [
                  'target_id' => $file->id(),
                ];

              }
            }

          }

          if (isset($entity_field['flat']) && $entity_field['flat'] == TRUE) {
            $uri = $current_game[$boardgame_field];
            if (!is_null($uri)) {
              // check first if the file exists for the uri
              $files = $this->entityTypeManager->getStorage('file')
                ->loadByProperties(['uri' => $uri]);
              $file = reset($files);

              $destination = $definitions[$entity_field['name']]->getSetting('file_directory');

              if (isset($entity_field['title'])) {
                $title = $entity_field['title'];
              }
              else {
                $title = 'Artwork';
              }
              // if not create a file
              if (!$file) {
                $filename = \Drupal::service('file_system')->basename($uri);
                $file_parts = pathinfo($filename);
                if (!isset($file_parts['extension'])) {
                  $filename_with_extension = $filename . '.jpg';
                  $external_file = file_get_contents($uri);
                  $destination = \Drupal::token()
                    ->replace('public://' . $destination);
                  \Drupal::service('file_system')->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
                  $location = $destination . '/' . $filename_with_extension;
                  $image_file = file_save_data($external_file, $location, FileSystemInterface::EXISTS_REPLACE);
                  $uri = $image_file->getFileUri();
                }
                $file = File::create([
                  'uri' => $uri,
                  'alt' => $title . ' for ' . $this->getBoardgameName(),
                  'title' => $title . ' for ' . $this->getBoardgameName(),
                ]);
                $file->save();
              }
              else {
                $filename = \Drupal::service('file_system')->basename($uri);
                $file_parts = pathinfo($filename);
                if (!isset($file_parts['extension'])) {
                  $filemime = explode('/', $file->getMimeType());
                  $filename_with_extension = $filename . '.' . $filemime[1];
                  $external_file = file_get_contents($uri);
                  $destination = \Drupal::token()
                    ->replace('public://' . $destination);
                  \Drupal::service('file_system')->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
                  $location = $destination . '/' . $filename_with_extension;
                  $image_file = file_save_data($external_file, $location, FileSystemInterface::EXISTS_REPLACE);
                  $uri = $image_file->getFileUri();
                  // Remove old image.
                  $file->delete();
                  // Replace the old file.
                  $file = File::create([
                    'uri' => $uri,
                    'alt' => $title . ' for ' . $this->getBoardgameName(),
                    'title' => $title . ' for ' . $this->getBoardgameName(),
                  ]);
                  $file->save();
                }
              }

              $this->boardgameFieldValues[$entity_field['name']] = [
                'target_id' => $file->id(),
                'alt' => $title . ' for ' . $this->getBoardgameName(),
                'title' => $title . ' for ' . $this->getBoardgameName(),
              ];
            }
          }

        }
        else {
          $handlers = $definitions[$entity_field['name']]->getSetting('handler_settings')['target_bundles'];
          foreach ($handlers as $bundle => $target_bundle) {
            foreach ($current_game[$boardgame_field] as $key => $term_name) {

              // For each taxonomy term, we need to check whether it is a
              // reference to a tag, a remote image or a text tag. Each will be
              // handled separately.
              if ($entity_field['type'] == 'reference') {

                $term = $this->getApiTerm([
                  'vid' => $target_bundle,
                  'field_id' => $term_name,
                ]);
                if ($term) {
                  $this->boardgameFieldValues[$entity_field['name']][$key] = [
                    'target_id' => $term->id(),
                  ];
                }
                else {
                  $this->setApiSetting('url', $this->getConfigKey('api_url') . $entity_field['uri']);
                  $this->setApiSetting('query', [
                    'client_id' => $this->getConfigKey('client_id'),
                  ]);
                  $this->getApiResponse('boardgame_atlas');
                  $response = $this->getApiResponseObject()[$boardgame_field];
                  $response_key = array_search($term_name['id'], array_column($response, 'id'));
                  $term_name = $response[$response_key];
                  $term_definition = [
                    'vid' => $target_bundle,
                    'name' => $term_name['name'],
                    'field_id' => $term_name['id'],
                  ];
                  $term = $this->createApiTerm($term_definition);
                  $this->boardgameFieldValues[$entity_field['name']][$key] = [
                    'target_id' => $term->id(),
                  ];
                }

              }
              else {

                // String Tags can just be associated directly with the field.
                $term = $this->getApiTerm([
                  'vid' => $target_bundle,
                  'name' => $term_name,
                ]);
                // Check if the referenced field exists, if so, use ID
                if ($term) {
                  $this->boardgameFieldValues[$entity_field['name']][$key] = [
                    'target_id' => $term->id(),
                  ];
                }
                else {
                  $term_definition = [
                    'vid' => $target_bundle,
                    'name' => $term_name,
                  ];
                  $term = $this->createApiTerm($term_definition);
                  $this->boardgameFieldValues[$entity_field['name']][$key] = [
                    'target_id' => $term->id(),
                  ];
                }

              }
            }
          }
        }
      }
    }
  }

  /**
   * @inheritDoc
   * @throws \Exception
   */
  public function buildBoardgameEntityValues() {
    $this->getApiResponse('boardgame_atlas');
    $this->setBoardgameObject();
    $this->setBoardgameFieldValues();
    $this->setBoardgameFieldReferenceValues();
    $entity = $this->createApiBoardgameEntity();
    $entity->save();
    return $entity;
  }

  /**
   * @inheritDoc
   */
  public function getApiResponseObject() {
    return $this->apiResponseObject;
  }

  /**
   * @inheritDoc
   */
  public function setApiResponseObject(array $object) {
    $this->apiResponseObject = $object;
  }

  /**
   * @inheritDoc
   */
  public function updateBoardgameFormFields(EntityInterface $entity) {
    $values = $this->boardgameFieldValues;
    foreach($values as $field => $value) {
      $entity->set($field, $value);
    }
  }

  /**
   * @inheritDoc
   */
  public function setBoardgameId($boardgame_id) {
    $this->boardgameId = $boardgame_id;
  }

  /**
   * @inheritDoc
   */
  public function getBoardgameId() {
    return $this->boardgameId;
  }

  /**
   * @inheritDoc
   */
  public function setBoardgameName($boardgame_name) {
    $this->boardgameName = $boardgame_name;
  }

  /**
   * @inheritDoc
   */
  public function getBoardgameName() {
    return $this->boardgameName;
  }

  /**
   * @inheritDoc
   */
  public function isFieldMappingReference($field) {
    if ($this->inMultiArray($field, $this->getFieldReferenceMapping())) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Checks if an element is found in an array or one of its subarray.
   * The verification layer of this method is limited to the parameter boundary
   * xdebug.max_nesting_level of your php.ini.
   *
   * @param mixed $element
   * @param array $array
   * @param bool $strict
   *
   * @return bool
   */
  public function inMultiArray($element, array $array, $strict = false) {
    $this->currentMultiArrayExec++;

    if($this->currentMultiArrayExec >= ini_get("xdebug.max_nesting_level")) return false;

    foreach($array as $key => $value){
      $bool = $strict ? $element === $key : $element == $key;

      if($bool) return true;

      if(is_array($value)){
        $bool = $this->inMultiArray($element, $value, $strict);
      } else {
        $bool = $strict ? $element === $value : $element == $value;
      }

      if($bool) return true;
    }

    $this->currentMultiArrayExec = 0;
    return isset($bool) ? $bool : false;
  }

  /**
   * @inheritDoc
   */
  public function setupFieldMappings($api = 'boardgame_atlas') {
    $config = $this->configFactory->getEditable('boardgames.mappings.' . $api);
    $this->setFieldMapping($config->get('field_mapping'));
    $this->setFieldReferenceMapping($config->get('field_reference_mapping'));
  }

  /**
   * @inheritDoc
   */
  public function setCurrentApi($api = 'boardgame_atlas') {
    $this->current_api = $api;
  }

  /**
   * @inheritDoc
   */
  public function getCurrentApi() {
    return $this->current_api;
  }

  /**
   * @param $key
   *
   * @return mixed|void
   */
  public function getConfigKey($key) {
    $config = $this->getConfigFactory()->get('boardgames.settings');
    $api = $this->getCurrentApi();
    return $config->get($api . '.' . $key);
  }

}
