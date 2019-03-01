<?php

namespace Drupal\bnald_migrate\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Retrieve the corresponding name to a file entity's ID.
 *
 * @MigrateProcessPlugin(
 *   id = "file_name"
 * )
 *
 * To do custom value transformations use the following:
 *
 * @code
 * field_file:
 *   plugin: file_name
 *   path: path/to/file
 *   source: fid
 * @endcode
 */
class FileName extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * An instance of the d7_file source plugin.
   *
   * @var Drupal\migrate\Plugin\migrate\source\SqlBase
   */
  protected $filePlugin;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, string $plugin_id, array $plugin_definition, SqlBase $file_plugin) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->filePlugin = $file_plugin;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration =
  NULL) {
    return new static($configuration, $plugin_id, $plugin_definition, $container
      ->get('plugin.manager.migrate.source')
      ->createInstance('d7_file', $configuration, $migration));
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $records = $this->executeD7FileQueryWhereFileIdEquals($value);
    $record = $records->fetch();
    return $this->pathTo($record['filename']);
  }

  /**
   * A d7_file query extended by a where condition.
   *
   * @param int $fid
   *   The file entity ID to query for.
   *
   * @return string
   *   The queried file entity's filename.
   */
  protected function executeD7FileQueryWhereFileIdEquals(int $fid) {
    return $this->filePlugin
      ->query()
      ->condition('f.fid', $fid, '=')
      ->execute();
  }

  /**
   * The path to the queried file, if configured, or the filename only.
   *
   * @param string $filename
   *   The name of the file.
   *
   * @return string
   *   The filepath and/or name
   */
  private function pathTo(string $filename) {
    $path = isset($this->configuration['path']) ? $this->configuration['path'] . DIRECTORY_SEPARATOR : '';
    return $path . $filename;
  }

}
