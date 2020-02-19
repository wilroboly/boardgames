<?php

namespace Drupal\boardgames\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\boardgames\Entity\BoardgameInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class BoardgameController.
 *
 *  Returns responses for Boardgame routes.
 */
class BoardgameController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->renderer = $container->get('renderer');
    return $instance;
  }

  /**
   * Displays a Boardgame revision.
   *
   * @param int $boardgame_revision
   *   The Boardgame revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($boardgame_revision) {
    $boardgame = $this->entityTypeManager()->getStorage('boardgame')
      ->loadRevision($boardgame_revision);
    $view_builder = $this->entityTypeManager()->getViewBuilder('boardgame');

    return $view_builder->view($boardgame);
  }

  /**
   * Page title callback for a Boardgame revision.
   *
   * @param int $boardgame_revision
   *   The Boardgame revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($boardgame_revision) {
    $boardgame = $this->entityTypeManager()->getStorage('boardgame')
      ->loadRevision($boardgame_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $boardgame->label(),
      '%date' => $this->dateFormatter->format($boardgame->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Boardgame.
   *
   * @param \Drupal\boardgames\Entity\BoardgameInterface $boardgame
   *   A Boardgame object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(BoardgameInterface $boardgame) {
    $account = $this->currentUser();
    $boardgame_storage = $this->entityTypeManager()->getStorage('boardgame');

    $langcode = $boardgame->language()->getId();
    $langname = $boardgame->language()->getName();
    $languages = $boardgame->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $boardgame->label()]) : $this->t('Revisions for %title', ['%title' => $boardgame->label()]);

    $header = [$this->t('Revision'), $this->t('Operations')];
    $revert_permission = (($account->hasPermission("revert all boardgame revisions") || $account->hasPermission('administer boardgame entities')));
    $delete_permission = (($account->hasPermission("delete all boardgame revisions") || $account->hasPermission('administer boardgame entities')));

    $rows = [];

    $vids = $boardgame_storage->revisionIds($boardgame);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\boardgames\BoardgameInterface $revision */
      $revision = $boardgame_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $boardgame->getRevisionId()) {
          $link = $this->l($date, new Url('entity.boardgame.revision', [
            'boardgame' => $boardgame->id(),
            'boardgame_revision' => $vid,
          ]));
        }
        else {
          $link = $boardgame->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.boardgame.translation_revert', [
                'boardgame' => $boardgame->id(),
                'boardgame_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.boardgame.revision_revert', [
                'boardgame' => $boardgame->id(),
                'boardgame_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.boardgame.revision_delete', [
                'boardgame' => $boardgame->id(),
                'boardgame_revision' => $vid,
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['boardgame_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
