<?php

namespace Drupal\boardgames;

use Drupal\boardgames\Entity\BoardgameInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Interface for managing boardgame API entities.
 *
 * The purpose of these methods is to make the CRUD process of a boardgame
 * collected from an API rest point to be made simple.
 *
 * @package Drupal\boardgames
 */
interface BoardgameManagerInterface {

  /**
   * @return mixed
   */
  public function getConfigFactory();

  /**
   * Updates a boardgame entity from the API REST endpoint.
   *
   * @param \Drupal\boardgames\Entity\BoardgameInterface $entity
   *
   * @return mixed
   */
  public function updateApiBoardgameEntity(BoardgameInterface $entity);

  /**
   * Creates a boardgame entity from the API REST endpoint data.
   *
   * @return mixed
   */
  public function createApiBoardgameEntity();

  /**
   * Get Boardgame Atlast API response.
   *
   * @param $api
   *   The string representing the api being used.
   * @return mixed
   */
  public function getApiResponse($api);

  /**
   * Build the Api URL.
   *
   * @return mixed
   */
  public function buildApiUrl();

  /**
   * Render the Boardgame Entity after an API creation / update.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @param string $entity_type
   *
   * @return mixed
   */
  public function renderApiBoardgameEntity(EntityInterface $entity, $entity_type);

  /**
   * Get the API to Entity field mapping table.
   *
   * @return mixed
   */
  public function getFieldMapping();

  /**
   * Check if the field is a reference field type or not.
   *
   * @param $field
   *
   * @return bool
   *   Returns TRUE or FALSE.
   */
  public function isFieldMappingReference($field);

  /**
   * Set the API Entity field mapping table.
   *
   * @param $field_mapping
   *
   * @return mixed
   */
  public function setFieldMapping($field_mapping);

  /**
   * Get the API to Entity reference field mapping table.
   *
   * @return mixed
   */
  public function getFieldReferenceMapping();

  /**
   * @param $field_mapping
   *
   * @return mixed
   */
  public function setFieldReferenceMapping($field_mapping);

  /**
   * Get the API Term object.
   *
   * @param array $properties
   *
   * @return mixed
   */
  public function getApiTerm(array $properties);

  /**
   * @param array $term_definition
   *
   * @return mixed
   */
  public function createApiTerm(array $term_definition);

  /**
   * Get the API Term (referenced) Objects.
   *
   * The Boardgame Atlas services stores its Mechanics and Categories as
   * objects. Therefore, to be useful, we need to do a lookup on all the
   * terms. Store them in our system and allow for some CRUD on these values.
   *
   * @TODO: Potentially use the migrate module for this.
   *
   * @param $id
   *
   * @return mixed
   */
  public function getApiReferenceValue($id);

  /**
   * @return mixed
   */
  public function getEntityTypeManager();

  /**
   * @return mixed
   */
  public function getEntityFieldManager();

  /**
   * @param string $key
   *
   * @return mixed
   */
  public function getApiSettings($key = NULL);

  /**
   * @param array $settings
   *
   * @return mixed
   */
  public function setApiSettings(array $settings);

  /**
   * @param $key
   * @param $value
   *
   * @return mixed
   */
  public function setApiSetting($key, $value);

  /**
   * @return mixed
   */
  public function setBoardgameObject();

  /**
   * @return mixed
   */
  public function getBoardgameObject();

  /**
   * Returns from a list of games, the likeliest game.
   *
   * @param string $needle
   *   The needle to find in the haystack.
   * @param $value
   *   The value of the needle in the haystack to find.
   *
   * @return mixed
   */
  public function bestBoardgameObject($needle, $value);

  /**
   * @return mixed
   */
  public function setBoardgameFieldValues();

  /**
   * @return mixed
   */
  public function setBoardgameFieldReferenceValues();

  /**
   * @return mixed
   */
  public function buildBoardgameEntityValues();

  /**
   * @return mixed
   */
  public function getApiResponseObject();

  /**
   * @param array $object
   *
   * @return mixed
   */
  public function setApiResponseObject(array $object);

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return mixed
   */
  public function updateBoardgameFormFields(EntityInterface $entity);

  /**
   * @param $boardgame_id
   *
   * @return mixed
   */
  public function setBoardgameId($boardgame_id);

  /**
   * @return mixed
   */
  public function getBoardgameId();

  /**
   * @param $boardgame_name
   *
   * @return mixed
   */
  public function setBoardgameName($boardgame_name);

  /**
   * @return mixed
   */
  public function getBoardgameName();

  /**
   * @param $api
   *   The string representing the API to be setup for its field mapping.
   * @return mixed
   */
  public function setupFieldMappings($api);

  /**
   * @param $api
   *
   * @return mixed
   */
  public function setCurrentApi($api);

  /**
   * @return mixed
   */
  public function getCurrentApi();

  /**
   * @param $key
   *
   * @return mixed
   */
  public function getConfigKey($key);
}
